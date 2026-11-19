<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\News;
use App\Models\NewsComment;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

/**
 * Permission matrix (Dev Handoff "ສິດຕາມ Role"). Guest = not signed in, Pending = signed in
 * but not yet approved. Every controller action checks these gates; hiding buttons in Blade
 * is only UX (the Laravel equivalent of the spec's RLS policies).
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::defaultView('components.pagination');
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        $member = fn (?User $u) => (bool) $u?->isMember();
        $admin = fn (?User $u) => (bool) $u?->isAdmin();

        // Phone numbers and full birth dates: members & admins only (guests/pending see the year).
        Gate::define('view-private', $member);

        Gate::define('members.write', $member);
        Gate::define('members.delete', $admin);
        Gate::define('leadership.manage', $admin);
        Gate::define('elders.manage', $admin);

        Gate::define('events.view', fn (?User $u, Event $e) => $e->is_public || (bool) $u?->isMember());
        Gate::define('events.view-all-tabs', $member);
        Gate::define('events.create', $member);
        Gate::define('events.update', fn (?User $u, Event $e) => (bool) $u?->isAdmin() || ($u?->isMember() && $e->created_by === $u->id));
        Gate::define('events.delete', $admin);
        Gate::define('events.join', $member);

        Gate::define('news.view', fn (?User $u, News $n) => $n->status === 'published' || (bool) $u?->isAdmin());
        Gate::define('news.manage', $admin);
        Gate::define('comments.create', $member);
        Gate::define('comments.update', fn (?User $u, NewsComment $c) => (bool) $u?->isMember() && $c->author_id === $u->id);
        Gate::define('comments.delete', fn (?User $u, NewsComment $c) => (bool) $u?->isAdmin() || ($u?->isMember() && $c->author_id === $u->id));

        Gate::define('finance.view', $member);
        Gate::define('finance.manage', $admin);
        Gate::define('donations.view', $member);
        Gate::define('donations.manage', $admin);
        Gate::define('fund.view', $member);
        Gate::define('fund.manage', $admin);

        Gate::define('admin', $admin);
    }
}
