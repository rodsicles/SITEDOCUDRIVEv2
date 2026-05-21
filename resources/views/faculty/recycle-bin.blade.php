@extends('layouts.dashboard')

@section('title', 'Recycle Bin - Faculty')

@section('page-title', 'Recycle Bin')
@section('page-subtitle', 'Restore files you moved here from Documents')

@section('sidebar')
    @include('partials.faculty-sidebar')
@endsection

@section('content')
    @include('partials.recycle-bin-table', ['routePrefix' => 'faculty', 'canForceDelete' => false])
@endsection
