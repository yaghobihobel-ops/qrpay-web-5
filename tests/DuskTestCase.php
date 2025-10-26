<?php

namespace Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

if (!class_exists(\Laravel\Dusk\TestCase::class)) {
    abstract class DuskTestCase extends PHPUnitTestCase
    {
        use CreatesApplication;
    }

    return;
}

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions())->addArguments([
            '--disable-gpu',
            '--headless',
            '--window-size=1920,1080',
            '--no-sandbox',
            '--disable-dev-shm-usage',
        ]);

        return RemoteWebDriver::create(
            $_SERVER['DUSK_DRIVER_URL'] ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options)
        );
    }
}
