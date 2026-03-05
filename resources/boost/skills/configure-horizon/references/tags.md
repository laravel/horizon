# Tags & Silencing

## Where to Find It

Search with `search-docs`:
- `"horizon tags"` — tagging API and auto-tagging behaviour
- `"horizon silenced jobs"` — silenced/silenced_tags config options

## What to Watch For

**Eloquent models are auto-tagged — you often don't need to add tags manually.**
If a job's constructor accepts Eloquent model instances, Horizon automatically tags the job with `ModelClass:id` (e.g., `App\Models\User:42`). You can filter by this tag in the dashboard without any code changes to the job. Only add a `tags()` method when you need custom tags beyond what auto-tagging provides.

**`silenced` hides jobs from the completed list — it does not stop them from running.**
Adding a job class to the `silenced` array in `config/horizon.php` removes it from the dashboard's completed jobs view. The job still runs normally. This is purely a dashboard noise-reduction tool, not a way to disable jobs.

**`silenced_tags` works the same way but matches by tag.**
Any job carrying a matching tag string is hidden from completed jobs. Useful when you want to silence a category of jobs (e.g., all jobs tagged `notifications`) rather than specific classes.
