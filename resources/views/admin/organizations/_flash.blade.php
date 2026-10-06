@if (session('success'))
    <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg p-4">
        <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
    </div>
@endif

@if (session('error'))
    <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <p class="text-sm text-red-800 dark:text-red-200">{{ session('error') }}</p>
    </div>
@endif
