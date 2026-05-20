@extends('layouts.dashboard')

@section('title', 'Notifications - Dean')

@section('page-title', 'Notifications')
@section('page-subtitle', 'Upload alerts and approval requests')

@section('sidebar')
    @if(auth()->user()->isSecretary())
        @include('partials.secretary-sidebar')
    @else
        @include('partials.dean-sidebar')
    @endif
@endsection

@section('content')
    @include('partials.notifications-list', ['routePrefix' => 'dean'])
@endsection
