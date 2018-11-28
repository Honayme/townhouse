<?php
namespace Tests\Unit;
use App\Http\Middleware\EnforceTenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
/**
 * @group UnitTests
 */
class EnforceTenancyMiddlewareTest extends TestCase
{
    /** @test */
    public function sets_database_connection_to_tenant(): void
    {
        $this->assertEquals('mysql', Config::get('database.default'));
        $sut = new EnforceTenancy();
        $sut->handle(new Request(), function () {
            // ignore
        });
        $this->assertEquals('tenant', Config::get('database.default'));
    }
    /** @test */
    public function forwards_request(): void
    {
        $sut = new EnforceTenancy();
        $request = new Request();
        $sut->handle($request, function ($forwardedRequest) use ($request, &$isForwarded) {
            $this->assertEquals($request, $forwardedRequest);
        });
    }
    /** @test */
    public function is_before_middleware(): void
    {
        $sut = new EnforceTenancy();
        $request = new Request();
        $sut->handle($request, function () use ($request, &$isForwarded) {
            $this->assertEquals('tenant', Config::get('database.default'));
        });
    }
}
