<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user)
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function view(User $user, User $model)
    {
        return $user->role === 'admin' || 
               ($user->role === 'manager' && $model->role === 'staff');
    }

    public function create(User $user)
    {
        return $user->role === 'admin';
    }

    public function update(User $user, User $model)
    {
        return $user->role === 'admin' || 
               ($user->role === 'manager' && $model->role === 'staff');
    }

    public function delete(User $user, User $model)
    {
        return $user->role === 'admin';
    }
}