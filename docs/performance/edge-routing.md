# Gateway Edge Geo-Routing Performance Notes

## Overview
This document tracks the latency benchmarking runs for the geo-aware gateway.
The scripted workload exercises the API endpoints that now participate in
edge-level caching and geo-routing headers:

- `GET /api/v1/get/basic/data`
- `GET /api/v1/app-settings`
- `GET /api/v1/app-settings/languages`
- `GET /api/v1/version-info`

Requests are replayed with regional country headers (`SG`, `TR`, `RU`) to
validate routing decisions toward the Singapore, Istanbul, and Moscow edges.

## Running the scenario
1. Boot the API locally on `http://localhost:8000` (for example with
   `php artisan serve --host=0.0.0.0 --port=8000`).
2. Execute the Artillery plan:

   ```bash
   npx artillery run scripts/perf/gateway-artillery.yml --output storage/logs/artillery-gateway.json
   ```

   The output file captures percentile latency, request volume, and error rates
   per phase. Import the JSON into the business intelligence tooling of your
   choice for longitudinal comparison.

## Observations
The CI environment that produced this change set does not have outbound npm
registry access; consequently, `npx artillery` could not be executed during the
run (HTTP 403). The scenario and reporting harness remain ready for use in any
internet-connected environment. When executed, please record the p95 latency per
region and append the summary table below.

| Region      | p50 (ms) | p95 (ms) | Error Rate |
|-------------|----------|----------|------------|
| Singapore   | _pending_| _pending_| _pending_  |
| Istanbul    | _pending_| _pending_| _pending_  |
| Moscow      | _pending_| _pending_| _pending_  |

Document update history:

- **v1.0** – Added geo-routing benchmarking plan and noted current execution
  constraints.
