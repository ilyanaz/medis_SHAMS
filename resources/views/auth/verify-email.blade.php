@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Verify your email address
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Thanks for signing up! Before getting started, could you verify your email address by clicking the link we just emailed to you?
            </p>
        </div>
        
        @if (session('resent'))
        <div class="rounded-md bg-green-50 p-4">
            <div class="text-sm text-green-700">
                A fresh verification link has been sent to your email address.
            </div>
        </div>
        @endif

        <form class="mt-8 space-y-6" method="POST" action="{{ route('verification.send') }}">
            @csrf
            
            <div>
                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Resend verification email
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
