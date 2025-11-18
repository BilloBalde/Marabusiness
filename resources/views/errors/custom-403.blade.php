@extends('layouts.app')

@section('content')
<div class="flex flex-col items-center justify-center min-h-screen px-4 text-center">
    <h1 class="text-5xl font-bold text-red-600">403 - Access Denied</h1>
    <p class="mt-4 text-gray-600">Sorry, you do not have permission to view this page.</p>
    <a href="{{ url('/') }}" class="px-4 py-2 mt-6 text-white bg-blue-500 rounded hover:bg-blue-600">Back to Home</a>
</div>
@endsection
