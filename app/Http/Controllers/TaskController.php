<?php
namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $tasks = Task::query();

        if ($user->role === 'staff') {
            $tasks->where('assigned_to', $user->id);
        } elseif ($user->role === 'manager') {
            $staffIds = User::where('role', 'staff')->pluck('id');
            $tasks->where(function($query) use ($user, $staffIds) {
                $query->where('created_by', $user->id)
                      ->orWhereIn('assigned_to', $staffIds);
            });
        }

        return response()->json($tasks->with(['assignee', 'creator'])->get());
    }

    public function store(Request $request)
    {
        $this->authorize('create', Task::class);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'assigned_to' => 'required|uuid|exists:users,id',
            'due_date' => 'required|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $assignedUser = User::findOrFail($request->assigned_to);
            $currentUser = auth()->user();

            // Validasi role
            if ($currentUser->role === 'manager' && $assignedUser->role !== 'staff') {
                return response()->json([
                    'message' => 'Managers can only assign tasks to staff'
                ], 403);
            }

            if ($currentUser->role === 'staff' && $assignedUser->id !== $currentUser->id) {
                return response()->json([
                    'message' => 'Staff can only assign tasks to themselves'
                ], 403);
            }

            $task = Task::create([
                'id' => (string) Str::uuid(),
                'title' => $request->title,
                'description' => $request->description,
                'assigned_to' => $request->assigned_to,
                'status' => 'pending',
                'due_date' => $request->due_date,
                'created_by' => $currentUser->id,
            ]);

            return response()->json([
                'message' => 'Task created successfully',
                'task' => $task
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating task',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function update(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:pending,in_progress,done',
            'due_date' => 'sometimes|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Jika staff mencoba mengubah assigned_to
        if ($request->has('assigned_to') && auth()->user()->role === 'staff') {
            return response()->json(['message' => 'Staff cannot reassign tasks'], 403);
        }

        // Jika manager mencoba mengubah assigned_to
        if ($request->has('assigned_to') && auth()->user()->role === 'manager') {
            $assignedUser = User::find($request->assigned_to);
            if ($assignedUser->role !== 'staff') {
                return response()->json(['message' => 'Managers can only assign tasks to staff'], 403);
            }
        }

        $task->update($request->all());

        return response()->json($task);
    }
    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);
        $task->delete();
        return response()->json(null, 204);
    }
}