@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="onboardingWizard()" x-cloak>
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ __('Welcome to HomeLearnAI!') }}</h2>
                <p class="text-gray-600 mt-1">{{ __('Let\'s set up your homeschool environment in just a few steps') }}</p>
            </div>
            <div class="flex items-center space-x-2 text-sm text-gray-500">
                <span>{{ __('Step') }} <span x-text="currentStep"></span> {{ __('of') }} <span x-text="totalSteps"></span></span>
            </div>
        </div>
    </div>

    <!-- Progress Indicator -->
    <div class="bg-white rounded-lg shadow-sm p-6" data-testid="wizard-progress">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="w-full bg-gray-200 rounded-full h-2 flex-1 max-w-md">
                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" :style="'width: ' + ((currentStep / totalSteps) * 100) + '%'"></div>
                </div>
                <div class="text-sm text-gray-500 ml-3">
                    <span x-text="currentStep"></span> / <span x-text="totalSteps"></span>
                </div>
            </div>
            
            <!-- Skip button -->
            <form method="POST" action="{{ route('onboarding.skip') }}" class="inline">
                @csrf
                <button type="submit" 
                        class="text-sm text-gray-500 hover:text-gray-700 transition-colors"
                        data-testid="skip-button">
                    {{ __('Skip Setup') }}
                </button>
            </form>
        </div>
    </div>

    <!-- Wizard Content -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <!-- Step 1: Welcome -->
        <template x-if="currentStep === 1">
            <div data-testid="step-1">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">{{ __('Welcome to Your Homeschool Hub!') }}</h2>
                <p class="text-lg text-gray-600 mb-8 max-w-2xl mx-auto">
                    {{ __('This wizard will help you set up your homeschool environment in just a few simple steps. We\'ll create profiles for your children and set up their learning subjects.') }}
                </p>
                
                <div class="grid md:grid-cols-3 gap-6 mb-8">
                    <div class="text-center p-4 rounded-lg border border-gray-200">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <h3 class="font-medium text-gray-900 mb-1">{{ __('Add Children') }}</h3>
                        <p class="text-sm text-gray-500">{{ __('Set up profiles for each child') }}</p>
                    </div>
                    
                    <div class="text-center p-4 rounded-lg border border-gray-200">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <h3 class="font-medium text-gray-900 mb-1">{{ __('Choose Subjects') }}</h3>
                        <p class="text-sm text-gray-500">{{ __('Select subjects for each child') }}</p>
                    </div>
                    
                    <div class="text-center p-4 rounded-lg border border-gray-200">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="font-medium text-gray-900 mb-1">{{ __('Get Started') }}</h3>
                        <p class="text-sm text-gray-500">{{ __('Begin your homeschool journey') }}</p>
                    </div>
                </div>
            </div>
            </div>
        </template>

        <!-- Step 2: Language Preference -->
        <template x-if="currentStep === 2">
            <div data-testid="step-2">
            <div class="max-w-xl mx-auto">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">{{ __('Choose Your Language') }}</h2>
                    <p class="text-lg text-gray-600">
                        {{ __('Select your preferred language for the application. You can change this later in your profile settings.') }}
                    </p>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-1 gap-4">
                        <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                               :class="userLocale === 'en' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                            <input type="radio" name="locale" value="en" x-model="userLocale" class="sr-only">
                            <div class="flex items-center justify-between w-full">
                                <div class="flex items-center">
                                    <span class="text-2xl mr-4">🇬🇧</span>
                                    <div>
                                        <p class="font-medium text-gray-900">English</p>
                                        <p class="text-sm text-gray-500">Use HomeLearnAI in English</p>
                                    </div>
                                </div>
                                <div x-show="userLocale === 'en'" class="text-blue-500">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                        </label>

                        <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                               :class="userLocale === 'ru' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                            <input type="radio" name="locale" value="ru" x-model="userLocale" class="sr-only">
                            <div class="flex items-center justify-between w-full">
                                <div class="flex items-center">
                                    <span class="text-2xl mr-4">🇷🇺</span>
                                    <div>
                                        <p class="font-medium text-gray-900">Русский</p>
                                        <p class="text-sm text-gray-500">Использовать HomeLearnAI на русском языке</p>
                                    </div>
                                </div>
                                <div x-show="userLocale === 'ru'" class="text-blue-500">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                        </label>
                    </div>

                    <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                        <p class="text-sm text-blue-800">
                            <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            {{ __('You can change your language preference anytime from your profile settings or using the language switcher in the navigation menu.') }}
                        </p>
                    </div>
                </div>
            </div>
            </div>
        </template>

        <!-- Step 3: Children Setup -->
        <template x-if="currentStep === 3">
            <div data-testid="step-3">
            <div class="max-w-2xl mx-auto">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">{{ __('Add Your Children') }}</h2>
                    <p class="text-lg text-gray-600">
                        {{ __('Tell us about your children so we can customize their learning experience.') }}
                    </p>
                </div>

                <form @submit.prevent="saveChildren" data-testid="children-form">
                    <div id="children-container" class="space-y-6">
                        <template x-for="(child, index) in children" :key="index">
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6" :data-testid="'child-form-' + index">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium text-gray-900" x-text="children.length === 1 ? '{{ __('Child Information') }}' : '{{ __('Child') }} ' + (index + 1)"></h3>
                                    <button 
                                        x-show="children.length > 1" 
                                        @click="removeChild(index)"
                                        type="button" 
                                        class="text-red-500 hover:text-red-700 p-1"
                                        :data-testid="'remove-child-' + index">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>

                                <div class="grid md:grid-cols-3 gap-4">
                                    <!-- Name -->
                                    <div class="md:col-span-2">
                                        <label :for="'child-name-' + index" class="block text-sm font-medium text-gray-700 mb-1">
                                            {{ __('Child\'s Name') }} <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            :id="'child-name-' + index"
                                            :name="'children[' + index + '][name]'"
                                            type="text" 
                                            x-model="child.name"
                                            required
                                            placeholder="{{ __('Enter child\'s full name') }}"
                                            :data-testid="'child-name-' + index"
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <div x-show="childErrors[index] && childErrors[index].name" class="mt-1 text-sm text-red-600" x-text="childErrors[index] && childErrors[index].name"></div>
                                    </div>

                                    <!-- Grade -->
                                    <div>
                                        <label :for="'child-grade-' + index" class="block text-sm font-medium text-gray-700 mb-1">
                                            {{ __('Grade') }} <span class="text-red-500">*</span>
                                        </label>
                                        <select 
                                            :id="'child-grade-' + index"
                                            :name="'children[' + index + '][grade]'"
                                            x-model="child.grade"
                                            required
                                            :data-testid="'child-grade-' + index"
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">{{ __('Select grade') }}</option>
                                            @foreach(\App\Models\Child::getGradeOptions() as $gradeValue => $gradeLabel)
                                                <option value="{{ $gradeValue }}">{{ $gradeLabel }}</option>
                                            @endforeach
                                        </select>
                                        <div x-show="childErrors[index] && childErrors[index].grade" class="mt-1 text-sm text-red-600" x-text="childErrors[index] && childErrors[index].grade"></div>
                                    </div>
                                </div>

                                <!-- Independence Level -->
                                <div class="mt-4">
                                    <label :for="'child-independence-' + index" class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ __('Independence Level') }}
                                    </label>
                                    <select 
                                        :id="'child-independence-' + index"
                                        :name="'children[' + index + '][independence_level]'"
                                        x-model="child.independence_level"
                                        :data-testid="'child-independence-' + index"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="1">{{ __('Level 1 - Guided (View only)') }}</option>
                                        <option value="2">{{ __('Level 2 - Basic (Can reorder tasks)') }}</option>
                                        <option value="3">{{ __('Level 3 - Intermediate (Move sessions in week)') }}</option>
                                        <option value="4">{{ __('Level 4 - Advanced (Propose weekly plans)') }}</option>
                                    </select>
                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ __('Controls what your child can do independently in their learning interface') }}
                                    </p>
                                    <div x-show="childErrors[index] && childErrors[index].independence_level" class="mt-1 text-sm text-red-600" x-text="childErrors[index] && childErrors[index].independence_level"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Add Another Child Button -->
                    <div class="mt-6 text-center">
                        <button 
                            x-show="children.length < 5"
                            @click="addChild"
                            type="button" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            data-testid="add-another-child">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            {{ __('Add Another Child') }}
                        </button>
                        <div x-show="children.length >= 5" class="text-sm text-gray-500">
                            {{ __('Maximum of 5 children allowed') }}
                        </div>
                    </div>

                    <!-- Form Error Messages -->
                    <div x-show="formError" class="mt-4 p-3 bg-red-100 border border-red-300 rounded-md" data-testid="form-error">
                        <div class="flex">
                            <svg class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div class="text-sm text-red-600" x-text="formError"></div>
                        </div>
                    </div>

                    <!-- Form Success Messages -->
                    <div x-show="formSuccess" class="mt-4 p-3 bg-green-100 border border-green-300 rounded-md" data-testid="form-success">
                        <div class="flex">
                            <svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <div class="text-sm text-green-600" x-text="formSuccess"></div>
                        </div>
                    </div>
                </form>
            </div>
            </div>
        </template>

        <!-- Step 4: Subjects Setup -->
        <template x-if="currentStep === 4">
            <div data-testid="step-4">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">{{ __('Choose Subjects') }}</h2>
                    <p class="text-lg text-gray-600">
                        {{ __('Select the subjects each child will be learning this year.') }}
                    </p>
                </div>

                <form @submit.prevent="saveSubjects" data-testid="subjects-form">
                    <div class="space-y-8">
                        <template x-for="(child, childIndex) in savedChildren" :key="child.id">
                            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                                <!-- Child Header -->
                                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="text-lg font-medium text-gray-900" x-text="child.name"></h3>
                                            <p class="text-sm text-gray-600">
                                                <span x-text="child.grade + ' {{ __('Grade') }}'"></span>
                                                <span class="mx-2">•</span>
                                                <span x-text="getGradeLevelText(child.grade)"></span>
                                            </p>
                                        </div>
                                        <button 
                                            type="button"
                                            @click="toggleChildExpanded(child.id)"
                                            class="p-2 text-gray-400 hover:text-gray-600">
                                            <svg class="w-5 h-5 transform transition-transform" :class="childExpanded[child.id] ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Subject Selection Content -->
                                <div x-show="childExpanded[child.id]" x-collapse class="p-6" :data-testid="'child-subjects-' + childIndex">
                                    <!-- Recommended Subjects -->
                                    <div class="mb-6">
                                        <h4 class="text-sm font-medium text-gray-900 mb-3">{{ __('Recommended Subjects') }}</h4>
                                        <p class="text-xs text-gray-500 mb-4">{{ __('Based on grade, we suggest these subjects. Uncheck any you don\'t want.') }}</p>
                                        
                                        <div class="grid md:grid-cols-2 gap-3">
                                            <template x-for="subject in getSubjectsForChildGrade(child.grade)" :key="subject">
                                                <label class="flex items-center p-3 rounded-lg border cursor-pointer hover:bg-gray-50 transition-colors"
                                                       :class="isSubjectSelected(child.id, subject) ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                                                    <input type="checkbox" 
                                                           :name="'subjects[' + child.id + '][standard][]'"
                                                           :value="subject"
                                                           :checked="isSubjectSelected(child.id, subject)"
                                                           @change="toggleSubject(child.id, subject)"
                                                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                                    <span class="ml-3 text-sm text-gray-700" x-text="subject"></span>
                                                </label>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Custom Subjects -->
                                    <div class="border-t border-gray-200 pt-6">
                                        <h4 class="text-sm font-medium text-gray-900 mb-3">{{ __('Custom Subjects') }} <span class="text-xs text-gray-500 font-normal">({{ __('optional') }})</span></h4>
                                        <p class="text-xs text-gray-500 mb-4">{{ __('Add up to 3 custom subjects for unique learning needs.') }}</p>
                                        
                                        <div class="space-y-3">
                                            <template x-for="(custom, index) in getCustomSubjects(child.id)" :key="index">
                                                <div class="flex items-center space-x-2">
                                                    <input type="text" 
                                                           :name="'subjects[' + child.id + '][custom][' + index + ']'"
                                                           x-model="subjectsData[child.id].custom[index]"
                                                           :placeholder="'{{ __('Custom subject') }} ' + (index + 1)"
                                                           class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                    <button type="button" 
                                                            @click="removeCustomSubject(child.id, index)"
                                                            x-show="getCustomSubjects(child.id).length > 1"
                                                            class="text-red-500 hover:text-red-700 p-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </template>
                                            <button type="button" 
                                                    @click="addCustomSubject(child.id)"
                                                    x-show="getCustomSubjects(child.id).length < 3"
                                                    class="text-sm text-blue-600 hover:text-blue-700 font-medium inline-flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                                {{ __('Add Custom Subject') }}
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Skip Option -->
                                    <div class="mt-6 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                        <label class="flex items-center">
                                            <input type="checkbox" 
                                                   :name="'subjects[' + child.id + '][skip]'"
                                                   x-model="subjectsData[child.id].skip"
                                                   class="w-4 h-4 text-yellow-600 border-gray-300 rounded focus:ring-yellow-500">
                                            <span class="ml-2 text-sm text-gray-700">{{ __('Skip subjects for now (can add later)') }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- No Children Message -->
                    <div x-show="savedChildren.length === 0" class="text-center py-8">
                        <p class="text-gray-500">{{ __('Please add children in the previous step first.') }}</p>
                    </div>

                    <!-- Form Messages -->
                    <div x-show="subjectsFormError" class="mt-6 p-3 bg-red-100 border border-red-300 rounded-md" data-testid="subjects-form-error">
                        <div class="flex">
                            <svg class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div class="text-sm text-red-600" x-text="subjectsFormError"></div>
                        </div>
                    </div>

                    <div x-show="subjectsFormSuccess" class="mt-6 p-3 bg-green-100 border border-green-300 rounded-md" data-testid="subjects-form-success">
                        <div class="flex">
                            <svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <div class="text-sm text-green-600" x-text="subjectsFormSuccess"></div>
                        </div>
                    </div>
                </form>
            </div>
            </div>
        </template>

        <!-- Step 5: Review and Completion -->
        <template x-if="currentStep === 5">
            <div data-testid="step-5">
            <div class="max-w-3xl mx-auto">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">{{ __('🎉 Your Homeschool is Ready!') }}</h2>
                    <p class="text-lg text-gray-600">
                        {{ __('Here\'s a summary of what we\'ve set up for you. Review everything before finalizing.') }}
                    </p>
                </div>

                <!-- Summary Cards -->
                <div class="space-y-6">
                    <!-- Children Summary -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900" x-text="savedChildren.length === 1 ? '{{ __('1 Child Added') }}' : savedChildren.length + ' {{ __('Children Added') }}'"></h3>
                                <p class="text-sm text-gray-500">{{ __('Ready to start their learning journey') }}</p>
                            </div>
                        </div>
                        
                        <div class="grid gap-3">
                            <template x-for="child in savedChildren" :key="child.id">
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-blue-200 rounded-full flex items-center justify-center mr-3">
                                            <span class="text-sm font-medium text-blue-800" x-text="child.name.charAt(0).toUpperCase()"></span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900" x-text="child.name"></p>
                                            <p class="text-sm text-gray-500">
                                                <span x-text="child.grade + ' {{ __('Grade') }}'" ></span>
                                                <span class="mx-2">•</span>
                                                <span x-text="getIndependenceLevelText(child.independence_level)"></span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-700" x-text="getChildSubjectCount(child.id) + ' {{ __('subjects') }}'"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Subjects Summary -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900" x-text="getTotalSubjectCount() + ' {{ __('Subjects Created') }}'"></h3>
                                <p class="text-sm text-gray-500">{{ __('Customized for each child\'s grade level') }}</p>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <template x-for="child in savedChildren" :key="child.id">
                                <div x-show="!subjectsData[child.id]?.skip && getChildSubjectCount(child.id) > 0">
                                    <h4 class="font-medium text-gray-800 mb-2" x-text="child.name + '\'s {{ __('Subjects') }}'"></h4>
                                    <div class="flex flex-wrap gap-2">
                                        <!-- Standard subjects -->
                                        <template x-for="subject in (subjectsData[child.id]?.selected || [])" :key="subject">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800" x-text="subject"></span>
                                        </template>
                                        <!-- Custom subjects -->
                                        <template x-for="subject in (subjectsData[child.id]?.custom || []).filter(s => s.trim() !== '')" :key="subject">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800" x-text="subject"></span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Next Steps -->  
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ __('What\'s Next?') }}</h3>
                        </div>
                        
                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="text-sm text-gray-700">{{ __('Add units and topics to each subject') }}</p>
                                </div>
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="text-sm text-gray-700">{{ __('Set up your weekly schedule in the Planning Board') }}</p>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="text-sm text-gray-700">{{ __('Review settings for each child in Children Management') }}</p>
                                </div>
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="text-sm text-gray-700">{{ __('Start planning your first week of learning!') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Completion Actions -->
                    <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                        <button @click="previousStep" 
                                class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors flex items-center space-x-2"
                                data-testid="review-back-button">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            <span>{{ __('Go Back to Edit') }}</span>
                        </button>
                        
                        <button @click="completeOnboarding" 
                                :disabled="isCompleting"
                                :class="isCompleting ? 'bg-gray-400 text-white px-6 py-2 rounded-lg cursor-not-allowed' : 'bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors'"
                                data-testid="complete-onboarding-button">
                            <span x-show="!isCompleting" class="flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>{{ __('Complete Setup & Start Learning!') }}</span>
                            </span>
                            <span x-show="isCompleting" class="flex items-center space-x-2">
                                <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ __('Completing Setup...') }}</span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
            </div>
        </template>

        <!-- Navigation -->
        <div class="flex justify-between items-center pt-6 border-t border-gray-200" data-testid="wizard-navigation">
            <button @click="previousStep" 
                    x-show="currentStep > 1"
                    class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors flex items-center space-x-2"
                    data-testid="previous-button">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>{{ __('Previous') }}</span>
            </button>
            
            <div x-show="currentStep < totalSteps">
                <button @click="nextStep" 
                        :disabled="(currentStep === 3 && !canProceedFromStep3) || (currentStep === 4 && !canProceedFromStep4)"
                        :class="((currentStep === 3 && !canProceedFromStep3) || (currentStep === 4 && !canProceedFromStep4)) ? 'bg-gray-300 text-gray-500 px-4 py-2 rounded-lg cursor-not-allowed' : 'bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors'"
                        data-testid="next-button">
                    <span x-show="!(((currentStep === 2 || currentStep === 3) && isSubmitting))" class="flex items-center space-x-2">
                        <span>{{ __('Next') }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                    <span x-show="((currentStep === 2 || currentStep === 3) && isSubmitting)" class="flex items-center space-x-2">
                        <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ __('Saving...') }}</span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function onboardingWizard() {
    return {
        currentStep: 1,
        totalSteps: 5,
        userLocale: '{{ Auth::user()->locale ?? App::getLocale() }}',
        isSubmitting: false,
        formError: '',
        formSuccess: '',
        
        // Children data management
        children: [
            {
                name: '',
                grade: '',
                independence_level: 1
            }
        ],
        childErrors: [{}],
        savedChildren: [],
        
        // Subjects data management
        subjectsData: {},
        childExpanded: {},
        subjectsFormError: '',
        subjectsFormSuccess: '',
        
        // Completion state
        isCompleting: false,
        subjectTemplates: {
            elementary: [
                '{{ __('Reading/Language Arts') }}',
                '{{ __('Mathematics') }}', 
                '{{ __('Science') }}',
                '{{ __('Social Studies') }}',
                '{{ __('Art') }}',
                '{{ __('Music') }}',
                '{{ __('Physical Education') }}'
            ],
            middle: [
                '{{ __('English Language Arts') }}',
                '{{ __('Mathematics') }}',
                '{{ __('Life Science') }}',
                '{{ __('Earth Science') }}',
                '{{ __('Physical Science') }}', 
                '{{ __('Social Studies') }}',
                '{{ __('World History') }}',
                '{{ __('Physical Education') }}',
                '{{ __('World Language') }}',
                '{{ __('Computer Science') }}',
                '{{ __('Art') }}',
                '{{ __('Music') }}',
                '{{ __('Health') }}'
            ],
            high: [
                '{{ __('English Language Arts') }}',
                '{{ __('Algebra') }}',
                '{{ __('Geometry') }}',
                '{{ __('Calculus') }}',
                '{{ __('Biology') }}',
                '{{ __('Chemistry') }}',
                '{{ __('Physics') }}',
                '{{ __('World History') }}',
                '{{ __('U.S. History') }}',
                '{{ __('Foreign Language') }}',
                '{{ __('Computer Science') }}',
                '{{ __('Economics') }}',
                '{{ __('Psychology') }}',
                '{{ __('Art') }}',
                '{{ __('Physical Education') }}'
            ]
        },
        
        // Computed properties
        get canProceedFromStep3() {
            // At least one child must have name and grade
            return this.children.some(child => child.name && child.grade);
        },
        
        get canProceedFromStep4() {
            // Each child must either have subjects selected or be marked as skip
            return this.savedChildren.every(child => {
                const childData = this.subjectsData[child.id];
                if (!childData) return false;
                
                if (childData.skip) return true;
                
                const hasStandardSubjects = childData.selected && childData.selected.length > 0;
                const hasCustomSubjects = childData.custom && childData.custom.some(s => s.trim() !== '');
                
                return hasStandardSubjects || hasCustomSubjects;
            });
        },
        
        // Children management methods
        addChild() {
            if (this.children.length < 5) {
                this.children.push({
                    name: '',
                    grade: '',
                    independence_level: 1
                });
                this.childErrors.push({});
            }
        },
        
        removeChild(index) {
            if (this.children.length > 1) {
                this.children.splice(index, 1);
                this.childErrors.splice(index, 1);
            }
        },
        
        // Validation
        validateChildren() {
            let isValid = true;
            this.childErrors = [];
            this.formError = '';
            
            if (this.children.length === 0) {
                this.formError = '{{ __("At least one child is required") }}';
                return false;
            }
            
            this.children.forEach((child, index) => {
                let errors = {};
                
                if (!child.name || child.name.trim() === '') {
                    errors.name = '{{ __("Child name is required") }}';
                    isValid = false;
                }
                
                if (!child.grade || child.grade === '') {
                    errors.grade = '{{ __("Child grade is required") }}';
                    isValid = false;
                }
                
                this.childErrors[index] = errors;
            });
            
            if (!isValid) {
                this.formError = '{{ __("Please fix the errors above") }}';
            }
            
            return isValid;
        },
        
        // Subject management methods
        initializeChildSubjects(children) {
            this.subjectsData = {};
            this.childExpanded = {};
            
            children.forEach(child => {
                // Initialize subjects data for each child
                this.subjectsData[child.id] = {
                    selected: [...this.getSubjectsForChildGrade(child.grade)], // Pre-select all recommended subjects
                    custom: [''],
                    skip: false
                };
                
                // Expand first child by default, collapse others
                this.childExpanded[child.id] = children.indexOf(child) === 0;
            });
        },
        
        getGradeLevel(grade) {
            const elementary = ['PreK', 'K', '1st', '2nd', '3rd', '4th', '5th'];
            const middle = ['6th', '7th', '8th'];
            const high = ['9th', '10th', '11th', '12th'];
            
            if (elementary.includes(grade)) return 'elementary';
            if (middle.includes(grade)) return 'middle';  
            if (high.includes(grade)) return 'high';
            return 'custom';
        },
        
        getGradeLevelText(gradeLevel) {
            switch (gradeLevel) {
                case 'elementary': return '{{ __('Elementary (K-5)') }}';
                case 'middle': return '{{ __('Middle School (6-8)') }}';
                case 'high': return '{{ __('High School (9-12)') }}';
                default: return '{{ __('Custom Subjects') }}';
            }
        },
        
        getSubjectsForChildGrade(grade) {
            const gradeLevel = this.getGradeLevel(grade);
            return this.subjectTemplates[gradeLevel] || [];
        },
        
        toggleChildExpanded(childId) {
            this.childExpanded[childId] = !this.childExpanded[childId];
        },
        
        isSubjectSelected(childId, subject) {
            const childData = this.subjectsData[childId];
            return childData && childData.selected && childData.selected.includes(subject);
        },
        
        toggleSubject(childId, subject) {
            const childData = this.subjectsData[childId];
            if (!childData || !childData.selected) return;
            
            const index = childData.selected.indexOf(subject);
            if (index > -1) {
                childData.selected.splice(index, 1);
            } else {
                childData.selected.push(subject);
            }
        },
        
        getCustomSubjects(childId) {
            const childData = this.subjectsData[childId];
            return (childData && childData.custom) || [''];
        },
        
        addCustomSubject(childId) {
            const childData = this.subjectsData[childId];
            if (childData && childData.custom && childData.custom.length < 3) {
                childData.custom.push('');
            }
        },
        
        removeCustomSubject(childId, index) {
            const childData = this.subjectsData[childId];
            if (childData && childData.custom) {
                childData.custom.splice(index, 1);
                if (childData.custom.length === 0) {
                    childData.custom = [''];
                }
            }
        },
        
        // AJAX submission of subjects data
        async saveSubjects() {
            this.isSubmitting = true;
            this.subjectsFormError = '';
            this.subjectsFormSuccess = '';
            
            // Check if all children are marked as skip
            const allChildrenSkipped = this.savedChildren.every(child => {
                const childData = this.subjectsData[child.id];
                return childData && childData.skip;
            });
            
            // If all children are skipped, proceed directly to step 4 without backend call
            if (allChildrenSkipped) {
                this.subjectsFormSuccess = '{{ __("Subjects setup skipped successfully!") }}';
                
                // Move to review step after short delay
                setTimeout(() => {
                    this.currentStep = 4;
                    this.subjectsFormSuccess = '';
                }, 1500);
                this.isSubmitting = false;
                return;
            }
            
            // Build subjects array for submission
            const subjects = [];
            
            this.savedChildren.forEach(child => {
                const childData = this.subjectsData[child.id];
                if (!childData || childData.skip) return;
                
                // Add standard subjects
                if (childData.selected) {
                    childData.selected.forEach(subjectName => {
                        subjects.push({
                            name: subjectName,
                            child_id: child.id,
                            color: this.getSubjectColor(subjectName, subjects.length)
                        });
                    });
                }
                
                // Add custom subjects
                if (childData.custom) {
                    childData.custom.forEach(subjectName => {
                        if (subjectName && subjectName.trim() !== '') {
                            subjects.push({
                                name: subjectName.trim(),
                                child_id: child.id,
                                color: this.getSubjectColor(subjectName, subjects.length)
                            });
                        }
                    });
                }
            });
            
            try {
                const response = await fetch('{{ route("onboarding.subjects") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ subjects })
                });
                
                const data = await response.json();
                
                if (response.ok && data.success) {
                    this.subjectsFormSuccess = data.message || '{{ __("Subjects added successfully!") }}';
                    
                    // Move to review step after short delay
                    setTimeout(() => {
                        this.currentStep = 4;
                        this.subjectsFormSuccess = '';
                    }, 1500);
                } else {
                    throw new Error(data.error || '{{ __("Failed to save subjects") }}');
                }
            } catch (error) {
                console.error('Error saving subjects:', error);
                this.subjectsFormError = error.message || '{{ __("An error occurred while saving subjects") }}';
            } finally {
                this.isSubmitting = false;
            }
        },
        
        getSubjectColor(subjectName, index) {
            // Color mapping similar to SubjectController
            const colorMap = {
                'Mathematics': '#3B82F6',
                'Science': '#10B981', 
                'Biology': '#10B981',
                'Chemistry': '#10B981',
                'Physics': '#10B981',
                'Life Science': '#10B981',
                'Earth Science': '#10B981',
                'Physical Science': '#10B981',
                'Reading/Language Arts': '#8B5CF6',
                'English Language Arts': '#8B5CF6',
                'Social Studies': '#F97316',
                'History': '#F97316',
                'World History': '#F97316',
                'U.S. History': '#F97316',
                'Art': '#EC4899',
                'Music': '#EC4899',
                'Physical Education': '#EF4444',
                'Health': '#EF4444',
                'Computer Science': '#6B7280',
                'Foreign Language': '#14B8A6',
                'World Language': '#14B8A6'
            };
            
            // Check for specific color mapping
            for (const [key, color] of Object.entries(colorMap)) {
                if (subjectName.toLowerCase().includes(key.toLowerCase())) {
                    return color;
                }
            }
            
            // Default color cycle
            const colors = ['#3B82F6', '#10B981', '#EAB308', '#8B5CF6', '#EC4899', '#6366F1', '#EF4444', '#F97316'];
            return colors[index % colors.length];
        },
        
        // AJAX submission of children data
        async saveLanguagePreference() {
            try {
                const response = await fetch('{{ route("locale.update") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        locale: this.userLocale
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Update the app locale
                    window.currentLocale = this.userLocale;
                    
                    // Reload to apply language changes
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Failed to save language preference');
                }
            } catch (error) {
                console.error('Language preference error:', error);
                this.formError = 'Failed to save language preference. You can continue and change it later.';
                // Allow to continue even if language save fails
                setTimeout(() => {
                    this.formError = '';
                }, 3000);
            }
        },
        
        async saveChildren() {
            if (!this.validateChildren()) {
                return;
            }
            
            this.isSubmitting = true;
            this.formError = '';
            this.formSuccess = '';
            
            try {
                const response = await fetch('{{ route("onboarding.child.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        children: this.children.map(child => ({
                            name: child.name.trim(),
                            grade: child.grade,
                            independence_level: parseInt(child.independence_level)
                        }))
                    })
                });
                
                const data = await response.json();
                
                if (response.ok && data.success) {
                    this.savedChildren = data.children || [];
                    this.formSuccess = data.message || '{{ __("Children saved successfully!") }}';
                    
                    // Initialize subjects data for the saved children
                    this.initializeChildSubjects(this.savedChildren);
                    
                    // Move to next step after short delay
                    setTimeout(() => {
                        this.currentStep++;
                        this.formSuccess = '';
                    }, 1500);
                } else if (response.status === 401) {
                    // Session expired - redirect to login
                    alert(data.error || '{{ __("Session expired. Please login again.") }}');
                    window.location.href = '{{ route("login") }}';
                } else {
                    throw new Error(data.error || '{{ __("Failed to save children") }}');
                }
            } catch (error) {
                console.error('Error saving children:', error);
                this.formError = error.message || '{{ __("An error occurred while saving children") }}';
            } finally {
                this.isSubmitting = false;
            }
        },
        
        // Navigation methods
        async nextStep() {
            if (this.currentStep === 2) {
                // Save language preference
                await this.saveLanguagePreference();
                this.currentStep++;
            } else if (this.currentStep === 3) {
                // For step 3, trigger form submission
                await this.saveChildren();
            } else if (this.currentStep === 4) {
                // For step 4, trigger subjects submission and go to review
                await this.saveSubjects();
            } else if (this.currentStep < this.totalSteps) {
                // Simple navigation for other steps
                this.currentStep++;
            }
        },
        
        previousStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
                this.formError = '';
                this.formSuccess = '';
            }
        },
        
        completeWizard() {
            // Legacy method for when we only had 3 steps
            // Now redirects to the new completion flow
            this.currentStep = 4;
        },
        
        // New completion method for Phase 5
        async completeOnboarding() {
            this.isCompleting = true;
            
            try {
                const response = await fetch('{{ route("onboarding.complete") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (response.ok && data.success) {
                    // Show brief success message
                    const successMessage = document.createElement('div');
                    successMessage.className = 'fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-6 py-3 rounded-lg shadow-lg z-50';
                    successMessage.innerHTML = '{{ __("🎉 Setup complete! Welcome to your homeschool hub!") }}';
                    document.body.appendChild(successMessage);
                    
                    // Redirect after short delay
                    setTimeout(() => {
                        window.location.href = data.redirect || '{{ route("dashboard") }}';
                    }, 2000);
                } else {
                    throw new Error(data.error || '{{ __("Failed to complete onboarding") }}');
                }
            } catch (error) {
                console.error('Error completing onboarding:', error);
                alert(error.message || '{{ __("An error occurred while completing setup") }}');
                this.isCompleting = false;
            }
        },
        
        // Helper methods for review display
        getChildSubjectCount(childId) {
            const childData = this.subjectsData[childId];
            if (!childData || childData.skip) return 0;
            
            let count = 0;
            if (childData.selected) count += childData.selected.length;
            if (childData.custom) count += childData.custom.filter(s => s && s.trim() !== '').length;
            
            return count;
        },
        
        getTotalSubjectCount() {
            return this.savedChildren.reduce((total, child) => {
                return total + this.getChildSubjectCount(child.id);
            }, 0);
        },
        
        getIndependenceLevelText(level) {
            switch (parseInt(level)) {
                case 1: return '{{ __("Guided") }}';
                case 2: return '{{ __("Basic") }}';
                case 3: return '{{ __("Intermediate") }}';
                case 4: return '{{ __("Advanced") }}';
                default: return '{{ __("Custom") }}';
            }
        }
    }
}
</script>
</div>
@endsection