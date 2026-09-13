@extends('errors.layout')

@section('title', 'Maintenance')

@section('content')
    <h1 class="text-7xl font-extrabold text-amber-500">503</h1>
    <p class="mt-4 text-xl">{{ __('The service is temporarily unavailable.') }}</p>
@endsection
