<?php

namespace Tests;

if (class_exists(\Laravel\Dusk\TestCase::class)) {
    abstract class DuskTestCase extends \Laravel\Dusk\TestCase
    {
        use CreatesApplication;
    }
} else {
    abstract class DuskTestCase extends TestCase
    {
        protected function setUp(): void
        {
            parent::setUp();

            if (!class_exists(\Laravel\Dusk\Browser::class)) {
                $this->markTestSkipped('Laravel Dusk is not installed.');
            }
        }

        protected function browse($callback)
        {
            $this->markTestSkipped('Laravel Dusk is not installed.');
        }
    }
}

