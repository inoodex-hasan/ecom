<?php

namespace Tests\Feature;

use App\Exports\CustomersExport;
use App\Exports\OrdersExport;
use App\Exports\ProductsExport;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AdminExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');
    }

    public function test_can_export_orders_excel_and_csv(): void
    {
        Excel::fake();

        $response = $this->actingAs($this->admin)->get('/admin/orders/export?format=xlsx');
        $response->assertOk();
        Excel::assertDownloaded('orders-export-'.now()->format('Y-m-d').'.xlsx', function (OrdersExport $export) {
            return true;
        });

        $responseCsv = $this->actingAs($this->admin)->get('/admin/orders/export?format=csv');
        $responseCsv->assertOk();
        Excel::assertDownloaded('orders-export-'.now()->format('Y-m-d').'.csv');
    }

    public function test_can_export_products_excel_and_csv(): void
    {
        Excel::fake();

        $response = $this->actingAs($this->admin)->get('/admin/products/export?format=xlsx');
        $response->assertOk();
        Excel::assertDownloaded('products-export-'.now()->format('Y-m-d').'.xlsx', function (ProductsExport $export) {
            return true;
        });

        $responseCsv = $this->actingAs($this->admin)->get('/admin/products/export?format=csv');
        $responseCsv->assertOk();
        Excel::assertDownloaded('products-export-'.now()->format('Y-m-d').'.csv');
    }

    public function test_can_export_customers_excel_and_csv(): void
    {
        Excel::fake();

        $response = $this->actingAs($this->admin)->get('/admin/customers/export?format=xlsx');
        $response->assertOk();
        Excel::assertDownloaded('customers-export-'.now()->format('Y-m-d').'.xlsx', function (CustomersExport $export) {
            return true;
        });

        $responseCsv = $this->actingAs($this->admin)->get('/admin/customers/export?format=csv');
        $responseCsv->assertOk();
        Excel::assertDownloaded('customers-export-'.now()->format('Y-m-d').'.csv');
    }

    public function test_unauthorized_user_cannot_export_data(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/orders/export')->assertForbidden();
        $this->actingAs($user)->get('/admin/products/export')->assertForbidden();
        $this->actingAs($user)->get('/admin/customers/export')->assertForbidden();
    }
}
