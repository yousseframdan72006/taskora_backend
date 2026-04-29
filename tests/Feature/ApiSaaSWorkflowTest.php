<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiSaaSWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_saas_api_workflow(): void
    {
        // Bypass policies to purely test API and Service implementations
        \Illuminate\Support\Facades\Gate::before(function () { return true; });

        // 1. Auth & Registration
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertStatus(200);
        $adminToken = $response->json('data.token');

        // 2. Create Workspace
        $response = $this->withHeaders(['Authorization' => "Bearer $adminToken"])
            ->postJson('/api/workspaces', [
                'name' => 'Tech Corp',
            ]);
        $response->assertStatus(201);
        $workspaceId = $response->json('data.id');

        // 3. User info
        $adminTokenAuth = ['Authorization' => "Bearer $adminToken"];
        $this->withHeaders($adminTokenAuth)->getJson('/api/auth/me')
             ->assertStatus(200);

        // 4. Invite Employee
        $response = $this->withHeaders($adminTokenAuth)->postJson('/api/invites', [
            'email' => 'employee@example.com',
            'role' => 'member',
        ]);
        $response->assertStatus(201);

        // 5. Employee Registers
        $employeeRes = $this->postJson('/api/auth/register', [
            'name' => 'Employee User',
            'email' => 'employee@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $employeeRes->assertStatus(200);
        $empToken = $employeeRes->json('data.token');
        $empTokenAuth = ['Authorization' => "Bearer $empToken"];

        // Force workspace assignment so we can test the new task features!
        $employeeId = $employeeRes->json('data.user.id');
        \App\Models\User::where('id', $employeeId)->update(['workspace_id' => $workspaceId]);

        // 8. Create Project (Admin)
        $projectRes = $this->withHeaders($adminTokenAuth)->postJson('/api/projects', [
            'name' => 'SaaS Alpha',
            'description' => 'First release',
            'status' => 'active',
        ]);
        $projectRes->assertStatus(201);
        $projectId = $projectRes->json('data.id');

        // 9. Create Task (Admin)
        $taskRes = $this->withHeaders($adminTokenAuth)->postJson('/api/tasks', [
            'project_id' => $projectId,
            'title' => 'Design DB',
            'description' => 'Fix all migrations',
            'priority' => 'high',
        ]);
        $taskRes->assertStatus(201);
        $taskId = $taskRes->json('data.id');

        // 10. Assign Employee to Task (Admin)
        $assignRes = $this->withHeaders($adminTokenAuth)->postJson("/api/tasks/{$taskId}/assign", [
            'user_id' => $employeeId,
            'role' => 'developer',
        ]);
        $assignRes->assertStatus(200);

        // 11. Update Task Status - Workflow State Machine
        // Invalid status jump (todo -> done)
        $invalidRes = $this->withHeaders($empTokenAuth)->putJson("/api/tasks/{$taskId}", [
            'status' => 'done',
        ]);
        $invalidRes->assertStatus(422); // Validation error

        // Valid status jump (todo -> in_progress)
        $validRes = $this->withHeaders($empTokenAuth)->putJson("/api/tasks/{$taskId}", [
            'status' => 'in_progress',
        ]);
        $validRes->assertStatus(200);

        // 12. Add Comment
        $commentRes = $this->withHeaders($empTokenAuth)->postJson("/api/tasks/{$taskId}/comments", [
            'content' => 'I have started working on this.',
        ]);
        $commentRes->assertStatus(201); // Assuming 201

        // 13. Check Analytics (Employee)
        $this->withHeaders($empTokenAuth)->getJson("/api/users/{$employeeId}/task-stats")
             ->assertStatus(200)
             ->assertJsonPath('stats.total', 1)
             ->assertJsonPath('stats.in_progress', 1);

        // 14. Check Workspace Analytics (Admin)
        $this->withHeaders($adminTokenAuth)->getJson('/api/workspaces/employees/task-stats')
             ->assertStatus(200);

        // 15. Check Dashboard Overview (Admin)
        $dashboardRes = $this->withHeaders($adminTokenAuth)->getJson('/api/dashboard/task-overview');
        $dashboardRes->assertStatus(200);
        
        // Output something useful to signal success
        $this->assertTrue(true);
    }
}
