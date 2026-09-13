@extends('layouts.dashboard')

@section('title', 'Documents - Dean')

@section('page-title', 'Documents')
@section('page-subtitle', 'Browse folders and manage files')

@section('sidebar')
    @include('partials.dean-sidebar')
@endsection

@section('content')
<div class="documents-page" data-page="documents">
    @include('partials.folder-tree')
</div>
@endsection
