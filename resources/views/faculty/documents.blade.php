@extends('layouts.dashboard')

@section('title', 'Documents - Faculty')

@section('page-title', 'Documents')
@section('page-subtitle', 'Open assigned folders and submit files')

@section('sidebar')
    @include('partials.faculty-sidebar')
@endsection

@section('content')
<div class="documents-page" data-page="documents">
    @include('partials.folder-tree')
</div>
@endsection
