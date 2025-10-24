<?php

namespace App\Contracts\Routing;

use App\Support\Routing\RouteOption;
use App\Support\Routing\RoutingContext;

interface RouteStrategyInterface
{
    /**
     * Attempt to build a route option for the provided context.
     */
    public function evaluate(RoutingContext $context): ?RouteOption;
}
