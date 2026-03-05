# Metrics & Snapshots

## Where to Find It

Search with `search-docs`:
- `"horizon metrics snapshot"` — snapshot command and scheduling
- `"horizon trim snapshots"` — retention configuration

## What to Watch For

**The metrics dashboard is blank until `horizon:snapshot` has run at least once.**
Running `php artisan horizon` does not populate metrics automatically. The metrics graph is built from snapshots — you must schedule `php artisan horizon:snapshot` to run every 5 minutes via Laravel's scheduler. If the dashboard is blank, this is almost always the reason.

**Register the snapshot in the scheduler, not just once manually.**
A single manual run populates the dashboard momentarily but won't keep it updated. Search `"horizon metrics snapshot"` for the exact scheduler registration syntax — it differs slightly between Laravel 10 and 11+.

**`metrics.trim_snapshots` controls how many data points are retained — not a time duration.**
The `trim_snapshots.job` and `trim_snapshots.queue` values in `config/horizon.php` are counts of snapshots to keep, not minutes or hours. With the default of 24 snapshots at 5-minute intervals, that's 2 hours of history. Increase the value to retain more history; the tradeoff is Redis memory usage.
