@if (session()->has('impersonator_user_id'))
<div class="bg-gradient-to-r from-purple-800 via-indigo-800 to-blue-800 text-white px-4 py-2.5 text-sm font-medium shadow-md sticky top-0 z-50 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-2.5">
        <span class="relative flex h-3 w-3">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
        </span>
        <span>
            <strong>SaaS Owner Mode:</strong> You are impersonating tenant 
            <span class="underline font-bold text-amber-200">{{ session('impersonated_role_subdomain', 'tenant') }}</span>
        </span>
    </div>
    <form method="POST" action="{{ route('admin.impersonate.stop') }}" class="inline">
        @csrf
        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/20 hover:bg-white/30 text-white text-xs font-semibold rounded-md backdrop-blur-sm transition-all border border-white/20">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Return to SaaS Owner Console
        </button>
    </form>
</div>
@endif
