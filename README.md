# Laravel Pipeline vs Darkwood Flow Demo

A small Symfony Console demonstration comparing:

- a **synchronous closure-onion pipeline** inspired by Laravel's `Illuminate\Pipeline`
- **Darkwood Flow**'s event-driven dataflow model with `AmpDriver`

## Requirements

- PHP >= 8.5
- Composer

## Installation

```bash
composer install
```

## Commands

```bash
php bin/console demo:pipeline:sync
php bin/console demo:pipeline:flow
php bin/console demo:pipeline:compare
```

### What the demo does

Five independent tasks flow through the same logical pipeline:

```text
Fetch content → Hash content → Save result
```

Each fetch step simulates I/O latency with controlled delays (0.8s, 0.3s, 0.6s, 0.2s, 0.5s). Content is deterministic in-memory data (`content-for-task-{id}`), not remote HTTP.

Results are saved to `var/demo/task-{id}.json`.

### Expected behavior

**Synchronous pipeline** (`demo:pipeline:sync`):

- Processes one task at a time through a Laravel-style onion (`array_reverse` + `array_reduce`)
- Uses blocking `usleep()` for fetch delays
- Total time ≈ sum of fetch delays (~2.4s) plus negligible hash/save overhead
- Logs are strictly ordered per task

**Darkwood Flow** (`demo:pipeline:flow`):

- Submits five `Ip` packets, then calls `await()`
- Uses `AmpDriver` with `Amp\delay()` — true event-loop timers (not `sleep()`)
- Multiple tasks overlap during fetch delays
- Total time is noticeably lower than the synchronous run
- Logs interleave across tasks

**Compare** (`demo:pipeline:compare`):

Runs both implementations and prints elapsed times, overlap observation, and execution model summary.

### Example output (approximate)

```text
Synchronous pipeline: 2.45 s
Darkwood Flow:        1.10 s
Observed overlap:     yes

Execution model:
- Synchronous: closure onion, one task at a time
- Flow: independent instruction packets driven by AmpDriver (Revolt event loop)
```

## Important note on Fibers and async

PHP Fibers alone do **not** make blocking I/O asynchronous. Darkwood Flow's default `FiberDriver` uses `time()`-based delays with second precision only.

This demo uses **`amphp/amp`** and **`AmpDriver`** so delays are scheduled on the Revolt event loop. That demonstrates genuine timer-based overlap — not parallel threads, and not non-blocking HTTP.

## Tests

```bash
php bin/phpunit
```

Tests verify pipe ordering, short-circuit behavior, identical output between both implementations, and that all five tasks are collected. No timing assertions.

## Further reading

See [`notes/implementation-analysis.md`](notes/implementation-analysis.md) for API findings, driver selection rationale, and known Flow limitations.
