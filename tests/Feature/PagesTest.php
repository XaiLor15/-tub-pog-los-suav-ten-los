<?php

namespace Tests\Feature;

use App\Models\Elder;
use App\Models\Event;
use App\Models\Member;
use App\Models\News;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;

/** Every page renders for every role (guards against broken views / missing variables). */
class PagesTest extends AppTestCase
{
    public function test_all_pages_render_for_each_role(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoSeeder::class);
        $this->app['auth']->forgetGuards(); // DemoSeeder acts as the admin while seeding
        $event = Event::where('is_public', true)->firstOrFail();
        $member = Member::firstOrFail();
        $news = News::published()->firstOrFail();
        $elder = Elder::firstOrFail();

        $public = ['/dashboard', '/members', '/members?view=tree', '/members/'.$member->id, '/leadership', '/leadership?view=list', '/elders', '/elders/'.$elder->id,
            '/activities', '/activities?when=past', '/activities/'.$event->id, '/activities/'.$event->id.'?tab=photos', '/news', '/news/'.$news->id, '/login', '/register', '/reset-password'];
        foreach ($public as $url) {
            $this->get($url)->assertOk();
        }

        $memberOnly = ['/finance', '/fund-contributors', '/donation-tracking', '/activities/'.$event->id.'?tab=participants', '/activities/'.$event->id.'?tab=donations',
            '/activities/'.$event->id.'?tab=expenses', '/members?new=1', '/members?edit='.$member->id, '/activities?new=1', '/profile', '/members/export.csv', '/finance/export.csv'];
        $this->actingAs(User::where('role', 'member')->firstOrFail());
        foreach ([...$public, ...$memberOnly] as $url) {
            if (in_array($url, ['/login', '/register', '/reset-password'], true)) {
                continue;
            }
            $this->get($url)->assertOk();
        }

        $this->actingAs(User::where('role', 'admin')->firstOrFail());
        foreach (['/admin', '/admin?tab=audit', '/news?new=1', '/news?drafts=1', '/elders?new=1', '/finance?new=1', '/leadership/export.csv', '/elders/export.csv', '/fund-contributors/export.csv', '/donation-tracking/export.csv?event='.$event->id] as $url) {
            $this->get($url)->assertOk();
        }
        $this->getJson('/notifications')->assertOk()->assertJsonStructure(['items', 'unread']);
        $this->postJson('/notifications/read', ['all' => true])->assertOk();
        $this->assertSame(0, $this->getJson('/notifications')->json('unread'));
        $this->get('/does-not-exist')->assertNotFound();
    }
}
