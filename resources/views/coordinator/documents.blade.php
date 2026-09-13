@extends('layouts.dashboard')

@section('title', 'Documents - Coordinator')

@section('page-title', 'Documents')
@section('page-subtitle', 'Browse departmental folders and files')

@section('sidebar')
    @include('partials.coordinator-sidebar')
@endsection

@section('content')
<div class="documents-page" data-page="documents">
    @include('partials.folder-tree')
</div>
@endsection
