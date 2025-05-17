<?php
namespace App\Console\Commands;

use App\Models\Task;
use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckOverdueTasks extends Command
{
    protected $signature = 'tasks:check-overdue';
    protected $description = 'Check for overdue tasks and log them';

    public function handle()
    {
        $overdueTasks = Task::where('due_date', '<', Carbon::now())
                          ->where('status', '!=', 'done')
                          ->where('is_overdue', false)
                          ->update(['is_overdue' => true])
                          ->get();

        foreach ($overdueTasks as $task) {
            ActivityLog::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'user_id' => $task->created_by,
                'action' => 'task_overdue',
                'description' => "Task overdue: {$task->id}",
                'logged_at' => now(),
            ]);
        }

        $this->info("Logged {$overdueTasks->count()} overdue tasks.");
    }
}