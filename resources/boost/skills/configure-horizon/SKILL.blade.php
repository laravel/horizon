---
name: configuring-horizon
description: "Configures Laravel Horizon for Redis queue management. Triggered when a user mentions Horizon installation, queue supervisor setup, worker configuration, dashboard authorization, auto-scaling, job monitoring, metrics, tags, or notifications. Also applies when troubleshooting blank metrics, LongWaitDetected alerts, or misconfigured worker processes, even when Horizon is not named explicitly — applies to any request about queue workers backed by Redis with a monitoring dashboard, or monitoring Laravel jobs."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Horizon Configuration

## When to Apply

Activate this skill when:

- Installing or configuring Horizon
- Setting up queue supervisors or worker processes
- Restricting access to the Horizon dashboard
- Configuring auto-scaling or balancing strategies
- Setting up job monitoring, tags, metrics, or notifications
- Troubleshooting blank metrics or LongWaitDetected alerts

## Documentation

Use `search-docs` for detailed Horizon patterns and documentation (configuration, supervisors, balancing, dashboard auth, tags, notifications, metrics, deployment).

For deeper guidance on specific topics, read the relevant reference file before implementing:

- `references/supervisors.md` — supervisor blocks, balancing strategies, multi-queue setups, auto-scaling
- `references/notifications.md` — LongWaitDetected alerts, failed job notifications, `waits` config
- `references/tags.md` — job tagging, dashboard filtering, silencing noisy jobs
- `references/metrics.md` — blank metrics dashboard, snapshot scheduling, retention config

## Basic Usage

### Installation

```bash
{{ $assist->artisanCommand('horizon:install') }}
```

### Supervisor Configuration

Define supervisors in `config/horizon.php`. The `environments` array merges into `defaults` — it does not replace the whole supervisor block:

@boostsnippet("Supervisor Config", "php")
'defaults' => [
    'supervisor-1' => [
        'connection' => 'redis',
        'queue' => ['default'],
        'balance' => 'auto',
        'minProcesses' => 1,
        'maxProcesses' => 10,
        'tries' => 3,
    ],
],

'environments' => [
    'production' => [
        'supervisor-1' => ['maxProcesses' => 10, 'balanceCooldown' => 3],
    ],
    'local' => [
        'supervisor-1' => ['maxProcesses' => 2],
    ],
],
@endboostsnippet

### Dashboard Authorization

Restrict access in `App\Providers\HorizonServiceProvider`:

@boostsnippet("Dashboard Gate", "php")
protected function gate(): void
{
    Gate::define('viewHorizon', function (User $user) {
        return $user->is_admin;
    });
}
@endboostsnippet

## Verification

1. Run `{{ $assist->artisanCommand('horizon') }}` and visit `/horizon`
2. Confirm dashboard access is restricted as expected
3. Check that metrics populate after scheduling `horizon:snapshot`

## Common Pitfalls

- Horizon only works with the Redis queue driver — other drivers (database, SQS, etc.) are not supported
- Redis Cluster is not supported — Horizon requires a standalone Redis connection
- Always check `config/horizon.php` to understand the current supervisor and environment configuration before making changes
- `environments` overrides only the keys you specify; it merges into `defaults`, it does not replace
- Timeout chain must be ordered: `retry_after` < job `timeout` < supervisor `timeout`. Wrong order causes jobs to be force-killed and re-queued indefinitely
- Metrics dashboard stays blank until `horizon:snapshot` is scheduled — `php artisan horizon` alone does not populate metrics
- Not using `search-docs` for the latest Horizon documentation
