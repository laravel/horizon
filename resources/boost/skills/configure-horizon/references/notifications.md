# Notifications & Alerts

## Where to Find It

Search with `search-docs`:
- `"horizon notifications"` — listener registration and event list
- `"horizon long wait detected"` — LongWaitDetected event details
- `"horizon failed job notification"` — JobFailed listener example

## What to Watch For

**`waits` in `config/horizon.php` controls the LongWaitDetected threshold — not a notification setting.**
The `waits` array (e.g., `'redis:default' => 60`) defines how many seconds a job can wait in a queue before Horizon fires a `LongWaitDetected` event. This is a config file value, not something set in the notification listener. If alerts are firing too often or too late, adjust `waits`, not the listener code.

**`LongWaitDetected` is an event — you must register a listener.**
Horizon fires the event but doesn't send any notification by itself. You register a listener for `LongWaitDetected` (and optionally `JobFailed`) inside the `boot()` method of `App\Providers\HorizonServiceProvider`. The listener receives the event and is responsible for dispatching whatever notification (Mail, Slack, etc.) you want. Fetch the docs for the exact listener signature and example.

**Failed job notifications work differently from queue failures.**
`LongWaitDetected` is about queue depth/wait time. Failed job notifications use the `JobFailed` event, which is also listened to in HorizonServiceProvider. These are two separate listeners with different event classes. Don't conflate them when setting up alerts.
