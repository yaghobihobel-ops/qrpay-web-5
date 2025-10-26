# Offline & Failover Recovery Scenarios

This document summarises the new offline-first enhancements, automated failover rules, and the simulated chaos experiments that validate recovery paths.

## PWA Offline Transaction Queue

* The **`public/frontend/js/offline-manager.js`** module intercepts write requests while the browser is offline and persists them to `localStorage`.
* A resilient banner (`resources/views/user/layouts/master.blade.php`) surfaces connection health, queued payloads, and a manual *Retry now* control.
* When connectivity is restored, the queue is replayed automatically. A background sync request (`qrpay-offline-sync`) keeps the service worker informed so retries also happen if the tab is not focused.

## Service Worker Caching

* `public/service-worker.js` now precaches the critical shell and switches to a `stale-while-revalidate` strategy for static assets.
* Navigation requests fall back to a lightweight offline response so users remain informed during outages.
* Clients request background sync registration when pending mutations exist, ensuring retries resume after transient network failures.

## Payment Orchestrator Failover

* `PaymentRoute` records may now carry SLA thresholds (`sla_thresholds` JSON column) that describe the minimum acceptable KPIs for a provider.
* `PaymentRouter` enforces these limits: if a provider breaches latency, uptime, or success-rate targets the router transparently promotes the next best route (BluBank, PSP fallback, etc.).
* Providers expose `updateSlaProfile`/`updateKpiMetrics` so monitoring jobs or tests can model degraded behaviour in real time.

## Chaos Simulation Checklist

| Scenario | Steps | Expected Recovery |
| --- | --- | --- |
| **Primary gateway outage** | Force `AlipayAdapter` unavailable (`setAvailability(false)`). | Router selects BluBank route automatically. |
| **SLA degradation** | Lower Alipay uptime/success KPIs below configured thresholds. | Router diverts to BluBank within the same call. |
| **Client offline capture** | Disconnect network, submit a payment form backed by Axios. | Request stored in queue, banner shows pending item. |
| **Deferred replay** | Reconnect network or trigger manual retry. | Queue flushes, banner confirms processed count. |

## Test Execution

Run the orchestration regression suite:

```bash
php artisan test --filter=PaymentRouterTest
```

The suite includes the new chaos scenario that simulates SLA failure and validates automated provider switching.

## Operational Notes

* Queued requests attach the `X-Queued-By: qrpay-offline-manager` header so back-end logs can identify replays.
* Queue entries retry up to three times. Fatal HTTP responses (e.g. validation errors) are surfaced in the banner for manual follow-up.
* To clear residual items manually, execute `window.qrpayOfflineQueue.clear()` from the browser console (useful during support sessions).
