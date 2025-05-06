<?php

namespace App\Services\Module;

use App\Mail\WelcomeEmailMail;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;

class ProfileService
{
    public function updateProfile(User $user, array $data): User
    {
        $user->setUseBase64(true);
        $user->update($data);
        return $user;
    }

    public function updatePassword(User $user, array $data): void
    {
        $user->update([
            'password' => Hash::make($data['password'])
        ]);
    }

    public function updateEmail(User $user, array $data): void
    {
        $user->update([
            'email' => $data['email'],
            'is_active' => 0,
            'email_verified_at' => null
        ]);
        $reactUrl = Request::header('Origin') ?: 'https://your-default-react-app.com';
        Mail::to($user->email)->send(new WelcomeEmailMail($user, $reactUrl));
    }

    public function getPreferences(User $user): UserPreference
    {
        return $user->preferences ?? new UserPreference();
    }

    public function updatePreferences(User $user, array $data): UserPreference
    {
        $preferences = $user->preferences ?? new UserPreference(['user_id' => $user->id]);
        $preferences->fill($data);
        $preferences->save();

        return $preferences;
    }
}
