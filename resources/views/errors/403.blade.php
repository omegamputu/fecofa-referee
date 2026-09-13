@extends('errors.layout')

@section('title', 'Erreur 403')

@section('content')
    <h1 class="text-7xl font-extrabold text-red-500">403</h1>
    <p class="mt-4 text-xl">{{ __('You are not authorized to access this page.') }}</p>

    <a href="{{ route('dashboard') }}" class="mt-6 rounded-xl bg-blue-600 px-6 py-3 shadow hover:bg-blue-700">
        {{ __('Back to dashboard') }}
    </a>
@endsection
