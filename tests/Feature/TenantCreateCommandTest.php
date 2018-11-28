<?php
namespace Tests\Feature;
use App\Notifications\TenantCreated;
use App\Tenant;
use App\User;
use Illuminate\Support\Facades\Notification;
use Tests\TenantTestCase;

class TenantCreateCommandTest extends TenantTestCase
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
        $this->artisan('tenant:create', ['name' => 'bob', 'email' => 'bob@mail.com']);
    }
    /** @test */
    public function tenant_name_is_required(): void
    {
        $this->expectExceptionMessage('Not enough arguments (missing: "name").');
        $this->artisan('tenant:create', ['fqdn' => 'client1', 'email' => 'bob@mail.com']);
    }
    /** @test */
    public function tenant_email_is_required(): void
    {
        $this->expectExceptionMessage('Not enough arguments (missing: "email").');
        $this->artisan('tenant:create', ['fqdn' => 'client1', 'name' => 'bob']);
    }
    /** @test */
    public function can_create_new_tenant(): void
    {
        $this->artisan('tenant:create', ['fqdn' => 'client1', 'name' => 'example', 'email' => 'test@example.com']);
        $this->assertSystemDatabaseHas('hostnames', ['fqdn' => 'client1.'.env('APP_URL')]);
    }
    /** @test */
    public function tenant_has_admin():void
    {
        $this->artisan('tenant:create', ['fqdn' => 'client1', 'name' => 'example', 'email' => 'test@example.com']);
        $this->assertDatabaseHas('users', ['email' =>  'test@example.com']);
    }
    /** @test */
    public function admin_has_proper_roles(): void
    {
        $this->artisan('tenant:create', ['fqdn' => 'client1', 'name' => 'example', 'email' => 'test@example.com']);
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasPermissionTo('edit user'));
        $this->assertTrue($user->hasPermissionTo('create user'));
        $this->assertTrue($user->hasPermissionTo('delete user'));
    }
    /** @test */
    public function admin_is_invited(): void
    {
        $this->artisan('tenant:create', ['fqdn' => 'client1', 'name' => 'example', 'email' => 'test@example.com']);
        Notification::assertSentTo(User::where('email', 'test@example.com')->get(), TenantCreated::class);
    }
    protected function tearDown(): void
    {
        if ($tenant = Tenant::retrieveBy('example')) {
            $tenant->delete('example');
        }
        parent::tearDown();
    }
}
