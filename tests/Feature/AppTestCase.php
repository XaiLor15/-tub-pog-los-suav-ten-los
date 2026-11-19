<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Member;
use App\Models\User;
use App\Services\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

abstract class AppTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
    }

    protected function user(string $role = 'member', array $attrs = []): User
    {
        static $n = 0;
        $n++;
        $u = new User(['display_name' => "User $n", 'email' => "user{$n}@test.local", 'password' => 'Secret123!']);
        $u->role = $role;
        $u->forceFill($attrs);
        $u->save();

        return $u;
    }

    protected function admin(): User
    {
        return $this->user('admin');
    }

    protected function member(string $name, string $sex = 'male', ?Member $father = null, array $data = []): Member
    {
        return app(FamilyService::class)->create(
            array_merge(['name' => $name, 'sex' => $sex, 'is_alive' => true], $data),
            $father?->id, null, $father ? null : 1, $this->admin(),
        );
    }

    protected function event(array $attrs = []): Event
    {
        $e = new Event(array_merge(['title' => 'Event', 'category' => 'ບຸນປະເພນີ', 'start_date' => now()->addDays(5)->toDateString(), 'is_public' => true], $attrs));
        $e->save();

        return $e;
    }
}
