<?php

declare(strict_types=1);

namespace Stancl\Tenancy\Tests;

use Illuminate\Support\Facades\Event;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Events\TenancyEnded;
use Stancl\Tenancy\Events\TenancyInitialized;
use Stancl\Tenancy\Listeners\BootstrapTenancy;
use Stancl\Tenancy\Listeners\RevertToCentralContext;
use Stancl\Tenancy\Tests\Etc\Tenant;

class AutomaticModeTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Event::listen(TenancyInitialized::class, BootstrapTenancy::class);
        Event::listen(TenancyEnded::class, RevertToCentralContext::class);
    }

    /** @test */
    public function context_is_switched_when_tenancy_is_initialized()
    {
        config(['tenancy.bootstrappers' => [
            MyBootstrapper::class,
        ]]);

        $tenant = Tenant::create([
            'id' => 'acme',
        ]);

        tenancy()->initialize($tenant);

        $this->assertSame('acme', app('tenancy_initialized_for_tenant'));
    }

    /** @test */
    public function context_is_reverted_when_tenancy_is_ended()
    {
        $this->context_is_switched_when_tenancy_is_initialized();

        tenancy()->end();

        $this->assertSame(true, app('tenancy_ended'));
    }

    /** @test */
    public function context_is_switched_when_tenancy_is_reinitialized()
    {
        config(['tenancy.bootstrappers' => [
            MyBootstrapper::class,
        ]]);

        $tenant = Tenant::create([
            'id' => 'acme',
        ]);

        tenancy()->initialize($tenant);

        $this->assertSame('acme', app('tenancy_initialized_for_tenant'));

        $tenant2 = Tenant::create([
            'id' => 'foobar',
        ]);

        tenancy()->initialize($tenant2);

        $this->assertSame('foobar', app('tenancy_initialized_for_tenant'));
    }
}

class MyBootstrapper implements TenancyBootstrapper
{
    public function bootstrap(\Stancl\Tenancy\Contracts\Tenant $tenant)
    {
        app()->instance('tenancy_initialized_for_tenant', $tenant->getTenantKey());
    }

    public function revert()
    {
        app()->instance('tenancy_ended', true);
    }
}
