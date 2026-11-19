<?php

namespace App\View;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/** AppSidebar menu: 6 groups filtered by permission, plus the shared icon vocabulary. */
final class Navigation
{
    /**
     * Icon vocabulary (Lucide). One meaning per icon across the whole app.
     *
     * @var array<string, string>
     */
    public const ICONS = [
        'dashboard' => 'layout-dashboard',
        'members' => 'users',
        'tree' => 'network',
        'leadership' => 'award',
        'elders' => 'book-user',
        'activities' => 'calendar-days',
        'news' => 'newspaper',
        'finance' => 'wallet',
        'fund' => 'heart-handshake',
        'donations' => 'hand-coins',
        'admin' => 'shield-check',
        'profile' => 'user-round',
    ];

    /** Icons JavaScript renders through the sprite (<svg><use href="#i-name"/></svg>). */
    public const SPRITE = [
        'x', 'check', 'chevron-left', 'chevron-right', 'arrow-up', 'arrow-down', 'arrow-right', 'loader-circle',
        'wifi-off', 'search-x', 'inbox', 'calendar-days', 'megaphone', 'user-round-plus', 'command', 'corner-down-left',
        'refresh-cw', 'users', 'circle-check', 'circle-alert', 'search', 'bell', 'check-check',
    ];

    /** Mobile bottom navigation: [route, icon, label, active pattern]. */
    public const BOTTOM = [
        ['dashboard', 'layout-dashboard', 'ໜ້າຫຼັກ', 'dashboard'],
        ['members.index', 'users', 'ສະມາຊິກ', 'members.*'],
        ['activities.index', 'calendar-days', 'ກິດຈະກຳ', 'activities.*'],
        ['news.index', 'newspaper', 'ຂ່າວ', 'news.*'],
    ];

    /** @return array<int, array{label:string, items: array<int, array{label:string, route:string, icon:string, active:string, badge?:int}>}> */
    public static function groups(?User $user): array
    {
        $groups = [
            ['label' => 'ພາບລວມ', 'items' => [
                ['label' => 'ໜ້າຫຼັກ', 'route' => 'dashboard', 'icon' => self::ICONS['dashboard'], 'active' => 'dashboard'],
            ]],
            ['label' => 'ຕະກູນ', 'items' => [
                ['label' => 'ສະມາຊິກ', 'route' => 'members.index', 'icon' => self::ICONS['members'], 'active' => 'members.*', 'except_tree' => true],
                ['label' => 'ແຜນຜັງຕະກູນ', 'route' => 'members.index', 'params' => ['view' => 'tree'], 'icon' => self::ICONS['tree'], 'active' => 'members.*', 'only_tree' => true],
                ['label' => 'ຄະນະນຳ', 'route' => 'leadership.index', 'icon' => self::ICONS['leadership'], 'active' => 'leadership.*'],
                ['label' => 'ຜູ້ອາວຸໂສ', 'route' => 'elders.index', 'icon' => self::ICONS['elders'], 'active' => 'elders.*'],
            ]],
            ['label' => 'ກິດຈະກຳ', 'items' => [
                ['label' => 'ກິດຈະກຳ', 'route' => 'activities.index', 'icon' => self::ICONS['activities'], 'active' => 'activities.*'],
                ['label' => 'ຂ່າວສານ', 'route' => 'news.index', 'icon' => self::ICONS['news'], 'active' => 'news.*'],
            ]],
        ];

        if ($user?->isMember()) {
            $groups[] = ['label' => 'ຄັງເງິນ', 'items' => [
                ['label' => 'ການເງິນ', 'route' => 'finance.index', 'icon' => self::ICONS['finance'], 'active' => 'finance.*'],
            ]];
            $groups[] = ['label' => 'ກອງທຶນ', 'items' => [
                ['label' => 'ຜູ້ຮ່ວມກອງທຶນ', 'route' => 'fund.index', 'icon' => self::ICONS['fund'], 'active' => 'fund.*'],
                ['label' => 'ຕິດຕາມການບໍລິຈາກ', 'route' => 'donations.index', 'icon' => self::ICONS['donations'], 'active' => 'donations.*'],
            ]];
        }

        if ($user?->isAdmin()) {
            $groups[] = ['label' => 'ລະບົບ', 'items' => [
                ['label' => 'ຈັດການຜູ້ໃຊ້', 'route' => 'admin.index', 'icon' => self::ICONS['admin'], 'active' => 'admin.*', 'badge' => self::pendingCount()],
            ]];
        }

        return $groups;
    }

    public static function pendingCount(): int
    {
        return Cache::remember('nav.pending-users', 30, fn () => User::where('role', 'pending')->whereNull('rejected_at')->where('is_disabled', false)->count());
    }

    public static function isActive(array $item): bool
    {
        if (! request()->routeIs($item['active'])) {
            return false;
        }

        $isTree = request()->query('view') === 'tree';
        if ($item['except_tree'] ?? false) {
            return ! $isTree;
        }

        return ! ($item['only_tree'] ?? false) || $isTree;
    }
}
