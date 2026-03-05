# Supervisor & Balancing Configuration

## Where to Find It

Search with `search-docs` before writing any supervisor config — option names and defaults change between Horizon versions:
- `"horizon supervisor configuration"` — full options list
- `"horizon balancing strategies"` — auto, simple, and false modes
- `"horizon autoscaling workers"` — autoScalingStrategy details
- `"horizon environment configuration"` — defaults+environments merge

## What to Watch For

**defaults + environments merge semantics.**
The `defaults` array defines the complete base supervisor config. The `environments` array patches it per environment — only the keys you list are overridden. You do not need to repeat every key in each environment block. A common pattern: define `connection`, `queue`, `balance`, `autoScalingStrategy`, `tries`, and `timeout` in `defaults`; only override `maxProcesses`, `balanceMaxShift`, and `balanceCooldown` in `production`.

**Named supervisors = queue priority.**
Horizon doesn't enforce queue order when using `balance: auto` on a single supervisor — the `queue` array order is ignored for load balancing. If you need true priority (process `notifications` before `default`), use two separately named supervisors: one for the high-priority queue with a higher `maxProcesses`, one for the low-priority queue with a lower cap. Fetch the docs — there's an explicit note about this.

**`balance: false` for dedicated queues with fixed worker counts.**
Auto-balancing is ideal for variable load, but if you have a queue that should always have exactly N workers (e.g., a video-processing queue limited to 2), set `balance: false` and `maxProcesses: 2`. Auto-balancing would scale it up during bursts, which you may not want.

**`balanceCooldown` prevents thrashing.**
When using `balance: auto`, the supervisor can scale up and down rapidly under bursty load. Set `balanceCooldown` (seconds between scaling decisions) to something like 3–5 seconds to smooth this out. `balanceMaxShift` limits how many processes are added or removed per cycle.
