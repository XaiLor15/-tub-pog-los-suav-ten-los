<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Backups to storage/app/private/backups:
 *  - database: mysqldump/mariadb-dump (gzip) or a copy of the SQLite file
 *  - files:    tar.gz of uploaded media (storage/app/private, without backups)
 * Keeps the newest BACKUP_KEEP (default 14) of each kind.
 */
class BackupService
{
    public function dir(): string
    {
        $dir = storage_path('app/private/backups');
        File::ensureDirectoryExists($dir, 0750);

        return $dir;
    }

    public function database(): string
    {
        $cfg = config('database.connections.'.config('database.default'));
        $stamp = now()->format('Ymd-His');

        if ($cfg['driver'] === 'sqlite') {
            $target = $this->dir()."/db-{$stamp}.sqlite";
            File::copy($cfg['database'], $target);

            return $this->rotate('db-', $target);
        }

        $target = $this->dir()."/db-{$stamp}.sql.gz";
        $bin = trim((string) Process::run('command -v mariadb-dump || command -v mysqldump')->output());
        if ($bin === '') {
            throw new RuntimeException('mariadb-dump / mysqldump not found');
        }
        // Password goes through the environment, never the command line (not visible in `ps`).
        $result = Process::env(['MYSQL_PWD' => (string) $cfg['password']])->timeout(600)->run(sprintf(
            '%s --single-transaction --quick --routines --default-character-set=utf8mb4 -h %s -P %s -u %s %s | gzip > %s',
            escapeshellarg($bin), escapeshellarg($cfg['host']), escapeshellarg((string) $cfg['port']),
            escapeshellarg($cfg['username']), escapeshellarg($cfg['database']), escapeshellarg($target)
        ));
        if (! $result->successful() || filesize($target) < 100) {
            @unlink($target);
            throw new RuntimeException('Database dump failed: '.$result->errorOutput());
        }

        return $this->rotate('db-', $target);
    }

    public function files(): string
    {
        $target = $this->dir().'/files-'.now()->format('Ymd-His').'.tar.gz';
        $result = Process::timeout(1800)->run(sprintf(
            'tar --exclude=./backups -czf %s -C %s .', escapeshellarg($target), escapeshellarg(storage_path('app/private'))
        ));
        if (! $result->successful()) {
            @unlink($target);
            throw new RuntimeException('File archive failed: '.$result->errorOutput());
        }

        return $this->rotate('files-', $target);
    }

    private function rotate(string $prefix, string $created): string
    {
        $keep = max(1, (int) config('app.backup_keep', 14));
        $all = collect(File::files($this->dir()))->filter(fn ($f) => str_starts_with($f->getFilename(), $prefix))
            ->sortByDesc(fn ($f) => $f->getFilename())->values();
        $all->slice($keep)->each(fn ($f) => File::delete($f->getPathname()));

        return $created;
    }
}
