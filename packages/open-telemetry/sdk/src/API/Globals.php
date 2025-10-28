<?php

namespace OpenTelemetry\API;

use OpenTelemetry\API\Metrics\MeterProvider;
use OpenTelemetry\API\Trace\TracerProvider;

class Globals
{
    private static ?TracerProvider $tracerProvider = null;

    private static ?MeterProvider $meterProvider = null;

    public static function tracerProvider(): TracerProvider
    {
        if (! self::$tracerProvider) {
            self::$tracerProvider = new TracerProvider();
        }

        return self::$tracerProvider;
    }

    public static function setTracerProvider(?TracerProvider $provider): void
    {
        self::$tracerProvider = $provider;
    }

    public static function meterProvider(): MeterProvider
    {
        if (! self::$meterProvider) {
            self::$meterProvider = new MeterProvider();
        }

        return self::$meterProvider;
    }

    public static function setMeterProvider(?MeterProvider $provider): void
    {
        self::$meterProvider = $provider;
    }
}
