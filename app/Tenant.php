<?php
namespace App;
use Hyn\Tenancy\Environment;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Support\Facades\Hash;
use Hyn\Tenancy\Contracts\Repositories\HostnameRepository;
use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
/**
 * @property Website website
 * @property Hostname hostname
 * @property User admin
 */
class Tenant
{
    public function __construct( Website $website = null, Hostname $hostname = null, User $admin = null)
    {
        $this->website = $website;
        $this->hostname = $hostname;
        $this->admin = $admin;
    }
    public function delete($fqdn)
    {
        if ($tenant = Hostname::where('fqdn', $fqdn)->firstOrFail()) {
            $tenant->website->delete();
            app(HostnameRepository::class)->delete($tenant, true);
        }
    }
    public static function createFrom($fqdn, $name, $email): Tenant
    {
        // associate the customer with a website
        $website = new Website;
        $website->hostnames();
        app(WebsiteRepository::class)->create($website);

        // associate the website with a hostname
        $hostname = new Hostname;
        $baseUrl = env('APP_URL');
        $hostname->fqdn = "{$fqdn}.{$baseUrl}";
        app(HostnameRepository::class)->attach($hostname, $website);

        // make hostname current
        app(Environment::class)->hostname($hostname);
        $admin = static::makeAdmin($website, $name, $email, "toto");
        return new Tenant($website, $hostname, $admin); // Changer le constructeur plus haut
    }
    private static function makeAdmin($website, $name, $email, $password): User
    {
        $tenancy = app(Environment::class);
        $tenancy->tenant($website); // switches the tenant and reconfigures the app

        $admin = User::create(['name' => $name, 'email' => $email, 'password' => Hash::make($password)]);
        $admin->guard_name = 'web';
        $admin->assignRole('admin');
        return $admin;
    }
    public static function retrieveBy($fqdn): ?Tenant
    {
        //Change this
        if ($hostname = Hostname::where('fqdn', $fqdn)->first()) {
            return new Tenant(null, $hostname);
        }
        return null;
    }
}
