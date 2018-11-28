<?php

namespace App\Console\Commands;

use App\Notifications\TenantCreated;
use App\Tenant;
use Hyn\Tenancy\Models\Hostname;
use Illuminate\Console\Command;

class CreateTenant extends Command
{
    protected $signature = 'tenant:create {fqdn} {name} {email}';
    protected $description = 'Creates a tenant with the provided fqdn address e.g. php artisan tenant:create sub.example.com';
    public function handle()
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $fqdn = $this->argument('fqdn');

        if ($this->tenantExists($fqdn)) {
            $this->error("A tenant with name '{$fqdn}'");
            return;
        }

        $tenant = Tenant::createFrom($fqdn, $name, $email);

        $this->info("Tenant '{$name}' is created and is now accessible at {$tenant->hostname->fqdn}");
        $tenant->admin->notify(new TenantCreated($tenant->hostname));
        $this->info("Admin {$email} has been invited");
    }


    private function tenantExists($fqdn): bool
    {
        return Hostname::where('fqdn', $fqdn)->exists();
    }
}

