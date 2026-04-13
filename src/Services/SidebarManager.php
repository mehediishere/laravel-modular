<?php

namespace Mehediishere\LaravelModular\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class SidebarManager
{
    /**
     * Build the sidebar groups for the currently authenticated user.
     *
     * Flow:
     *   1. Collect raw group definitions from every enabled module's sidebar.php
     *   2. Merge groups that share the same group_id into a single dropdown
     *   3. Filter each group's items by the user's permissions
     *   4. Sort groups by 'order', then items within each group by 'order'
     *   5. Cache the result per user
     */
    public function build(): array
    {
        $user     = Auth::user();
        $cacheKey = 'modular_sidebar_' . ($user?->id ?? 'guest');
        $useCache = config('modular.sidebar.cache', true);

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $groups = $this->collect();
        $groups = $this->mergeByGroupId($groups);
        $groups = $this->filterByPermission($groups, $user);
        $groups = $this->sort($groups);

        if ($useCache) {
            Cache::put($cacheKey, $groups, config('modular.sidebar.cache_ttl', 3600));
        }

        return $groups;
    }

    /**
     * Flush sidebar cache for a specific user (or the currently authed user).
     */
    public function flush(?int $userId = null): void
    {
        Cache::forget('modular_sidebar_' . ($userId ?? Auth::id()));
    }

    /**
     * Flush sidebar cache for all users.
     * Uses cache tags when supported; falls back to current user + guest.
     */
    public function flushAll(): void
    {
        try {
            Cache::tags(['modular_sidebar'])->flush();
        } catch (\BadMethodCallException) {
            $this->flush();
            Cache::forget('modular_sidebar_guest');
        }
    }

    // -------------------------------------------------------------------------
    // Internal pipeline
    // -------------------------------------------------------------------------

    /**
     * Collect raw sidebar definitions from every enabled module.
     * Each entry is one module's full sidebar.php array.
     */
    private function collect(): array
    {
        $raw     = [];
        $enabled = config('modules.enabled', []);

        foreach ($enabled as $module) {
            $providerClass = "Modules\\{$module}\\app\\Providers\\{$module}ServiceProvider";

            if (!class_exists($providerClass)) {
                continue;
            }

            /** @var \Mehediishere\LaravelModular\BaseModuleServiceProvider $sp */
            $sp    = app($providerClass);
            $links = method_exists($sp, 'sidebarLinks') ? $sp->sidebarLinks() : [];

            if (!empty($links)) {
                $raw[] = $links;
            }
        }

        return $raw;
    }

    /**
     * Merge raw group definitions by group_id.
     *
     * Modules that declare the same group_id (e.g. 'finance') are collapsed
     * into a single sidebar group. The first module to declare the group
     * sets the group label, icon, and order — subsequent modules with the
     * same group_id only contribute their items.
     *
     * Example — two modules both using group_id = 'finance':
     *
     *   Account module sidebar.php:
     *     group_id => 'finance', group => 'Finance', order => 20
     *     items    => [Chart of Accounts, Journal Entries]
     *
     *   Payroll module sidebar.php:
     *     group_id => 'finance', group => 'Finance', order => 20
     *     items    => [Payroll Runs, Tax Reports]
     *
     *   Merged result:
     *     group_id => 'finance', group => 'Finance', order => 20
     *     items    => [Chart of Accounts, Journal Entries, Payroll Runs, Tax Reports]
     *
     * @param  array[] $raw  Array of raw sidebar group arrays from each module
     * @return array[]       Deduplicated and merged groups keyed by group_id
     */
    private function mergeByGroupId(array $raw): array
    {
        $merged = [];

        foreach ($raw as $group) {
            $id = $group['group_id'] ?? null;

            if (!$id) {
                // No group_id defined — treat as standalone, use a unique key
                $merged[uniqid('group_', true)] = $group;
                continue;
            }

            if (!isset($merged[$id])) {
                // First module to claim this group_id — register the group shell
                $merged[$id] = [
                    'group_id' => $id,
                    'group'    => $group['group']  ?? $id,
                    'icon'     => $group['icon']   ?? null,
                    'order'    => $group['order']  ?? 99,
                    'items'    => [],
                ];
            }

            // Append this module's items into the shared group
            foreach ($group['items'] ?? [] as $item) {
                $merged[$id]['items'][] = $item;
            }
        }

        // Re-index to a plain array for consistent downstream handling
        return array_values($merged);
    }

    /**
     * Remove items the current user does not have permission to see.
     * Groups that end up with zero visible items are removed entirely.
     */
    private function filterByPermission(array $groups, $user): array
    {
        if (!$user) {
            return [];
        }

        $filtered = [];

        foreach ($groups as $group) {
            $items = array_values(array_filter(
                $group['items'] ?? [],
                fn($item) => empty($item['permission']) || $user->can($item['permission'])
            ));

            if (!empty($items)) {
                $group['items'] = $items;
                $filtered[]     = $group;
            }
        }

        return $filtered;
    }

    /**
     * Sort groups by their 'order' value, then sort items within each group.
     */
    private function sort(array $groups): array
    {
        usort($groups, fn($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));

        return array_map(function (array $group) {
            usort($group['items'], fn($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));
            return $group;
        }, $groups);
    }
}
