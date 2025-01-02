<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Role;

class UserObserver
{
    public function created(User $user)
    {
        $customerRole = Role::where('slug', 'customer')->first();
        if ($customerRole) {
            $user->roles()->attach($customerRole->id);
        }
    }
} 