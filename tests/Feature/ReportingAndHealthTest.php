<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingAndHealthTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $role): User
    {
        $roleModel = Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)]);
        return User::factory()->create(['role_id' => $roleModel->id]);
    }

    public function test_health_endpoint_returns_ok(): void
    {
        $this->get('/health')->assertOk()->assertJson(['status' => 'ok']);
    }

    public function test_owner_can_access_reports_and_export_and_kasir_cannot(): void
    {
        $owner = $this->createUser('owner');
        $kasir = $this->createUser('kasir');

        $this->actingAs($owner)->get(route('reports.index'))->assertOk();
        $this->actingAs($owner)->get(route('reports.export'))->assertOk();
        $this->actingAs($owner)->get(route('reports.export-pdf'))->assertOk();

        $this->actingAs($kasir)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($kasir)->get(route('reports.export'))->assertForbidden();
    }

    public function test_owner_can_view_audit_logs_and_kasir_cannot(): void
    {
        $owner = $this->createUser('owner');
        $kasir = $this->createUser('kasir');
        $this->actingAs($owner)->get(route('audit-logs.index'))->assertOk();
        $this->actingAs($kasir)->get(route('audit-logs.index'))->assertForbidden();
    }
}
