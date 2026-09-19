<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo">
            <a href="/">
                <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
            </a>
        </x-slot>

        @if (session()->has('reset_link_shown'))
            <!-- Reset Link Display (Local Development) -->
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="text-sm text-green-700 font-semibold mb-2">{{ __('Password Reset Link (Local Development)') }}</div>
                <p class="text-sm text-gray-700 mb-3">{{ __('Click the button below to reset your password:') }}</p>
                <a href="{{ session('reset_url') }}" 
                   target="_blank"
                   class="inline-block px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition">
                    {{ __('Reset Password') }}
                </a>
                <p class="text-xs text-gray-600 mt-3 break-all">{{ __('Link:') }} {{ session('reset_url') }}</p>
            </div>
        @else
            <div class="mb-4 text-sm text-gray-600">
                @if (app()->environment('local'))
                    {{ __('Forgot your password? No problem. Just let us know your email address and we will show you a password reset link in a new tab.') }}
                @else
                    {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
                @endif
            </div>
        @endif

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <!-- Validation Errors -->
        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <!-- Email Address -->
            <div>
                <x-label for="email" :value="__('Email')" />

                <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-button>
                    @if (app()->environment('local'))
                        {{ __('Get Reset Link') }}
                    @else
                        {{ __('Email Password Reset Link') }}
                    @endif
                </x-button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
