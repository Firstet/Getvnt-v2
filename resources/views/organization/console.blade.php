<x-app-admin-layout>

    <div class="max-w-5xl mx-auto space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $organization->name }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@lang('messages.organization_console')</p>
            </div>

            @if ($myOrganizations->count() > 1)
                <div class="flex flex-wrap gap-2">
                    @foreach ($myOrganizations->where('id', '!=', $organization->id) as $other)
                        <form method="POST" action="{{ route('organization.switch', \App\Utils\UrlUtils::encodeId($other->id)) }}">
                            @csrf
                            <x-secondary-button type="submit">{{ $other->name }}</x-secondary-button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>

        @include('admin.organizations._flash')

        @unless ($canManage)
            <p class="text-sm text-gray-500 dark:text-gray-400">@lang('messages.organization_read_only')</p>
        @endunless

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Schedules --}}
            <div class="ap-card rounded-xl shadow p-6 space-y-3">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">@lang('messages.organization_schedules')</h2>
                @forelse ($schedules as $schedule)
                    <div class="flex items-center justify-between text-sm border-t border-gray-100 dark:border-gray-700 pt-3">
                        <a href="{{ route('role.view_admin', ['subdomain' => $schedule->subdomain, 'tab' => 'schedule']) }}"
                            class="font-medium text-gray-900 dark:text-white hover:text-[var(--brand-blue)]">{{ $schedule->name }}</a>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $schedule->subdomain }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">@lang('messages.organization_no_schedules')</p>
                @endforelse
            </div>

            {{-- Team --}}
            <div class="ap-card rounded-xl shadow p-6 space-y-3">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">@lang('messages.organization_team')</h2>

                @foreach ($members as $member)
                    @php
                        $isOwner = $member->pivot->level === \App\Models\Organization::LEVEL_OWNER;
                        $memberHash = \App\Utils\UrlUtils::encodeId($member->id);
                    @endphp
                    <div class="flex flex-wrap items-center justify-between gap-2 text-sm border-t border-gray-100 dark:border-gray-700 pt-3">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $member->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $member->email }}</p>
                        </div>

                        @if ($canManage && ! $isOwner)
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('organization.members.update', $memberHash) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <select name="level" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-xs py-1">
                                        <option value="admin" @selected($member->pivot->level === 'admin')>@lang('messages.organization_level_admin')</option>
                                        <option value="member" @selected($member->pivot->level === 'member')>@lang('messages.organization_level_member')</option>
                                    </select>
                                    <x-secondary-button type="submit">@lang('messages.save')</x-secondary-button>
                                </form>
                                <form method="POST" action="{{ route('organization.members.remove', $memberHash) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-danger-button type="submit">@lang('messages.organization_remove_member')</x-danger-button>
                                </form>
                            </div>
                        @else
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ __('messages.organization_level_'.$member->pivot->level) }}</span>
                        @endif
                    </div>
                @endforeach

                @if ($canManage)
                    <form method="POST" action="{{ route('organization.members.add') }}" class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-2">
                        @csrf
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">@lang('messages.organization_add_member')</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">@lang('messages.organization_add_member_help')</p>
                        <div class="flex flex-wrap gap-2">
                            <input type="email" name="email" value="{{ old('email') }}" required
                                class="flex-1 min-w-[12rem] rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm">
                            <select name="level" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm">
                                <option value="member">@lang('messages.organization_level_member')</option>
                                <option value="admin">@lang('messages.organization_level_admin')</option>
                            </select>
                            <x-brand-button type="submit">@lang('messages.add')</x-brand-button>
                        </div>
                        @error('email')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    </form>
                @endif
            </div>
        </div>

        @if ($canManage)
            <div class="ap-card rounded-xl shadow p-6">
                <form method="POST" action="{{ route('organization.update') }}" class="flex flex-wrap items-end gap-2">
                    @csrf
                    @method('PUT')
                    <div class="flex-1 min-w-[12rem]">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">@lang('messages.organization_name')</label>
                        <input type="text" name="name" value="{{ old('name', $organization->name) }}" required maxlength="100"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm">
                    </div>
                    <x-brand-button type="submit">@lang('messages.save')</x-brand-button>
                </form>
            </div>
        @endif
    </div>

</x-app-admin-layout>
