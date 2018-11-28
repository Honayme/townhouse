<?php
namespace App\Console\Commands;
use App\Tenant;
use Hyn\Tenancy\Contracts\Repositories\HostnameRepository;
use Hyn\Tenancy\Models\Hostname;
use Illuminate\Console\Command;
class DeleteTenant extends Command
{
    protected $signature = 'tenant:delete {fqdn}';
    protected $description = 'Deletes a tenant of the provided fqdn. Only available on the local environment e.g. php artisan tenant:delete boise';
    public function handle()
    {
        // because this is a destructive command, we'll only allow to run this command
        // if you are on the local environment
        if (!(app()->isLocal() || app()->runningUnitTests())) {
            $this->error('This command is only available on the local environment.');
            return;
        }

        $fqdn = $this->argument('fqdn');
        if ($tenant = Tenant::retrieveBy($fqdn)){
            $tenant->delete($fqdn);
            $this->info("Tenant {$fqdn} successfully deleted.");
        }else {
            $this->error('This fqdn doesn\'t exist.');
        }

    }
    private function deleteTenant($fqdn)
    {
        if (!Hostname::where('fqdn', $fqdn)->first()) {
            $this->error('This fqdn doesn\'t exist.');
            return;
        }

        if ($tenant = Hostname::where('fqdn', $fqdn)->firstOrFail()) {
            $tenant->website->delete();
            app(HostnameRepository::class)->delete($tenant, true);
            $this->info("Tenant {$fqdn} successfully deleted.");
        }
    }
}
