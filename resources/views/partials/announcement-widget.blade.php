{{-- Announcement Feed Widget for Dashboard --}}
@if(isset($announcements) && $announcements->count() > 0)
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-bullhorn mr-2 text-[#028a0f] dark:text-[#02b815]"></i> Announcements</h3>
        <a href="{{ route('announcements.index') }}" class="badge badge-info no-underline cursor-pointer">View All</a>
    </div>
    <div class="announcement-feed-widget">
        @foreach($announcements as $announcement)
        <div class="announcement-widget-item {{ $announcement->is_pinned ? 'announcement-widget-pinned' : '' }} {{ !$announcement->isReadBy(auth()->user()) ? 'announcement-widget-unread' : '' }}">
            <div class="flex items-start gap-3">
                <div class="announcement-widget-avatar">
                    {{ strtoupper(substr($announcement->author->username ?? 'A', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        @if($announcement->is_pinned)
                        <span class="text-[#028a0f] dark:text-[#02b815] text-xs font-bold"><i class="fas fa-thumbtack"></i></span>
                        @endif
                        <span class="font-semibold text-gray-800 dark:text-gray-200 text-sm truncate">{{ $announcement->title }}</span>
                        @if(!$announcement->isReadBy(auth()->user()))
                        <span class="inline-block w-2 h-2 rounded-full bg-[#028a0f] dark:bg-[#02b815] flex-shrink-0"></span>
                        @endif
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 text-xs mt-1 line-clamp-2 m-0">{{ Str::limit($announcement->body, 120) }}</p>
                    <div class="flex items-center gap-3 mt-2 text-xs text-gray-400 dark:text-gray-500">
                        <span>{{ $announcement->author->employee->full_name ?? $announcement->author->username }}</span>
                        <span>&middot;</span>
                        <span>{{ $announcement->created_at->diffForHumans() }}</span>
                        @if($announcement->reactions->count() > 0)
                        <span>&middot;</span>
                        <span>
                            @foreach($announcement->reactions->groupBy('emoji')->take(3) as $emoji => $group)
                                {{ $emoji }}{{ $group->count() > 1 ? $group->count() : '' }}
                            @endforeach
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
