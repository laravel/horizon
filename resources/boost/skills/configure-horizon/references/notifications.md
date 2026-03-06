# Notifications & Alerts

## Where to Find It

Search with `search-docs`:
- `"horizon notifications"` for listener registration and the event list
- `"horizon long wait detected"` for LongWaitDetected event details
- `"horizon failed job notification"` for a JobFailed listener example

## What to Watch For

### `waits` in `config/horizon.php` controls the LongWaitDetected threshold, not the notification itself

The `waits` array (e.g., `'redis:default' => 60`) defines how many seconds a job can wait in a queue before Horizon fires a `LongWaitDetected` event. This value is set in the config file, not in the notification listener. If alerts are firing too often or too late, adjust `waits` rather than the listener code.

### `LongWaitDetected` fires an event that requires a registered listener to send notifications

Horizon fires the event but does not send any notification by itself. Register a listener for `LongWaitDetected` (and optionally `JobFailed`) inside the `boot()` method of `App\Providers\HorizonServiceProvider`. The listener receives the event and is responsible for dispatching the appropriate notification such as Mail or Slack. Fetch the docs for the exact listener signature and example.

### Failed job notifications use a separate event from LongWaitDetected

`LongWaitDetected` covers queue depth and wait time. Failed job notifications use the `JobFailed` event, which is also registered in HorizonServiceProvider. These are two separate listeners with different event classes.
