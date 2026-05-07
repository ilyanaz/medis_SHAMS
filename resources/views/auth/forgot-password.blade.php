@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Forgot your password?
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Enter your email address and we'll send you a link to reset your password.
            </p>
        </div>
        
        <form class="mt-8 space-y-6" method="POST" action="{{ route('password.email') }}">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                <input id="email" name="email" type="email" required value="{{ old('email') }}" class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Email address">
            </div>

            @if ($errors->any())
            <div class="rounded-md bg-red-50 p-4">
                <div class="text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
            @endif

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Send password reset link
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
