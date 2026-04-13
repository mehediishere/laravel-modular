{{--
    Admin layout — provided by mehediishere/laravel-modular.
    Publish with: php artisan vendor:publish --tag=modular-views
    Then customise at: resources/views/vendor/modular/layouts/admin.blade.php

    The $sidebarGroups array structure:
    [
        [
            'group_id' => 'finance',         // unique group identifier
            'group'    => 'Finance',          // dropdown label
            'icon'     => 'bar-chart',        // group icon key
            'order'    => 20,                 // sidebar position
            'items'    => [                   // merged from all modules sharing this group_id
                [
                    'label'      => 'Chart of Accounts',
                    'route'      => 'account.coa.index',
                    'icon'       => 'list',
                    'order'      => 1,
                    'permission' => 'account.coa.view',
                ],
                ...
            ],
        ],
        ...
    ]
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name')) — Admin</title>
    @stack('styles')
</head>
<body>

<div class="admin-wrapper">

    {{-- ------------------------------------------------------------------ --}}
    {{-- Sidebar                                                             --}}
    {{-- ------------------------------------------------------------------ --}}
    <aside class="admin-sidebar">

        <div class="sidebar-brand">
            @yield('brand', config('app.name'))
        </div>

        <nav class="sidebar-nav">
            @php
                $sidebarGroups = app(\Mehediishere\LaravelModular\Services\SidebarManager::class)->build();
            @endphp

            @foreach($sidebarGroups as $group)
                @if(!empty($group['items']))

                    {{--
                        Each $group is one dropdown in the sidebar.
                        Multiple modules can contribute items to the same group
                        via a shared group_id in their config/sidebar.php.

                        $group['group_id'] — use this as the HTML id/data attribute
                                             for JS-driven open/close state.
                        $group['group']    — the visible dropdown label.
                        $group['icon']     — icon for the group header.
                        $group['items']    — the merged, permission-filtered,
                                             sorted navigation links.
                    --}}

                    <div class="sidebar-group" data-group="{{ $group['group_id'] }}">

                        {{-- Group header / dropdown toggle --}}
                        <button class="sidebar-group-toggle"
                                aria-expanded="false"
                                aria-controls="sidebar-group-{{ $group['group_id'] }}">

                            @if(!empty($group['icon']))
                                <span class="sidebar-icon sidebar-icon--{{ $group['icon'] }}"></span>
                            @endif

                            <span class="sidebar-group-label">{{ $group['group'] }}</span>
                            <span class="sidebar-chevron"></span>
                        </button>

                        {{-- Group items --}}
                        <div class="sidebar-group-items"
                             id="sidebar-group-{{ $group['group_id'] }}">

                            @foreach($group['items'] as $item)
                                @php
                                    $isActive = request()->routeIs($item['route'])
                                             || request()->routeIs($item['route'] . '.*');
                                @endphp

                                <a href="{{ route($item['route']) }}"
                                   class="sidebar-item {{ $isActive ? 'sidebar-item--active' : '' }}"
                                   data-permission="{{ $item['permission'] ?? '' }}">

                                    @if(!empty($item['icon']))
                                        <span class="sidebar-icon sidebar-icon--{{ $item['icon'] }}"></span>
                                    @endif

                                    {{ $item['label'] }}
                                </a>
                            @endforeach

                        </div>
                    </div>

                @endif
            @endforeach
        </nav>

    </aside>

    {{-- ------------------------------------------------------------------ --}}
    {{-- Main content                                                        --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="admin-main">

        <header class="admin-header">
            @yield('header')
        </header>

        <main class="admin-content">

            @if(session('success'))
                <div class="alert alert--success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert--error">{{ session('error') }}</div>
            @endif

            @yield('content')

        </main>

    </div>
</div>

@stack('scripts')
</body>
</html>
