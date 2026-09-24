<?php

namespace Tests\Unit;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_soft_delete_account_termination(): void
    {
        $tenant = Tenant::create([
            'name' => 'Termination Corp',
            'slug' => 'termination-corp',
            'status' => 'active',
        ]);

        $this->assertTrue($tenant->isActive());

        $tenant->terminate();

        $this->assertSoftDeleted('tenants', [
            'id' => $tenant->id,
        ]);
        $this->assertEquals(TenantStatus::Canceled, $tenant->fresh()->status);
        $this->assertFalse($tenant->fresh()->isActive());
    }
}
