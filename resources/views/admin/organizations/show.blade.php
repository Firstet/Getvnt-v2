<x-app-admin-layout>

    @php $hash = \App\Utils\UrlUtils::encodeId($organization->id); @endphp

    <div class="space-y-4">
        @include('admin.partials._navigation', ['active' => 'organizations'])

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $organization->name }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $organization->slug }} &bull; {{ $organization->owner?->email }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-secondary-link :href="route('admin.organizations')">@lang('messages.organization_back_to_list')</x-secondary-link>
                <form method="POST" action="{{ route('admin.organizations.switch', $hash) }}">
                    @csrf
                    <x-brand-button type="submit">@lang('messages.organization_enter')</x-brand-button>
                </form>
            </div>
        </div>

        @include('admin.organizations._flash')

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Settings and status --}}
            <div class="ap-card rounded-xl shadow p-6 space-y-4">
                <form method="POST" action="{{ route('admin.organizations.update', $hash) }}" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">@lang('messages.organization_name')</label>
                    <input type="text" name="name" value="{{ old('name', $organization->name) }}" required maxlength="100"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm">
                    @error('name')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <x-brand-button type="submit">@lang('messages.save')</x-brand-button>
                </form>

                <div class="border-t border-gray-100 dark:border-gray-700 pt-4 flex items-center justify-between">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $organization->isSuspended() ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' }}">
                        {{ $organization->isSuspended() ? __('messages.organization_status_suspended') : __('messages.organization_status_active') }}
                    </span>
                    @if ($organization->isSuspended())
                        <form method="POST" action="{{ route('admin.organizations.resume', $hash) }}">
                            @csrf
                            <x-secondary-button type="submit">@lang('messages.organization_resume')</x-secondary-button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.organizations.suspend', $hash) }}">
                            @csrf
                            <x-danger-button type="submit">@lang('messages.organization_suspend')</x-danger-button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Team --}}
            <div class="ap-card rounded-xl shadow p-6 space-y-3">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">@lang('messages.organization_team')</h2>
                @foreach ($members as $member)
                    <div class="flex items-center justify-between text-sm border-t border-gray-100 dark:border-gray-700 pt-3">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $member->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $member->email }}</p>
                        </div>
                        <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ __('messages.organization_level_'.$member->pivot->level) }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Schedules --}}
            <div class="ap-card rounded-xl shadow p-6 space-y-3 lg:col-span-2">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">@lang('messages.organization_schedules')</h2>
                @forelse ($schedules as $schedule)
                    <div class="flex items-center justify-between text-sm border-t border-gray-100 dark:border-gray-700 pt-3">
                        <span class="font-medium text-gray-900 dark:text-white">{{ $schedule->name }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $schedule->subdomain }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">@lang('messages.organization_no_schedules')</p>
                @endforelse

                <form method="POST" action="{{ route('admin.organizations.assign_schedule', $hash) }}" class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-2">
                    @csrf
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">@lang('messages.organization_assign_schedule')</label>
                    <p class="text-xs text-gray-500 dark:text-gray-400">@lang('messages.organization_assign_schedule_help')</p>
                    <div class="flex gap-2">
                        <input type="text" name="subdomain" value="{{ old('subdomain') }}" required
                            class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm">
                        <x-brand-button type="submit">@lang('messages.save')</x-brand-button>
                    </div>
                    @error('subdomain')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </form>
            </div>
        </div>
    </div>

</x-app-admin-layout>
