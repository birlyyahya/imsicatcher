<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <x-toaster-hub />
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>
                <flux:navbar.item icon="signal" :href="route('network-traffic')" :current="request()->routeIs('network-traffic')" wire:navigate>
                    {{ __('Network Traffic') }}
                </flux:navbar.item>
                <flux:navbar.item icon="folder" :href="route('mission-issues')" :current="request()->routeIs('mission-issues*')" wire:navigate>
                    {{ __('Incidents') }}
                </flux:navbar.item>
                <flux:navbar.item icon="cpu-chip" :href="route('operasi-alat')" :current="request()->routeIs('operasi-alat*')" wire:navigate>
                    {{ __('Log Operasi Alat') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
                <flux:tooltip :content="__('Log')" position="bottom">
                    <flux:navbar.item class="!h-10 [&>div>svg]:size-5" icon="inbox-arrow-down" href="{{ route('logs') }}" :label="__('Search')" />
                </flux:tooltip>
                @unless (auth()->user()->isOperator())
                    <flux:tooltip :content="__('Users')" position="bottom">
                        <flux:navbar.item
                            class="h-10 max-lg:hidden [&>div>svg]:size-5"
                            icon="users"
                            href="{{ route('users') }}"
                            :label="__('Repository')"
                        />
                    </flux:tooltip>
                @endunless
                @if (auth()->user()->isSuperadmin())
                    <flux:tooltip :content="__('Satker')" position="bottom">
                        <flux:navbar.item
                            class="h-10 max-lg:hidden [&>div>svg]:size-5"
                            icon="building-office-2"
                            href="{{ route('satkers') }}"
                            :label="__('Satker')"
                        />
                    </flux:tooltip>
                @endif
                <flux:tooltip :content="__('Inventaris Aset')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="archive-box"
                        href="{{ route('aset') }}"
                        :label="__('Inventaris Aset')"
                    />
                </flux:tooltip>
                <flux:tooltip :content="__('Documentation')" position="bottom">
                    <flux:navbar.item
                        href="{{ route('documentation') }}"
                        icon="book-open-text"
                        :current="request()->routeIs('documentation*')"
                        :label="__('Documentation')"
                    />
                </flux:tooltip>
            </flux:navbar>

            <x-desktop-user-menu />
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')">
                    <flux:sidebar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard')  }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="plus" :href="route('network-traffic')" :current="request()->routeIs('network-traffic')" wire:navigate>
                        {{ __('Network Traffic') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="plus" :href="route('mission-issues')" :current="request()->routeIs('mission-issues*')" wire:navigate>
                        {{ __('Incident') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="cpu-chip" :href="route('operasi-alat')" :current="request()->routeIs('operasi-alat*')" wire:navigate>
                        {{ __('Log Operasi Alat') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="plus" :href="route('logs')" :current="request()->routeIs('logs')" wire:navigate>
                        {{ __('Log') }}
                    </flux:sidebar.item>
                    @unless (auth()->user()->isOperator())
                        <flux:sidebar.item icon="users" :href="route('users')" :current="request()->routeIs('users')" wire:navigate>
                            {{ __('Management User') }}
                        </flux:sidebar.item>
                    @endunless
                    @if (auth()->user()->isSuperadmin())
                        <flux:sidebar.item icon="building-office-2" :href="route('satkers')" :current="request()->routeIs('satkers')" wire:navigate>
                            {{ __('Manajemen Satker') }}
                        </flux:sidebar.item>
                    @endif
                    <flux:sidebar.item icon="archive-box" :href="route('aset')" :current="request()->routeIs('aset')" wire:navigate>
                        {{ __('Inventaris Aset') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="book-open-text" :href="route('documentation')" :current="request()->routeIs('documentation*')" wire:navigate>
                        {{ __('Dokumentasi') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>
        </flux:sidebar>
        {{ $slot }}

        @fluxScripts
    </body>
</html>
