<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_super_admin_can_view_audit_trail(): void
    {
        $admin = User::where('email', 'admin@kerefa.com')->first();

        // Create an audit log
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'test_action',
            'module' => 'test_module',
            'description' => 'Test audit log entry',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('audit-logs.index'));

        $response->assertStatus(200);
        $response->assertSee('System Audit Trail');
        $response->assertSee('Test audit log entry');
    }

    public function test_unauthorized_user_cannot_view_audit_trail(): void
    {
        $waiter = User::where('username', 'waiter')->first();

        $response = $this->actingAs($waiter)->get(route('audit-logs.index'));

        $response->assertStatus(403);
    }
}
