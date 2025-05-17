<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use App\Policies\TaskPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskServiceTest extends TestCase
{
    protected User $admin;
    protected User $manager;
    protected User $staff;
    protected TaskPolicy $taskPolicy;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->staff = User::factory()->create(['role' => 'staff']);
        
        $this->taskPolicy = new TaskPolicy();
    }

    /** @test */
    public function admin_can_manage_all_tasks()
    {
        $task = Task::factory()->create();
        
        $this->assertTrue($this->taskPolicy->view($this->admin, $task));
        $this->assertTrue($this->taskPolicy->update($this->admin, $task));
        $this->assertTrue($this->taskPolicy->delete($this->admin, $task));
    }

    /** @test */
    public function manager_can_only_manage_their_team_tasks()
    {
        $managerTask = Task::factory()->create(['created_by' => $this->manager->id]);
        $staffTask = Task::factory()->create(['assigned_to' => $this->staff->id]);
        $otherTask = Task::factory()->create();
        
        $this->assertTrue($this->taskPolicy->update($this->manager, $managerTask));
        $this->assertTrue($this->taskPolicy->update($this->manager, $staffTask));
        $this->assertFalse($this->taskPolicy->update($this->manager, $otherTask));
    }

    /** @test */
    public function staff_can_only_manage_their_own_tasks()
    {
        $ownTask = Task::factory()->create(['assigned_to' => $this->staff->id]);
        $otherTask = Task::factory()->create();
        
        $this->assertTrue($this->taskPolicy->update($this->staff, $ownTask));
        $this->assertFalse($this->taskPolicy->update($this->staff, $otherTask));
    }

    /** @test */
    public function overdue_task_is_detected_correctly()
    {
        $overdueTask = Task::factory()->create([
            'due_date' => now()->subDay(),
            'status' => 'pending'
        ]);
        
        $completedTask = Task::factory()->create([
            'due_date' => now()->subDay(),
            'status' => 'done'
        ]);
        
        $currentTask = Task::factory()->create([
            'due_date' => now()->addDay(),
            'status' => 'pending'
        ]);
        
        $this->assertTrue($overdueTask->isOverdue());
        $this->assertFalse($completedTask->isOverdue());
        $this->assertFalse($currentTask->isOverdue());
    }
}