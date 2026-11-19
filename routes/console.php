<?php

use App\Services\BackupService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('kwvtij:backup {type=full : database|files|full}', function (BackupService $backup, string $type) {
    if (! in_array($type, ['database', 'files', 'full'], true)) {
        $this->error('type must be database, files or full');

        return 1;
    }
    try {
        if ($type !== 'files') {
            $this->info('Database: '.$backup->database());
        }
        if ($type !== 'database') {
            $this->info('Files: '.$backup->files());
        }
    } catch (Throwable $e) {
        $this->error($e->getMessage());

        return 1;
    }

    return 0;
})->purpose('Back up the database and uploaded files to storage/app/private/backups');

// Daily at 02:00 (server cron: * * * * * php artisan schedule:run)
Schedule::command('kwvtij:backup full')->dailyAt('02:00')->withoutOverlapping();
