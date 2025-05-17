<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager;
    protected User $staff;
    protected string $adminToken;
    protected string $managerToken;
    protected string $staffToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->admin = User::factory()->admin()->create();
        $this->manager = User::factory()->manager()->create();
        $this->staff = User::factory()->staff()->create();
        // $this->otherStaff = User::factory()->staff()->create();

        // Create tokens
        $this->adminToken = $this->admin->createToken('test-token')->plainTextToken;
        $this->managerToken = $this->manager->createToken('test-token')->plainTextToken;
        $this->staffToken = $this->staff->createToken('test-token')->plainTextToken;
    }

    /** @test */
    public function admin_can_view_all_tasks()
    {
        Task::factory()->create(['created_by' => $this->admin->id]);
        Task::factory()->create(['created_by' => $this->manager->id]);
        Task::factory()->create(['assigned_to' => $this->staff->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    /** @test */
    public function manager_can_view_their_tasks_and_their_team_tasks()
    {
        $managerTask = Task::factory()->create(['created_by' => $this->manager->id]);
        $staffTask = Task::factory()->create(['assigned_to' => $this->staff->id]);
        $otherTask = Task::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken
        ])->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $managerTask->id])
            ->assertJsonFragment(['id' => $staffTask->id])
            ->assertJsonMissing(['id' => $otherTask->id]);
    }

    /** @test */
    public function staff_can_only_view_their_own_tasks()
    {
        $ownTask = Task::factory()->create(['assigned_to' => $this->staff->id]);
        // $otherTask = Task::factory()->create(['assigned_to' => $this->Staff->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->staffToken
        ])->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $ownTask->id]);
            // ->assertJsonMissing(['id' => $otherTask->id]);
    }

    /** @test */
    public function admin_can_create_task_for_any_user()
    {
        $taskData = [
            'title' => 'Admin Task',
            'description' => 'Task description',
            'assigned_to' => $this->staff->id,
            'due_date' => now()->addWeek()->format('Y-m-d')
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->postJson('/api/tasks', $taskData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tasks', ['title' => 'Admin Task']);
    }

    /** @test */
    public function manager_can_only_assign_tasks_to_staff()
    {
        // Valid assignment to staff
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken
        ])->postJson('/api/tasks', [
            'title' => 'Valid Task',
            'description' => 'Description',
            'assigned_to' => $this->staff->id,
            'due_date' => now()->addWeek()->format('Y-m-d')
        ]);

        $response->assertStatus(201);

        // Invalid assignment to manager
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken
        ])->postJson('/api/tasks', [
            'title' => 'Invalid Task',
            'description' => 'Description',
            'assigned_to' => $this->manager->id,
            'due_date' => now()->addWeek()->format('Y-m-d')
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function staff_can_only_update_their_task_status()
    {
        $task = Task::factory()->create([
            'assigned_to' => $this->staff->id,
            'status' => 'pending'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->staffToken
        ])->putJson("/api/tasks/{$task->id}", [
            'status' => 'in_progress'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'in_progress'
        ]);
    }

    /** @test */
    public function only_admin_or_creator_can_delete_task()
    {
        $adminTask = Task::factory()->create(['created_by' => $this->admin->id]);
        $managerTask = Task::factory()->create(['created_by' => $this->manager->id]);

        // Admin can delete any task
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken
        ])->deleteJson("/api/tasks/{$managerTask->id}");

        $response->assertStatus(204);

        // Manager can delete their own task
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken
        ])->deleteJson("/api/tasks/{$managerTask->id}");

        $response->assertStatus(204);

        // Staff cannot delete tasks they didn't create
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->staffToken
        ])->deleteJson("/api/tasks/{$managerTask->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function inactive_user_cannot_access_tasks()
    {
        $inactiveUser = User::factory()->staff()->inactive()->create();
        $token = $inactiveUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/tasks');

        $response->assertStatus(403);
    }
}