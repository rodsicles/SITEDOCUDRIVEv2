@extends('layouts.dashboard')

@section('title', 'Recycle Bin - Dean')

@section('page-title', 'Recycle Bin')
@section('page-subtitle', 'All deleted files — restore or permanently remove')

@section('sidebar')
    @include('partials.dean-sidebar')
@endsection

@section('content')
    @include('partials.recycle-bin-table', ['routePrefix' => 'dean', 'canForceDelete' => $canForceDelete ?? false])
@endsection
