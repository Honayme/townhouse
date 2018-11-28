<?php
namespace Tests\Feature;

use App\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Notification;
use Tests\TenantTestCase;

class TenantDeleteCommandTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }
    /** @test */
    public function tenant_fqdn_is_required(): void
    {
        $this->expectExceptionMessage('Not enough arguments (missing: "fqdn").');
        $this->artisan('tenant:delete');
    }
    /** @test */
    public function can_delete_existing_tenant(): void
    {
        $this->artisan('tenant:create', ['fqdn' => 'client1', 'name' => 'example', 'email' => 'test@example.com']);
        $this->artisan('tenant:delete', ['fqdn' => 'client1'.env('APP_URL')]);
        $this->assertSystemDatabaseMissing('hostnames', ['fqdn' => 'client1']);
    }
    /** @test */
    public function tenant_database_is_removed(): void
    {
        $this->artisan('tenant:create', ['fqdn' => 'client1', 'name' => 'example', 'email' => 'test@example.com']);
        $this->artisan('tenant:delete', ['fqdn' => 'client1'.env('APP_URL')]);
        $this->expectException(QueryException::class);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com'],'mysql');
    }
    protected function tearDown(): void
    {
        if ($tenant = Tenant::retrieveBy('client1')) {
            $tenant->delete('example');
        }
        parent::tearDown();
    }
}
