<?php

namespace App\Support\Routing;

use App\Contracts\Routing\RouteStrategyInterface;

class SmartRoutingEngine
{
    /**
     * @param array<int, RouteStrategyInterface> $strategies
     */
    public function __construct(
        protected array $strategies = [],
    ) {
    }

    public function findBestRoute(RoutingContext $context): ?RouteOption
    {
        foreach ($this->strategies as $strategy) {
            $option = $strategy->evaluate($context);

            if ($option instanceof RouteOption) {
                return $option;
            }
        }

        return null;
    }

    /**
     * @return array<int, class-string<RouteStrategyInterface>>
     */
    public function strategyClasses(): array
    {
        return array_map(static function (RouteStrategyInterface $strategy) {
            return $strategy::class;
        }, $this->strategies);
    }
}
