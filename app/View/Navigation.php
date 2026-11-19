<?php

namespace App\View;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/** AppSidebar menu: 6 groups filtered by permission. */
final class Navigation
{
    /** @return array<int, array{label:string, items: array<int, array{label:string, route:string, icon:string, active:string, badge?:int}>}> */
    public static function groups(?User $user): array
    {
        $groups = [
            ['label' => 'ພາບລວມ', 'items' => [
                ['label' => 'ໜ້າຫຼັກ', 'route' => 'dashboard', 'icon' => 'layout-grid', 'active' => 'dashboard'],
            ]],
            ['label' => 'ຕະກູນ', 'items' => [
                ['label' => 'ສະມາຊິກ', 'route' => 'members.index', 'icon' => 'users', 'active' => 'members.*', 'except_tree' => true],
                ['label' => 'ແຜນຜັງຕະກູນ', 'route' => 'members.index', 'params' => ['view' => 'tree'], 'icon' => 'network', 'active' => 'members.*', 'only_tree' => true],
                ['label' => 'ຄະນະນຳ', 'route' => 'leadership.index', 'icon' => 'award', 'active' => 'leadership.*'],
                ['label' => 'ຜູ້ອາວຸໂສ', 'route' => 'elders.index', 'icon' => 'user', 'active' => 'elders.*'],
            ]],
            ['label' => 'ກິດຈະກຳ', 'items' => [
                ['label' => 'ກິດຈະກຳ', 'route' => 'activities.index', 'icon' => 'calendar', 'active' => 'activities.*'],
                ['label' => 'ຂ່າວສານ', 'route' => 'news.index', 'icon' => 'newspaper', 'active' => 'news.*'],
            ]],
        ];

        if ($user?->isMember()) {
            $groups[] = ['label' => 'ຄັງເງິນ', 'items' => [
                ['label' => 'ການເງິນ', 'route' => 'finance.index', 'icon' => 'wallet', 'active' => 'finance.*'],
            ]];
            $groups[] = ['label' => 'ກອງທຶນ', 'items' => [
                ['label' => 'ຜູ້ຮ່ວມກອງທຶນ', 'route' => 'fund.index', 'icon' => 'heart', 'active' => 'fund.*'],
                ['label' => 'ຕິດຕາມການບໍລິຈາກ', 'route' => 'donations.index', 'icon' => 'target', 'active' => 'donations.*'],
            ]];
        }

        if ($user?->isAdmin()) {
            $groups[] = ['label' => 'ລະບົບ', 'items' => [
                ['label' => 'ຈັດການຜູ້ໃຊ້', 'route' => 'admin.index', 'icon' => 'shield', 'active' => 'admin.*', 'badge' => self::pendingCount()],
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
