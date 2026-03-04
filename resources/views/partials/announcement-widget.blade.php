{{-- Announcement Feed Widget for Dashboard --}}
@if(isset($announcements) && $announcements->count() > 0)
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-bullhorn mr-2 text-[#028a0f] dark:text-[#02b815]"></i> Announcements</h3>
        <a href="{{ route('announcements.index') }}" class="badge badge-info no-underline cursor-pointer">View All</a>
    </div>
    <div class="divide-y divide-gray-100 dark:divide-gray-700">
        @foreach($announcements as $announcement)
        <div class="flex items-start gap-3 py-3 px-1
             {{ $announcement->is_pinned ? 'border-l-2 border-[#028a0f] dark:border-[#02b815] pl-3' : '' }}
             {{ !$announcement->isReadBy(auth()->user()) ? 'bg-blue-50 dark:bg-blue-900/10' : '' }}">

            {{-- Avatar --}}
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                 style="background:linear-gradient(135deg,#028a0f,#026a0c);">
                {{ strtoupper(substr($announcement->author->username ?? 'A', 0, 1)) }}
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    @if($announcement->is_pinned)
                    <i class="fas fa-thumbtack text-[#028a0f] dark:text-[#02b815] text-xs"></i>
                    @endif
                    <span class="font-semibold text-gray-800 dark:text-gray-200 text-sm truncate">{{ $announcement->title }}</span>
                    @if(!$announcement->isReadBy(auth()->user()))
                    <span class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0"></span>
                    @endif
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-xs mt-0.5 line-clamp-2 m-0">{{ Str::limit($announcement->body, 100) }}</p>
                <div class="flex items-center gap-2 mt-1.5 text-xs text-gray-400 dark:text-gray-500 flex-wrap">
                    <span>{{ $announcement->author->employee->full_name ?? $announcement->author->username }}</span>
                    <span>&middot;</span>
                    <span>{{ $announcement->created_at->diffForHumans() }}</span>

                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
