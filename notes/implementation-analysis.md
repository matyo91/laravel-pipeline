# Implementation Analysis

Findings confirmed from Darkwood Flow source and tests (not from README alone).

## Flow public API

| Topic | Confirmed behavior |
|---|---|
| Default driver | `FiberDriver` in `Flow\Flow\Flow::__construct()` |
| `await()` | Returns `void` — completion barrier only, no aggregated result |
| Multiple IPs | Call `($flow)(new Ip($data))` per packet, then `$flow->await()` once |
| Job chaining | `->fn($job)` appends nodes; each node gets its own `EventDispatcher`, `IpStrategy`, and `AsyncHandler` |
| Result collection | No `Flow::run()` or return value from `await()`; a terminal `->fn()` job must collect results explicitly |

## Driver selection for this demo

**Selected: `AmpDriver` with `amphp/amp:^3.1`**

| Driver | `delay()` implementation | Suitability for sub-second demo |
|---|---|---|
| `FiberDriver` (default) | `time()` + `Fiber::suspend()` loop | Second resolution only — unsuitable for 300ms/800ms delays |
| `AmpDriver` | `Amp\delay($seconds)` via Revolt event loop | Fractional seconds, true timer-based scheduling |
| `ReactDriver` | `React\Async\delay($seconds)` | Same class of solution; not chosen to keep dependencies minimal |

`sleep()`, `usleep()`, blocking HTTP, and `file_get_contents()` on slow URLs are **not** used to claim Flow concurrency. The synchronous pipeline uses `usleep()` intentionally as a blocking stand-in for I/O latency.

## Concurrency model (honest labeling)

- **Synchronous pipeline**: one task completes fetch → hash → save before the next task starts. Closure onion, single-threaded.
- **Darkwood Flow + AmpDriver**: multiple `Ip` packets in flight. `Amp\delay()` yields to the Revolt event loop. Logs should interleave (e.g. task #2 fetch starts before task #1 fetch ends).
- This is **event-loop scheduled overlap**, not parallel OS threads or non-blocking HTTP I/O.

## Result collection

`ResultCollector` is a terminal `->fn()` job that calls `add(SavedResult)` on each completed packet. This limitation is article-relevant: Laravel Pipeline returns a value from `then()`; Flow requires explicit collection.

## Fixture design

No external HTTP. Five deterministic tasks with controlled fetch delays (0.8s, 0.3s, 0.6s, 0.2s, 0.5s). Content is `"content-for-task-{id}"`. Sequential expected fetch time = 2.4s.

## Known Flow limitations

1. `await()` does not return results.
2. `FiberDriver::delay()` uses `time()` — unsuitable for sub-second precision.
3. `Flow::fn()` reads private `$job` / `$errorJob` from other `Flow` instances (legal in PHP, surprising).
4. Symfony `FlowBundle` `services.php` is empty — manual driver wiring required.
5. `darkwood/flow` requires PHP >= 8.5.

## Differences from Laravel Pipeline

| Laravel Pipeline | Darkwood Flow |
|---|---|
| `send($passable)->through($pipes)->then($dest)` | `new Flow($job)->fn(...)` + `($flow)(new Ip($data))` + `await()` |
| Return value from `then()` | Terminal collector job |
| Sync closure onion | Event-driven async nodes |
| Single passable | Multiple independent IPs |
| `$next` short-circuit | No built-in short-circuit |
