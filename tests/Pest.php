<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(Tests\TestCase::class)->in('Feature');
pest()->extend(Tests\TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function adminUser(string $email = 'admin@muno.local'): \App\Models\User
{
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = \App\Models\User::firstOrCreate(
        ['email' => $email],
        ['name' => 'Test Admin', 'password' => \Illuminate\Support\Facades\Hash::make('secret1234')]
    );

    if (!$user->hasRole('super_admin')) {
        $user->assignRole('super_admin');
    }

    return $user;
}
