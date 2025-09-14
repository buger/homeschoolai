@extends('layouts.app')

@section('content')
<div class="py-12" x-data="{ activeTab: 'general' }">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">{{ __('Profile Settings') }}</h1>
            <p class="mt-2 text-sm text-gray-600">{{ __('Manage your account settings and preferences') }}</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 lg:gap-8">
            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <!-- Profile Picture Section -->
                    <div class="p-6 text-center border-b border-gray-200">
                        <div class="inline-block">
                            <div class="w-24 h-24 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-3xl font-bold shadow-lg">
                                {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                            </div>
                        </div>
                        <h2 class="mt-4 text-lg font-semibold text-gray-900">{{ $user->name ?? 'User' }}</h2>
                        <p class="text-sm text-gray-500">{{ $user->email ?? 'No email' }}</p>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="p-4">
                        <div class="grid grid-cols-2 gap-4 text-center">
                            <div class="py-2">
                                <p class="text-xl font-bold text-blue-600">{{ $user->children()->count() ?? 0 }}</p>
                                <p class="text-xs text-gray-500">{{ __('Children') }}</p>
                            </div>
                            <div class="py-2">
                                <p class="text-xl font-bold text-green-600">{{ $user->subjects()->count() ?? 0 }}</p>
                                <p class="text-xs text-gray-500">{{ __('Subjects') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Links -->
                    <nav class="p-2">
                        <button @click="activeTab = 'general'"
                                data-testid="profile-tab-general"
                                :class="activeTab === 'general' ? 'bg-blue-50 text-blue-700 border-l-4 border-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent'"
                                class="w-full flex items-center px-3 py-2 text-sm font-medium transition-all">
                            <svg class="mr-3 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            {{ __('General') }}
                        </button>
                        
                        <button @click="activeTab = 'preferences'"
                                data-testid="profile-tab-preferences"
                                :class="activeTab === 'preferences' ? 'bg-blue-50 text-blue-700 border-l-4 border-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent'"
                                class="w-full flex items-center px-3 py-2 text-sm font-medium transition-all mt-1">
                            <svg class="mr-3 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ __('Preferences') }}
                        </button>
                        
                        <button @click="activeTab = 'security'"
                                data-testid="profile-tab-security"
                                :class="activeTab === 'security' ? 'bg-blue-50 text-blue-700 border-l-4 border-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent'"
                                class="w-full flex items-center px-3 py-2 text-sm font-medium transition-all mt-1">
                            <svg class="mr-3 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            {{ __('Security') }}
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="lg:col-span-3">
                <!-- General Tab -->
                <div x-show="activeTab === 'general'" x-transition class="bg-white rounded-lg shadow-sm">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('General Information') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Update your personal information') }}</p>
                    </div>
                    
                    <form method="POST" action="{{ route('profile.update') }}" class="p-6 space-y-6">
                        @csrf
                        @method('PATCH')
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">{{ __('Full Name') }}</label>
                                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}"
                                       data-testid="profile-name-input"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       required>
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">{{ __('Email Address') }}</label>
                                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                                       data-testid="profile-email-input"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       required>
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" data-testid="profile-save-changes" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Preferences Tab -->
                <div x-show="activeTab === 'preferences'" x-transition class="bg-white rounded-lg shadow-sm" style="display: none;">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Preferences') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Customize your experience') }}</p>
                    </div>
                    
                    <div class="p-6 space-y-6">
                        <!-- Language Preference -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">{{ __('Language') }}</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-data="{ selectedLocale: '{{ $user->locale ?? 'en' }}' }">
                                <label class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                                       :class="selectedLocale === 'en' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                                    <input type="radio" name="locale" value="en" x-model="selectedLocale"
                                           data-testid="locale-option-en"
                                           @change="updateLocale('en')" class="sr-only">
                                    <div class="flex items-center">
                                        <span class="text-2xl mr-3">🇬🇧</span>
                                        <div>
                                            <p class="font-medium text-gray-900">English</p>
                                            <p class="text-xs text-gray-500">Interface language</p>
                                        </div>
                                    </div>
                                    <div x-show="selectedLocale === 'en'" class="ml-auto text-blue-500">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </label>
                                
                                <label class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                                       :class="selectedLocale === 'ru' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                                    <input type="radio" name="locale" value="ru" x-model="selectedLocale"
                                           data-testid="locale-option-ru"
                                           @change="updateLocale('ru')" class="sr-only">
                                    <div class="flex items-center">
                                        <span class="text-2xl mr-3">🇷🇺</span>
                                        <div>
                                            <p class="font-medium text-gray-900">Русский</p>
                                            <p class="text-xs text-gray-500">Язык интерфейса</p>
                                        </div>
                                    </div>
                                    <div x-show="selectedLocale === 'ru'" class="ml-auto text-blue-500">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Other Preferences -->
                        <form method="POST" action="{{ route('profile.preferences') }}" class="space-y-6">
                            @csrf
                            @method('PATCH')
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="timezone" class="block text-sm font-medium text-gray-700">{{ __('Timezone') }}</label>
                                    <select name="timezone" id="timezone" 
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        @foreach(\App\Models\User::COMMON_TIMEZONES as $value => $label)
                                            <option value="{{ $value }}" {{ ($user->timezone ?? 'UTC') === $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="date_format" class="block text-sm font-medium text-gray-700">{{ __('Date Format') }}</label>
                                    <select name="date_format" id="date_format" 
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        @foreach(\App\Models\User::DATE_FORMATS as $value => $label)
                                            <option value="{{ $value }}" {{ ($user->date_format ?? 'Y-m-d') === $value ? 'selected' : '' }}>
                                                {{ $label }} ({{ now()->format($value) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Notification Settings -->
                            <div class="space-y-4">
                                <h4 class="text-sm font-medium text-gray-900">{{ __('Notifications') }}</h4>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" name="email_notifications" value="1" 
                                           {{ $user->email_notifications ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">{{ __('Email notifications') }}</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" name="review_reminders" value="1" 
                                           {{ $user->review_reminders ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">{{ __('Review reminders') }}</span>
                                </label>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" data-testid="profile-save-preferences" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    {{ __('Save Preferences') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Security Tab -->
                <div x-show="activeTab === 'security'" x-transition class="bg-white rounded-lg shadow-sm" style="display: none;">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Security Settings') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Manage your account security') }}</p>
                    </div>
                    
                    <div class="p-6">
                        <!-- Update Password Form -->
                        <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
                            @csrf
                            @method('PUT')
                            
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700">{{ __('Current Password') }}</label>
                                <input type="password" name="current_password" id="current_password" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       autocomplete="current-password">
                                @error('current_password', 'updatePassword')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700">{{ __('New Password') }}</label>
                                <input type="password" name="password" id="password" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       autocomplete="new-password">
                                @error('password', 'updatePassword')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">{{ __('Confirm New Password') }}</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       autocomplete="new-password">
                                @error('password_confirmation', 'updatePassword')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    {{ __('Update Password') }}
                                </button>
                            </div>
                        </form>
                        
                        <!-- Delete Account Section -->
                        <div class="mt-10 pt-10 border-t border-gray-200">
                            <h4 class="text-sm font-medium text-red-600">{{ __('Danger Zone') }}</h4>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Once your account is deleted, all of its resources and data will be permanently deleted.') }}</p>
                            
                            <form method="POST" action="{{ route('profile.destroy') }}" class="mt-6" 
                                  onsubmit="return confirm('{{ __('Are you sure you want to delete your account?') }}');">
                                @csrf
                                @method('DELETE')
                                
                                <div class="mb-4">
                                    <label for="delete_password" class="block text-sm font-medium text-gray-700">{{ __('Password') }}</label>
                                    <input type="password" name="password" id="delete_password" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm"
                                           placeholder="{{ __('Enter your password to confirm') }}"
                                           required>
                                    @error('password', 'userDeletion')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    {{ __('Delete Account') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function updateLocale(locale) {
    try {
        // Show loading state if possible
        const radioButtons = document.querySelectorAll('input[name="locale"]');
        radioButtons.forEach(btn => btn.disabled = true);

        const response = await fetch('{{ route('locale.update') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ locale: locale })
        });

        const data = await response.json();

        if (data.success) {
            // Show success message
            if (window.showToast) {
                window.showToast(data.message || 'Language updated successfully', 'success');
            }

            // Update global locale state
            window.currentLocale = locale;

            // Reload page to show new language
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            throw new Error(data.message || 'Failed to update language');
        }
    } catch (error) {
        console.error('Error updating locale:', error);

        // Show error message
        if (window.showToast) {
            window.showToast('Failed to update language. Please try again.', 'error');
        }

        // Re-enable radio buttons
        const radioButtons = document.querySelectorAll('input[name="locale"]');
        radioButtons.forEach(btn => btn.disabled = false);
    }
}
</script>
@endsection