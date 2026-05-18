@extends('layouts.dashboard')

@section('title', 'Recycle Bin - Program Coordinator')

@section('page-title', 'Recycle Bin')
@section('page-subtitle', 'Restore files you moved here from Documents')

@section('sidebar')
    @include('partials.coordinator-sidebar')
@endsection

@section('content')
    @include('partials.recycle-bin-table', ['routePrefix' => 'coordinator', 'canForceDelete' => false])
@endsection
