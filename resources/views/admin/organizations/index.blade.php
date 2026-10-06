<x-app-admin-layout>

    <div class="space-y-4">
        @include('admin.partials._navigation', ['active' => 'organizations'])

        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">@lang('messages.organizations')</h1>
        </div>

        @include('admin.organizations._flash')

        @if ($unassignedSchedules > 0)
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                <p class="text-sm text-amber-800 dark:text-amber-200">{{ __('messages.organization_unassigned_schedules', ['count' => $unassignedSchedules]) }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            {{-- List --}}
            <div class="lg:col-span-2 ap-card rounded-xl shadow p-6 space-y-4">
                <form method="GET" action="{{ route('admin.organizations') }}" class="flex gap-2">
                    <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('messages.organization_search') }}"
                        class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm">
                    <x-secondary-button type="submit">@lang('messages.search')</x-secondary-button>
                </form>

                @forelse ($organizations as $organization)
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 dark:border-gray-700 pt-4">
                        <div class="min-w-0">
                            <a href="{{ route('admin.organizations.show', \App\Utils\UrlUtils::encodeId($organization->id)) }}"
                                class="font-semibold text-gray-900 dark:text-white hover:text-[var(--brand-blue)]">{{ $organization->name }}</a>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $organization->owner?->email }}
                                <span class="mx-1">&bull;</span>{{ __('messages.organization_members_count', ['count' => $organization->users_count]) }}
                                <span class="mx-1">&bull;</span>{{ __('messages.organization_schedules_count', ['count' => $organization->schedules_count]) }}
                            </p>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $organization->isSuspended() ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' }}">
                            {{ $organization->isSuspended() ? __('messages.organization_status_suspended') : __('messages.organization_status_active') }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">@lang('messages.organization_no_results')</p>
                @endforelse

                {{ $organizations->links() }}
            </div>

            {{-- Create --}}
            <div class="ap-card rounded-xl shadow p-6 space-y-4 self-start">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">@lang('messages.organization_new')</h2>
                <form method="POST" action="{{ route('admin.organizations.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">@lang('messages.organization_name')</label>
                        <input type="text" name="name" value="{{ old('name') }}" required maxlength="100"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm">
                        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">@lang('messages.organization_owner_email')</label>
                        <input type="email" name="owner_email" value="{{ old('owner_email') }}" required
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm">
                        @error('owner_email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <x-brand-button type="submit">@lang('messages.organization_create')</x-brand-button>
                </form>
            </div>
        </div>
    </div>

</x-app-admin-layout>
