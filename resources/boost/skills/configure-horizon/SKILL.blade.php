---
name: configuring-horizon
description: "Configures Laravel Horizon for Redis queue management. Activate when the user explicitly mentions Horizon by name. Covers Horizon installation, supervisor configuration, worker processes, dashboard authorization, auto-scaling, balancing strategies, job monitoring, metrics, tags, and notifications. Also applies when troubleshooting Horizon-specific issues such as blank metrics, LongWaitDetected alerts, or misconfigured Horizon workers. Do NOT activate for generic queue, Redis, or job monitoring questions that do not mention Horizon."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Horizon Configuration

## When to Apply

Activate this skill when the user explicitly mentions **Horizon** by name. Examples:

- Installing or configuring Horizon
- Setting up Horizon supervisors or worker processes
- Restricting access to the Horizon dashboard
- Configuring Horizon auto-scaling or balancing strategies
- Setting up Horizon job monitoring, tags, metrics, or notifications
- Troubleshooting Horizon blank metrics or LongWaitDetected alerts

Do NOT activate for generic queue workers, Redis configuration, or job monitoring questions that do not mention Horizon.

## Documentation

Use `search-docs` for detailed Horizon patterns and documentation covering configuration, supervisors, balancing, dashboard authorization, tags, notifications, metrics, and deployment.

For deeper guidance on specific topics, read the relevant reference file before implementing:

- `references/supervisors.md` covers supervisor blocks, balancing strategies, multi-queue setups, and auto-scaling
- `references/notifications.md` covers LongWaitDetected alerts, notification routing, and the `waits` config
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
- The timeout chain must be ordered: job `timeout` less than supervisor `timeout` less than `retry_after`. The wrong order can cause jobs to be retried before Horizon finishes timing them out.
- The metrics dashboard stays blank until `horizon:snapshot` is scheduled. Running `php artisan horizon` alone does not populate metrics.
- Always use `search-docs` for the latest Horizon documentation rather than relying on this skill alone.
