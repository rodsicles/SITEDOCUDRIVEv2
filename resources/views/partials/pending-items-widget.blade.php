{{-- My Pending Items widget --}}
@php
    $items = $pendingItems ?? ['tasks' => 0, 'overdue' => 0, 'unread' => 0, 'leaves' => 0];
@endphp
<div class="content-card mb-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-inbox mr-2"></i> My Pending Items</h3>
        <span class="text-xs text-gray-500 dark:text-gray-400">Quick snapshot of what needs your attention</span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        {{-- Pending Tasks --}}
        <a href="{{ route('faculty.tasks', ['filter' => 'pending']) }}"
           class="flex items-center gap-3 p-4 border border-gray-200 dark:border-gray-700 bg-white dark:bg-[#1e1e1e] no-underline">
            <div class="w-10 h-10 flex items-center justify-center bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-300">
                <i class="fas fa-tasks text-lg"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-100 leading-none">{{ $items['tasks'] }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Pending Tasks</div>
            </div>
        </a>

        {{-- Overdue Tasks --}}
        <a href="{{ route('faculty.tasks', ['filter' => 'overdue']) }}"
           class="flex items-center gap-3 p-4 border {{ $items['overdue'] > 0 ? 'border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-[#1e1e1e]' }} no-underline">
            <div class="w-10 h-10 flex items-center justify-center {{ $items['overdue'] > 0 ? 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-500' }}">
                <i class="fas fa-triangle-exclamation text-lg"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-2xl font-bold {{ $items['overdue'] > 0 ? 'text-red-700 dark:text-red-300' : 'text-gray-800 dark:text-gray-100' }} leading-none">{{ $items['overdue'] }}</div>
                <div class="text-xs {{ $items['overdue'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400' }} mt-1">Overdue</div>
            </div>
        </a>

        {{-- Unread Notifications --}}
        <a href="{{ route('faculty.notifications') }}"
           class="flex items-center gap-3 p-4 border border-gray-200 dark:border-gray-700 bg-white dark:bg-[#1e1e1e] no-underline">
            <div class="w-10 h-10 flex items-center justify-center bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300">
                <i class="fas fa-bell text-lg"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-100 leading-none">{{ $items['unread'] }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Unread Alerts</div>
            </div>
        </a>

        {{-- Pending Leaves --}}
        @php $leavesRoute = \Illuminate\Support\Facades\Route::has('faculty.leaves') ? route('faculty.leaves') : '#'; @endphp
        <a href="{{ $leavesRoute }}"
           class="flex items-center gap-3 p-4 border border-gray-200 dark:border-gray-700 bg-white dark:bg-[#1e1e1e] no-underline">
            <div class="w-10 h-10 flex items-center justify-center bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-300">
                <i class="fas fa-calendar-minus text-lg"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-100 leading-none">{{ $items['leaves'] }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">Pending Leaves</div>
            </div>
        </a>
    </div>
</div>
