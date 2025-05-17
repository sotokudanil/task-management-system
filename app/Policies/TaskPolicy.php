<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Task $task)
    {
        return $user->id === $task->assigned_to || $user->id === $task->created_by;
    }

    public function create(User $user)
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function update(User $user, Task $task)
    {
        // Staff hanya bisa update task yang ditugaskan ke mereka
        if ($user->role === 'staff') {
            return $user->id === $task->assigned_to;
        }
        
        // Manager bisa update task yang mereka buat atau task untuk staff mereka
        if ($user->role === 'manager') {
            return $user->id === $task->created_by || 
                ($task->assignee->role === 'staff');
        }
        
        // Admin bisa update semua task
        return true;
    }
    
    public function delete(User $user, Task $task)
    {
        return $user->id === $task->created_by || $user->role === 'admin';
    }

    public function assign(User $user)
    {
        return in_array($user->role, ['admin', 'manager']);
    }
}