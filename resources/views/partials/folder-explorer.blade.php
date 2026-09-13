@if(!empty($explorerLevels))
<nav class="folder-explorer" aria-label="Folders in this location">
    @foreach($explorerLevels as $level)
        <section class="folder-level {{ empty($level['heading']) ? 'folder-level--chips' : '' }}">
            @if(!empty($level['heading']))
                <h3 class="folder-level__heading">{{ $level['heading'] }}</h3>
            @endif
            <div class="folder-level__items">
                @forelse($level['folders'] as $folder)
                    @php
                        $isSelected = (int) ($level['selected_id'] ?? 0) === (int) $folder->folder_id;
                        $folderCount = (int) ($folder->documents_count ?? 0);
                    @endphp
                    <a href="{{ route($docsRoute, ['tab' => $tab, 'folder' => $folder->folder_id]) }}"
                       class="folder-chip {{ $isSelected ? 'is-selected' : '' }}"
                       @if($isSelected) aria-current="page" @endif>
                        <i class="fas fa-folder" aria-hidden="true"></i>
                        <span>{{ $folder->folder_name }}</span>
                        @if($folderCount > 0)
                            <span class="folder-chip__count">{{ $folderCount }}</span>
                        @endif
                    </a>
                @empty
                    <p class="ui-empty__text">No folders at this level.</p>
                @endforelse
            </div>
        </section>
    @endforeach
</nav>
@endif
