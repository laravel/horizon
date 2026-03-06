---
name: configuring-horizon
description: "Configures Laravel Horizon for Redis queue management. Triggered when a user mentions Horizon installation, queue supervisor setup, worker configuration, dashboard authorization, auto-scaling, job monitoring, metrics, tags, or notifications. Also applies when troubleshooting blank metrics, LongWaitDetected alerts, or misconfigured worker processes, even when Horizon is not named explicitly. Applies to any request about queue workers backed by Redis with a monitoring dashboard, or monitoring Laravel jobs."
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

Use `search-docs` for detailed Horizon patterns and documentation covering configuration, supervisors, balancing, dashboard authorization, tags, notifications, metrics, and deployment.

For deeper guidance on specific topics, read the relevant reference file before implementing:

- `references/supervisors.md` covers supervisor blocks, balancing strategies, multi-queue setups, and auto-scaling
- `references/notifications.md` covers LongWaitDetected alerts, failed job notifications, and the `waits` config
- `references/tags.md` covers job tagging, dashboard filtering, and silencing noisy jobs
- `references/metrics.md` covers the blank metrics dashboard, snapshot scheduling, and retention config

## Basic Usage

### Installation

```bash
{{ $assist->artisanCommand('horizon:install') }}
```

### Supervisor Configuration

Define supervisors in `config/horizon.php`. The `environments` array merges into `defaults` and does not replace the whole supervisor block:

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

- Horizon only works with the Redis queue driver. Other drivers such as database and SQS are not supported.
- Redis Cluster is not supported. Horizon requires a standalone Redis connection.
- Always check `config/horizon.php` before making changes to understand the current supervisor and environment configuration.
- The `environments` array overrides only the keys you specify. It merges into `defaults` and does not replace it.
- The timeout chain must be ordered: `retry_after` less than job `timeout` less than supervisor `timeout`. The wrong order causes jobs to be force-killed and re-queued indefinitely.
- The metrics dashboard stays blank until `horizon:snapshot` is scheduled. Running `php artisan horizon` alone does not populate metrics.
- Always use `search-docs` for the latest Horizon documentation rather than relying on this skill alone.
