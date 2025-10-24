# Smart routing engine skeleton

## Overview

To advance Sprint 2 we introduced a `SmartRoutingEngine` that orchestrates
country-aware provider selection without modifying existing controllers. The
engine receives a `RoutingContext` describing the transfer request and tries
registered strategies in order until one produces a viable `RouteOption`.

## Strategies

The default order defined in `config/routing.php` reflects the required
DIRECT → CORRESPONDENT → CRYPTO priority:

1. `DirectRouteStrategy` checks whether both a source-side payment provider and
   a destination top-up provider exist. When both bindings are available the
   strategy returns a direct route enriched with provider metadata.
2. `CorrespondentRouteStrategy` falls back to any FX provider registered for the
   source or destination country (or globally) to model partner-led remittance
   paths.
3. `CryptoFallbackStrategy` consults crypto bridge bindings so a stablecoin
   tunnel can be offered when traditional rails are unavailable.

Each strategy relies on the existing `CountryProviderResolver`, ensuring
country modules and admin overrides drive the decision making process.

## Extensibility

The `RoutingServiceProvider` resolves strategy classes from configuration. New
strategies can be appended without touching the engine, enabling country
modules or enterprise deployments to add bespoke rule sets.

Admin tooling can inspect `SmartRoutingEngine::strategyClasses()` to expose the
active evaluation order alongside the metadata returned from each option.
