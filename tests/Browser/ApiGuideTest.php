<?php

namespace Tests\Browser;

use App\Models\Admin\Admin;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\DuskTestCase;

if (!class_exists(\Laravel\Dusk\Browser::class)) {
    class ApiGuideTest extends \PHPUnit\Framework\TestCase
    {
        public function test_dusk_dependencies_are_missing(): void
        {
            $this->markTestSkipped('Laravel Dusk is not installed.');
        }
    }

    return;
}

use Laravel\Dusk\Browser;

class ApiGuideTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_admin_can_open_api_guide_from_sidebar(): void
    {
        $admin = Admin::factory()->create();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin, 'admin')
                ->visitRoute('admin.dashboard')
                ->click('@sidebar-api-guide')
                ->assertRouteIs('admin.api.guide')
                ->assertSee(__('API Help Center'))
                ->assertSee(__('Postman collection'))
                ->assertPresent('@api-help-search');
        });
    }

    public function test_search_field_filters_categories_client_side(): void
    {
        $admin = Admin::factory()->create();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin, 'admin')
                ->visitRoute('admin.api.guide')
                ->assertPresent('@api-category-authentication')
                ->type('@api-help-search', 'withdrawal')
                ->pause(500)
                ->assertScript("var el = document.querySelector('[dusk=\\'api-category-authentication\\']'); return el ? el.classList.contains('d-none') : false;", true)
                ->assertScript("var el = document.querySelector('[dusk=\\'api-category-withdrawals\\']'); return el ? el.classList.contains('d-none') : false;", false)
                ->assertScript("var empty = document.querySelector('[dusk=\\'api-help-no-results-empty\\']'); return empty ? empty.classList.contains('d-none') : true;", true);
        });
    }
}
