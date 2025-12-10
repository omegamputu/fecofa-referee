@extends('errors.layout')

@section('title', 'Erreur 404')

@section('content')
    <h1 class="text-7xl font-extrabold text-red-500">404</h1>
    <p class="text-xl mt-4">{{ __("The page you are looking for cannot be found.") }}</p>

    <a href="{{ route('dashboard') }}" class="mt-6 px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-xl shadow">
        {{ __("Back to dashboard") }}
    </a>
@endsection