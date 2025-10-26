<?php

namespace Tests\Browser;

use Tests\DuskTestCase;

class LocalizedFlowsTest extends DuskTestCase
{
    /** @test */
    public function developer_examples_page_displays_localized_wizard_mount()
    {
        if (!class_exists(\Laravel\Dusk\Browser::class)) {
            $this->markTestSkipped('Laravel Dusk is not installed.');
        }

        $this->browse(function (\Laravel\Dusk\Browser $browser) {
            $browser->visit('/developer/examples')
                ->waitFor('@developer-localized-wizard')
                ->assertPresent('@developer-localized-wizard')
                ->assertAttribute('@developer-localized-wizard', 'data-context', 'add-money');
        });
    }

    /** @test */
    public function developer_examples_page_displays_scenario_explorer_mounts()
    {
        if (!class_exists(\Laravel\Dusk\Browser::class)) {
            $this->markTestSkipped('Laravel Dusk is not installed.');
        }

        $this->browse(function (\Laravel\Dusk\Browser $browser) {
            $browser->visit('/developer/examples')
                ->waitFor('@developer-qr-scenario')
                ->assertAttribute('@developer-qr-scenario', 'data-scenario', 'qr')
                ->assertAttribute('@developer-alipay-scenario', 'data-scenario', 'alipay')
                ->assertAttribute('@developer-bank-scenario', 'data-scenario', 'bankAuth');
        });
    }
}

