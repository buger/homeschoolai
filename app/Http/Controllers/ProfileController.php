<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update user preferences including language, timezone, etc.
     */
    public function updatePreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:en,ru'],
            'timezone' => ['required', 'string'],
            'date_format' => ['required', 'string'],
            'region_format' => ['required', 'string', 'in:us,eu,custom'],
            'time_format' => ['nullable', 'string', 'in:12h,24h'],
            'week_start' => ['nullable', 'string', 'in:sunday,monday'],
            'date_format_type' => ['nullable', 'string', 'in:us,eu,iso'],
            'email_notifications' => ['nullable', 'boolean'],
            'review_reminders' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $previousLocale = $user->locale;

        // Update basic preferences
        $user->locale = $validated['locale'];
        $user->timezone = $validated['timezone'];
        $user->date_format = $validated['date_format'];
        $user->region_format = $validated['region_format'];
        // Handle boolean checkboxes - missing checkbox means false
        $user->email_notifications = $validated['email_notifications'] ?? false;
        $user->review_reminders = $validated['review_reminders'] ?? false;

        // Handle regional format preferences
        if ($validated['region_format'] === 'custom') {
            // Use custom values provided by user, with proper defaults
            $user->time_format = $validated['time_format'] ?? ($user->time_format ?: '12h');
            $user->week_start = $validated['week_start'] ?? ($user->week_start ?: 'sunday');
            $user->date_format_type = $validated['date_format_type'] ?? ($user->date_format_type ?: 'us');
        } else {
            // Apply preset defaults for the selected region format
            $defaults = \App\Models\User::getRegionalDefaults($user->locale);
            if ($validated['region_format'] === 'us') {
                $user->time_format = '12h';
                $user->week_start = 'sunday';
                $user->date_format_type = 'us';
                $user->date_format = 'm/d/Y';
            } elseif ($validated['region_format'] === 'eu') {
                $user->time_format = '24h';
                $user->week_start = 'monday';
                $user->date_format_type = 'eu';
                $user->date_format = 'd.m.Y';
            }
        }

        $user->save();

        // Update session locale if changed
        if ($previousLocale !== $user->locale) {
            app()->setLocale($user->locale);
            session(['locale' => $user->locale]);
        }

        return Redirect::route('profile.edit')->with('status', __('Preferences updated successfully'));
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required'],
        ]);

        $user = $request->user();

        // Manually check password
        if (! \Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => __('The provided password is incorrect.')], 'userDeletion');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
