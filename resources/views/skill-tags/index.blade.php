@extends('layouts.dashboard')

@section('title', 'Skill Tags')
@section('page-title', 'Skill Tags')
@section('page-subtitle', 'Manage skill tags and view faculty skills')

@section('sidebar')
    @if(auth()->user()->isFaculty())
        @include('partials.faculty-sidebar')
    @elseif(auth()->user()->isProgramCoordinator())
        @include('partials.coordinator-sidebar')
    @else
        @include('partials.dean-sidebar')
    @endif
@endsection

@section('content')
    @if(auth()->user()->isFaculty())
        {{-- Faculty: Manage Own Tags --}}
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tags mr-2"></i>My Skill Tags</h3>
                <span class="badge badge-info">{{ $tags->count() }} Tags</span>
            </div>

            <form action="{{ route('skill-tags.store') }}" method="POST" class="mb-5">
                @csrf
                <div class="flex gap-3 items-end">
                    <div class="form-group mb-0 flex-1">
                        <label class="form-label">Add a Skill Tag</label>
                        <input type="text" name="tag_name" class="form-control" placeholder="e.g. PHP, Networking, UI/UX" required maxlength="50">
                    </div>
                    <button type="submit" class="btn btn-primary border-0">
                        <i class="fas fa-plus"></i> Add Tag
                    </button>
                </div>
                @error('tag_name')
                    <p class="text-red-600 dark:text-red-400 text-sm mt-2">{{ $message }}</p>
                @enderror
            </form>

            @if($tags->count() > 0)
                <div class="py-2">
                    @foreach($tags as $tag)
                        <span class="skill-tag">
                            {{ $tag->tag_name }}
                            <form action="{{ route('skill-tags.destroy', $tag->skill_tag_id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="skill-tag-remove" title="Remove tag">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </span>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                    <i class="fas fa-tags text-5xl mb-4 opacity-50"></i>
                    <p>No skill tags added yet. Add your first tag above.</p>
                </div>
            @endif
        </div>
    @else
        {{-- Dean/Coordinator: Faculty Skills Summary --}}
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tags mr-2"></i>Faculty Skills Summary</h3>
                <span class="badge badge-info">{{ isset($summary) ? $summary->count() : 0 }} Unique Skills</span>
            </div>

            @if(isset($summary) && $summary->count() > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Skill Tag</th>
                            <th>Faculty Count</th>
                            <th>Faculty Members</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summary as $tag)
                        <tr>
                            <td><span class="skill-tag">{{ $tag->tag_name }}</span></td>
                            <td><strong>{{ $tag->faculty_count }}</strong></td>
                            <td>{{ $tag->faculty_members }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                    <i class="fas fa-tags text-5xl mb-4 opacity-50"></i>
                    <p>No faculty skill tags have been added yet.</p>
                </div>
            @endif
        </div>
    @endif
@endsection
