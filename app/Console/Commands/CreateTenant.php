<?php

namespace App\Console\Commands;

use App\User;
use Hyn\Tenancy\Contracts\Repositories\HostnameRepository;
use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
use Hyn\Tenancy\Environment;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

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

        $hostname = $this->registerTenant($fqdn);
        app(Environment::class)->hostname($hostname);
        // we'll create a random secure password for our to-be admin
        $password = "toto";
        $this->addAdmin($name, $email, $password);
        $this->info("Tenant '{$name}' is created and is now accessible at {$hostname->fqdn}");
        $this->info("Admin {$email} can log in using password {$password}");
    }


    private function tenantExists($fqdn)
    {
        return Hostname::where('fqdn', $fqdn)->exists();
    }

    private function registerTenant($fqdn)
    {
        $website = new Website;
        $website->hostnames();
        app(WebsiteRepository::class)->create($website);

        // associate the website with a hostname
        $hostname = new Hostname;
        $baseUrl = config('APP_URL');
        $hostname->fqdn = "{$fqdn}.housetown";
        app(HostnameRepository::class)->attach($hostname, $website);
        return $hostname;
    }

    private function addAdmin($name, $email, $password)
    {
        $admin = User::create(['name' => $name, 'email' => $email, 'password' => Hash::make($password)]);
        return $admin;
    }
}

