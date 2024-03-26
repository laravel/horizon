# Release Notes

## [Unreleased](https://github.com/laravel/horizon/compare/v5.23.2...5.x)

## [v5.23.2](https://github.com/laravel/horizon/compare/v5.23.1...v5.23.2) - 2024-03-23

* [5.x] Make commands lazy by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/horizon/pull/1399

## [v5.23.1](https://github.com/laravel/horizon/compare/v5.23.0...v5.23.1) - 2024-02-20

* Output console error when terminating due to memory usage by [@SanderMuller](https://github.com/SanderMuller) in https://github.com/laravel/horizon/pull/1391

## [v5.23.0](https://github.com/laravel/horizon/compare/v5.22.1...v5.23.0) - 2024-02-12

* [5.x] Improves console output and fixes Carbon v3 support by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/horizon/pull/1387

## [v5.22.1](https://github.com/laravel/horizon/compare/v5.22.0...v5.22.1) - 2024-01-30

* Make the expiration time for temporarily failed jobs configurable by [@alirezadp10](https://github.com/alirezadp10) in https://github.com/laravel/horizon/pull/1375
* Carbon v3 support by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/horizon/pull/1380

## [v5.22.0](https://github.com/laravel/horizon/compare/v5.21.5...v5.22.0) - 2024-01-16

* Add cache check before registering routes by [@serpentblade](https://github.com/serpentblade) in https://github.com/laravel/horizon/pull/1367
* Fix the pagination of recent jobs list by [@kirba](https://github.com/kirba) in https://github.com/laravel/horizon/pull/1366
* [5.x] Laravel v11 support by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/horizon/pull/1369
* Add exit code to horizon:forget command by [@bytestream](https://github.com/bytestream) in https://github.com/laravel/horizon/pull/1370

## [v5.21.5](https://github.com/laravel/horizon/compare/v5.21.4...v5.21.5) - 2023-12-29

* [5.x] Pass event instance to event listeners tag() method by [@mateusjatenee](https://github.com/mateusjatenee) in https://github.com/laravel/horizon/pull/1361

## [v5.21.4](https://github.com/laravel/horizon/compare/v5.21.3...v5.21.4) - 2023-11-23

* Bump axios from 1.3.2 to 1.6.0 by [@dependabot](https://github.com/dependabot) in https://github.com/laravel/horizon/pull/1347
* fix: undefined array key 'queue' by [@TomaszOnePilot](https://github.com/TomaszOnePilot) in https://github.com/laravel/horizon/pull/1351

## [v5.21.3](https://github.com/laravel/horizon/compare/v5.21.2...v5.21.3) - 2023-10-27

- Ensure out of memory events are raised before exiting the process by [@jameshulse](https://github.com/jameshulse) in https://github.com/laravel/horizon/pull/1341

## [v5.21.2](https://github.com/laravel/horizon/compare/v5.21.1...v5.21.2) - 2023-10-17

- Fix the job repository class based on service bindings by [@daison12006013](https://github.com/daison12006013) in https://github.com/laravel/horizon/pull/1335

## [v5.21.1](https://github.com/laravel/horizon/compare/v5.21.0...v5.21.1) - 2023-09-19

- Fixing Bug in Clear Metrics by [@joaopalopes24](https://github.com/joaopalopes24) in https://github.com/laravel/horizon/pull/1319

## [v5.21.0](https://github.com/laravel/horizon/compare/v5.20.1...v5.21.0) - 2023-09-18

- Add `horizon:reset-metrics` command by [@trevorgehman](https://github.com/trevorgehman) in https://github.com/laravel/horizon/pull/1318

## [v5.20.1](https://github.com/laravel/horizon/compare/v5.20.0...v5.20.1) - 2023-09-12

- Use available `batch.processedJobs` value from `Illuminate\Bus\Batch` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/horizon/pull/1315

## [v5.20.0](https://github.com/laravel/horizon/compare/v5.19.2...v5.20.0) - 2023-08-30

- Throws `Laravel\Horizon\Exceptions\ForbiddenException` on unauthorized access by [@joelbutcher](https://github.com/joelbutcher) in https://github.com/laravel/horizon/pull/1308

## [v5.19.2](https://github.com/laravel/horizon/compare/v5.19.1...v5.19.2) - 2023-08-29

- Set 0 as the default runtime of the job by [@AsemAlalami](https://github.com/AsemAlalami) in https://github.com/laravel/horizon/pull/1307

## [v5.19.1](https://github.com/laravel/horizon/compare/v5.19.0...v5.19.1) - 2023-08-09

- Handle non-array parameters for "queue" setting in supervisor config by [@PrinsFrank](https://github.com/PrinsFrank) in https://github.com/laravel/horizon/pull/1299

## [v5.19.0](https://github.com/laravel/horizon/compare/v5.18.0...v5.19.0) - 2023-07-14

- Make sure that missing supervisors are correctly displayed when using multiple environments by [@PrinsFrank](https://github.com/PrinsFrank) in https://github.com/laravel/horizon/pull/1294

## [v5.18.0](https://github.com/laravel/horizon/compare/v5.17.0...v5.18.0) - 2023-06-30

- Fix supervisor reprovisioning by [@PrinsFrank](https://github.com/PrinsFrank) in https://github.com/laravel/horizon/pull/1288
- Display inactive supervisors in dashboard by [@PrinsFrank](https://github.com/PrinsFrank) in https://github.com/laravel/horizon/pull/1286
- Fix autoscaler scaling down below zero when (nr. of queues * minProcesses) is higher than maxProcesses by [@PrinsFrank](https://github.com/PrinsFrank) in https://github.com/laravel/horizon/pull/1292

## [v5.17.0](https://github.com/laravel/horizon/compare/v5.16.1...v5.17.0) - 2023-06-13

- Add events for when supervisors are terminated by @PrinsFrank in https://github.com/laravel/horizon/pull/1282
- Fixed occasional flashing when toggling scheme by @ramonrietdijk in https://github.com/laravel/horizon/pull/1283

## [v5.16.1](https://github.com/laravel/horizon/compare/v5.16.0...v5.16.1) - 2023-05-29

- Mark as deprecated using `night()` and `$useDarkTheme` by @lloricode in https://github.com/laravel/horizon/pull/1279

## [v5.16.0](https://github.com/laravel/horizon/compare/v5.15.1...v5.16.0) - 2023-05-18

- Refreshes tags periodically by @nunomaduro in https://github.com/laravel/horizon/pull/1274
- Respect the prefers-color-scheme setting by @ramonrietdijk in https://github.com/laravel/horizon/pull/1275

## [v5.15.1](https://github.com/laravel/horizon/compare/v5.15.0...v5.15.1) - 2023-04-26

- Fix deprecation errors for PHP 8.2 by @AntonCooper in https://github.com/laravel/horizon/pull/1270

## [v5.15.0](https://github.com/laravel/horizon/compare/v515.0...v5.15.0) - 2023-03-07

- Add cancelled status to batch overview by @markvaneijk in https://github.com/laravel/horizon/pull/1255
- Add autoScalingStrategy option by @cosmastech in https://github.com/laravel/horizon/pull/1254

## [v5.14.2](https://github.com/laravel/horizon/compare/v5.14.1...v5.14.2) - 2023-02-10

### Changed

- Remove Lodash by @jessarcher in https://github.com/laravel/horizon/pull/1246
- Bump axios to v1.x by @ankurk91 in https://github.com/laravel/horizon/pull/1247

## [v5.14.1](https://github.com/laravel/horizon/compare/v5.14.0...v5.14.1) - 2023-02-07

### Fixed

- Fix missing variable use in anonymous function by @joshuaruesweg in https://github.com/laravel/horizon/pull/1244

## [v5.14.0](https://github.com/laravel/horizon/compare/v5.13.0...v5.14.0) - 2023-01-27

### Changed

- Allow any queued class to be silenced by @lioneaglesolutions in https://github.com/laravel/horizon/pull/1241
- A fresh coat of paint by @jessarcher in https://github.com/laravel/horizon/pull/1242

## [v5.13.0](https://github.com/laravel/horizon/compare/v5.12.0...v5.13.0) - 2023-01-17

### Added

- Implement Silenced interface by @driesvints in https://github.com/laravel/horizon/pull/1236

### Fixed

- Increase `perPage` variable for monitored tags by @joshuaruesweg in https://github.com/laravel/horizon/pull/1237

## [v5.12.0](https://github.com/laravel/horizon/compare/v5.11.0...v5.12.0) - 2023-01-09

### Added

- Silenced jobs by @driesvints in https://github.com/laravel/horizon/pull/1232
- Laravel v10 Support by @driesvints in https://github.com/laravel/horizon/pull/1234

## [v5.11.0](https://github.com/laravel/horizon/compare/v5.10.6...v5.11.0) - 2023-01-03

### Changed

- Uses PHP Native Type Declarations 🐘 by @nunomaduro in https://github.com/laravel/horizon/pull/1206

### Fixed

- Fix vue-json-pretty CSS for new classnames by @datpmwork in https://github.com/laravel/horizon/pull/1231

## [v5.10.6](https://github.com/laravel/horizon/compare/v5.10.5...v5.10.6) - 2022-12-14

### Changed

- Add signal option to `horizon:purge` (e.g. `SIGKILL`) by @crishoj in https://github.com/laravel/horizon/pull/1226

## [v5.10.5](https://github.com/laravel/horizon/compare/v5.10.4...v5.10.5) - 2022-11-25

### Fixed

- Ensure Autoscaler never reduces processes to less than 1 by @LasseRafn in https://github.com/laravel/horizon/pull/1221

## [v5.10.4](https://github.com/laravel/horizon/compare/v5.10.3...v5.10.4) - 2022-11-07

### Changed

- Add missing anchor title for clarity by @stevebauman in https://github.com/laravel/horizon/pull/1212

### Fixed

- Set default value for job `$payload['pushedAt']` when retrying by @stevebauman in https://github.com/laravel/horizon/pull/1211

## [v5.10.3](https://github.com/laravel/horizon/compare/v5.10.2...v5.10.3) - 2022-10-14

### Fixed

- Allow deleting of Tags which contain forward slashes (Fixes #1093) by @sgotre in https://github.com/laravel/horizon/pull/1196
- Unnecessary param to refreshStatsPeriodically by @peterlupu in https://github.com/laravel/horizon/pull/1198

## [v5.10.2](https://github.com/laravel/horizon/compare/v5.10.1...v5.10.2) - 2022-09-26

### Changed

- Add exit codes to status command by @mbardelmeijer in https://github.com/laravel/horizon/pull/1191

## [v5.10.1](https://github.com/laravel/horizon/compare/v5.10.0...v5.10.1) - 2022-09-05

### Changed

- Failed job list: Modify job retry tooltip label to include status of last job retry by @Jevz in https://github.com/laravel/horizon/pull/1182

## [v5.10.0](https://github.com/laravel/horizon/compare/v5.9.10...v5.10.0) - 2022-08-11

### Added

- Ability to ignore long waits for queue by @jasonmccreary in https://github.com/laravel/horizon/pull/1172

## [v5.9.10](https://github.com/laravel/horizon/compare/v5.9.9...v5.9.10) - 2022-07-25

### Fixed

- Fallback array for `retried_by` by @driesvints in https://github.com/laravel/horizon/pull/1166

## [v5.9.9](https://github.com/laravel/horizon/compare/v5.9.8...v5.9.9) - 2022-07-12

### Changed

- When register, keep the original Redis options, only overwrite prefix by @nthsky in https://github.com/laravel/horizon/pull/1161

## [v5.9.8](https://github.com/laravel/horizon/compare/v5.9.7...v5.9.8) - 2022-06-08

### Changed

- Predis v2 support by @driesvints in https://github.com/laravel/horizon/commit/71291b2dc8172f46a8a503aee97b80d9a4139cdf

## [v5.9.7](https://github.com/laravel/horizon/compare/v5.9.6...v5.9.7) - 2022-04-21

### Fixed

- Checking the "viewHorizon" gate first by @marcialpaulg in https://github.com/laravel/horizon/pull/1148

## [v5.9.6](https://github.com/laravel/horizon/compare/v5.9.5...v5.9.6) - 2022-04-07

### Fixed

- Revert "Make horizon work with strict content-security-policy" by @driesvints in https://github.com/laravel/horizon/pull/1141

## [v5.9.5](https://github.com/laravel/horizon/compare/v5.9.4...v5.9.5) - 2022-03-31

### Changed

- Make horizon work with strict content-security-policy by @Grldk in https://github.com/laravel/horizon/pull/1138

### Fixed

- Fix for table headers in the Monitoring tab to match the data that is presented to the user by @dylan7778 in https://github.com/laravel/horizon/pull/1137

## [v5.9.4](https://github.com/laravel/horizon/compare/v5.9.3...v5.9.4) - 2022-03-29

### Changed

- change chart.js intersect mode to false by @swist666 in https://github.com/laravel/horizon/pull/1133

## [v5.9.3](https://github.com/laravel/horizon/compare/v5.9.2...v5.9.3) - 2022-03-03

### Fixed

- Forget job timer in case of exception or failed job by @Namoshek in https://github.com/laravel/horizon/pull/1127

## [v5.9.2](https://github.com/laravel/horizon/compare/v5.9.1...v5.9.2) - 2022-02-22

### Fixed

- Clear stopwatch once job is deleted by @taylorotwell in https://github.com/laravel/horizon/commit/69486d5e4fad8b6ee02c97ed0bea39ef241df956

## [v5.9.1](https://github.com/laravel/horizon/compare/v5.9.0...v5.9.1) - 2022-02-15

### Changed

- Add some more defaults to supervisor ([#1122](https://github.com/laravel/horizon/pull/1122))
- added `display=swap` to google font ([#1121](https://github.com/laravel/horizon/pull/1121))

## [v5.9.0](https://github.com/laravel/horizon/compare/v5.8.0...v5.9.0) - 2022-02-08

### Added

- Add exception context on failed jobs ([#1115](https://github.com/laravel/horizon/pull/1115))

## [v5.8.0 (2022-01-12)](https://github.com/laravel/horizon/compare/v5.7.18...v5.8.0)

### Changed

- Laravel 9 Support ([#1112](https://github.com/laravel/horizon/pull/1112))

## [v5.7.18 (2022-01-04)](https://github.com/laravel/horizon/compare/v5.7.17...v5.7.18)

### Changed

- Resolve issue with php strict type ([#1110](https://github.com/laravel/horizon/pull/1110))

## [v5.7.17 (2021-12-07)](https://github.com/laravel/horizon/compare/v5.7.16...v5.7.17)

### Fixed

- Add some PHP 8.1 fixes ([#1100](https://github.com/laravel/horizon/pull/1100))

## [v5.7.16 (2021-11-23)](https://github.com/laravel/horizon/compare/v5.7.15...v5.7.16)

### Changed

- Reduce bundle size ([#1091](https://github.com/laravel/horizon/pull/1091))
- Hide Retry failed jobs button if no jobs failed ([#1097](https://github.com/laravel/horizon/pull/1097))

### Fixed

- Prevent pool to receive more than available processes on scaling ([#1096](https://github.com/laravel/horizon/pull/1096))

## [v5.7.15 (2021-10-26)](https://github.com/laravel/horizon/compare/v5.7.14...v5.7.15)

### Changed

- Use callAfterResolving to register QueueManager ([#1089](https://github.com/laravel/horizon/pull/1089))

## [v5.7.14 (2021-10-12)](https://github.com/laravel/horizon/compare/v5.7.13...v5.7.14)

### Changed

- Alphabetically sort jobs/queues for metrics ([#1083](https://github.com/laravel/horizon/pull/1083))
- Update page title when switching between Pending and Completed views ([#1084](https://github.com/laravel/horizon/pull/1084))

## [v5.7.13 (2021-09-14)](https://github.com/laravel/horizon/compare/v5.7.12...v5.7.13)

### Changed

- Added pushed at to failedJobs ([#0](https://github.com/laravel/horizon/pull/1069))

### Fixed

- Check if `failedJob.reserved_at` is set before making time calculation ([#1067](https://github.com/laravel/horizon/pull/1067))

## [v5.7.12 (2021-08-31)](https://github.com/laravel/horizon/compare/v5.7.11...v5.7.12)

### Changed

- Improve completed jobs UI ([#1064](https://github.com/laravel/horizon/pull/1064))

## [v5.7.11 (2021-08-17)](https://github.com/laravel/horizon/compare/v5.7.10...v5.7.11)

### Changed

- Show paused supervisors on dashboard ([#1052](https://github.com/laravel/horizon/pull/1052))

## [v5.7.10 (2021-08-03)](https://github.com/laravel/horizon/compare/v5.7.9...v5.7.10)

### Changed

- Unified asset publishing ([#1045](https://github.com/laravel/horizon/pull/1045))

## [v5.7.9 (2021-06-08)](https://github.com/laravel/horizon/compare/v5.7.8...v5.7.9)

### Changed

- Use env. to set config domain and path ([#1030](https://github.com/laravel/horizon/pull/1030))

## [v5.7.8 (2021-05-27)](https://github.com/laravel/horizon/compare/v5.7.7...v5.7.8)

### Fixed

- Revert app layout PR ([#1026](https://github.com/laravel/horizon/pull/1026))

## [v5.7.7 (2021-05-18)](https://github.com/laravel/horizon/compare/v5.7.6...v5.7.7)

### Changed

- Move contents of #horizon to separate component ([#1022](https://github.com/laravel/horizon/pull/1022))

## [v5.7.6 (2021-04-27)](https://github.com/laravel/horizon/compare/v5.7.5...v5.7.6)

### Fixed

- Fix encoding ([ef0c45a](https://github.com/laravel/horizon/commit/ef0c45a6691744c3c8491cf6c096bbcefbaf129f), [9e3748e](https://github.com/laravel/horizon/commit/9e3748e4b9871a3ad5095b20873063a9647c959d))

## [v5.7.5 (2021-04-06)](https://github.com/laravel/horizon/compare/v5.7.4...v5.7.5)

### Fixed

- Update syntax highlighting ([#1002](https://github.com/laravel/horizon/pull/1002))
- Fix status for large batches ([58b71c1](https://github.com/laravel/horizon/commit/58b71c188cdbc3dc07ff0ab22861641200334b25))

## [v5.7.4 (2021-03-30)](https://github.com/laravel/horizon/compare/v5.7.3...v5.7.4)

### Changed

- Add Supervisor Rest Option ([#992](https://github.com/laravel/horizon/pull/992))

## [v5.7.3 (2021-03-23)](https://github.com/laravel/horizon/compare/v5.7.2...v5.7.3)

### Changed

- Move booting of services ([#990](https://github.com/laravel/horizon/pull/990))
- Clarify button purpose ([#991](https://github.com/laravel/horizon/pull/991))

## [v5.7.2 (2021-03-16)](https://github.com/laravel/horizon/compare/v5.7.1...v5.7.2)

### Changed

- Throw exception when minProcesses is set to zero ([#988](https://github.com/laravel/horizon/pull/988), [831a5a8](https://github.com/laravel/horizon/commit/831a5a8690595688e2aa396b6215ef682e4280f6))

### Fixed

- Fix checking parent id ([#989](https://github.com/laravel/horizon/pull/989))

## [v5.7.1 (2021-03-09)](https://github.com/laravel/horizon/compare/v5.7.0...v5.7.1)

### Fixed

- Add `--rest` flag to WorkCommand ([fcb9843](https://github.com/laravel/horizon/commit/fcb98431ff4ba0a59ab99f1c6bebe8b7115f328a))

## [v5.7.0 (2021-02-16)](https://github.com/laravel/horizon/compare/v5.6.6...v5.7.0)

### Added

- Show workload also per queue when balancing is disabled ([#966](https://github.com/laravel/horizon/pull/966), [b4e8c6a](https://github.com/laravel/horizon/commit/b4e8c6a460d34efe2843c0d943c0cdea20fb0bba))

## [v5.6.6 (2021-02-09)](https://github.com/laravel/horizon/compare/v5.6.5...v5.6.6)

### Fixed

- Add fallback font ([#964](https://github.com/laravel/horizon/pull/964))

## [v5.6.5 (2021-01-12)](https://github.com/laravel/horizon/compare/v5.6.4...v5.6.5)

### Fixed

- Bump minimum Laravel version ([#954](https://github.com/laravel/horizon/pull/954))

## [v5.6.4 (2021-01-05)](https://github.com/laravel/horizon/compare/v5.6.3...v5.6.4)

### Fixed

- Set `lastPushed` when executing the delayed enqueue closure ([#951](https://github.com/laravel/horizon/pull/951))

## [v5.6.3 (2020-12-22)](https://github.com/laravel/horizon/compare/v5.6.2...v5.6.3)

### Changed

- Considering queue config parameter 'after_commit' ([#948](https://github.com/laravel/horizon/pull/948))

## [v5.6.2 (2020-12-15)](https://github.com/laravel/horizon/compare/v5.6.1...v5.6.2)

### Fixed

- Fix missing tag check in payload ([#945](https://github.com/laravel/horizon/pull/945))

## [v5.6.1 (2020-12-08)](https://github.com/laravel/horizon/compare/v5.6.0...v5.6.1)

### Changed

- Use enqueueUsing when pushing delayed jobs ([#939](https://github.com/laravel/horizon/pull/939))

## [v5.6.0 (2020-12-01)](https://github.com/laravel/horizon/compare/v5.5.0...v5.6.0)

### Added

- Metrics snapshot config proposal and fix for race condition ([#936](https://github.com/laravel/horizon/pull/936), [59221e9](https://github.com/laravel/horizon/commit/59221e9b60eeb1e04f8bedb4954e9f1a24188959))

## [v5.5.0 (2020-11-24)](https://github.com/laravel/horizon/compare/v5.4.0...v5.5.0)

### Added

- Ability to see which masters are paused and only show paused if everything is paused ([#929](https://github.com/laravel/horizon/pull/929), [f9b5aea](https://github.com/laravel/horizon/commit/f9b5aea1c25518c4def4ce3b33a937b1972cd2a4))

### Changed

- Accept array format for exponential backoff ([#926](https://github.com/laravel/horizon/pull/926))

## [v5.4.0 (2020-11-03)](https://github.com/laravel/horizon/compare/v5.3.0...v5.4.0)

### Added

- Add commands to pause and continue supervisors ([#914](https://github.com/laravel/horizon/pull/914))
- Support PHP 8 ([#917](https://github.com/laravel/horizon/pull/917))

### Changed

- Improve wording of error message ([#918](https://github.com/laravel/horizon/pull/918))

### Fixed

- Fix balance false display on dashboard ([88c84ac](https://github.com/laravel/horizon/commit/88c84acd10198b661fa77600187b59cd6505272e))

## [v5.3.0 (2020-10-20)](https://github.com/laravel/horizon/compare/v5.2.1...v5.3.0)

### Added

- Add maintenance notice on dashboard ([#911](https://github.com/laravel/horizon/pull/911))

### Fixed

- Fix delayed until on pending jobs screen ([#907](https://github.com/laravel/horizon/pull/907))
- Add missing force option to `horizon:clear` ([#909](https://github.com/laravel/horizon/pull/909))
- Fix misleading memory limit config ([#908](https://github.com/laravel/horizon/pull/908))
- Fix completed jobs count ([#910](https://github.com/laravel/horizon/pull/910))

## [v5.2.1 (2020-10-06)](https://github.com/laravel/horizon/compare/v5.2.0...v5.2.1)

### Changed

- Add secs to y-axis ticks' for clarity ([#903](https://github.com/laravel/horizon/pull/903))

## [v5.2.0 (2020-09-29)](https://github.com/laravel/horizon/compare/v5.1.0...v5.2.0)

### Added

- Add `horizon:forget` command to delete a failed job ([#896](https://github.com/laravel/horizon/pull/896))

### Fixed

- Fix check deleting failed job is actually failed ([#894](https://github.com/laravel/horizon/pull/894))

## [v5.1.0 (2020-09-22)](https://github.com/laravel/horizon/compare/v5.0.0...v5.1.0)

### Added

- Add ability to see if a failed job is retried and/or a retry ([573e6a8](https://github.com/laravel/horizon/commit/573e6a88dcf0b2798db444b20cde8e9ac4357ee3))
- Add Horizon command to clear queue ([#892](https://github.com/laravel/horizon/pull/892), [05e3a1c](https://github.com/laravel/horizon/commit/05e3a1cc374010e8ffc98c94f9dcd32d93eaaee7))

### Fixed

- Rename variable to match route ([4822955](https://github.com/laravel/horizon/commit/48229555075d3f46d782d77794705c60c9c7b379))

## [v5.0.0 (2020-09-08)](https://github.com/laravel/horizon/compare/v4.3.5...v5.0.0)

### Added

- Add support for setting workers name ([#840](https://github.com/laravel/horizon/pull/840))
- Support batches ([#844](https://github.com/laravel/horizon/pull/844))
- Support worker max-time and max-jobs ([#860](https://github.com/laravel/horizon/pull/860))
- Implement default environment ([#869](https://github.com/laravel/horizon/pull/869))
- Make autoscaling rate configurable ([#874](https://github.com/laravel/horizon/pull/874), [9af71de](https://github.com/laravel/horizon/commit/9af71dea8edc1b3045439c59437b522e7df63277))

### Changed

- Switch from Chronos to Carbon ([#826](https://github.com/laravel/horizon/pull/826))
- Rename `timeoutAt` to `retryUntil` & `delay` to `backoff` ([6d00eb9](https://github.com/laravel/horizon/commit/6d00eb9b80a599d3ac403108b7a8d65629af2c59))
- Bump minimum PHP version to PHP 7.3 ([ca9ddda](https://github.com/laravel/horizon/commit/ca9dddacdf1b08ef5ba494d1a954c79a52c7ab9a))
- Merge tags from payload ([#843](https://github.com/laravel/horizon/pull/843))

### Fixed

- Check if parent is still running ([#881](https://github.com/laravel/horizon/pull/881))

## [v4.3.5 (2020-09-08)](https://github.com/laravel/horizon/compare/v4.3.4...v4.3.5)

### Fixed

- Add try/catch for failed-jobs ([#880](https://github.com/laravel/horizon/pull/880))

## [v4.3.4 (2020-09-01)](https://github.com/laravel/horizon/compare/v4.3.3...v4.3.4)

### Changed

- Unhide horizon command  ([#878](https://github.com/laravel/horizon/pull/878))

### Fixed

- Try catch serialization error ([57903ed](https://github.com/laravel/horizon/commit/57903edbf845e3d401a9d188199d89f2ed203ff1))

## [v4.3.3 (2020-05-26)](https://github.com/laravel/horizon/compare/v4.3.2...v4.3.3)

### Fixed

- Trigger MasterSupervisorDeployed event ([#839](https://github.com/laravel/horizon/pull/839))

## [v4.3.2 (2020-04-28)](https://github.com/laravel/horizon/compare/v4.3.1...v4.3.2)

### Fixed

- Fix pending jobs count ([#832](https://github.com/laravel/horizon/pull/832))

## [v4.3.1 (2020-04-21)](https://github.com/laravel/horizon/compare/v4.3.0...v4.3.1)

### Fixed

- Revert "Prevent `horizon:purge` from killing too many processes" ([#831](https://github.com/laravel/horizon/pull/831))

## [v4.3.0 (2020-04-14)](https://github.com/laravel/horizon/compare/v4.2.1...v4.3.0)

### Changed

- Chronos 2.0 ([#824](https://github.com/laravel/horizon/pull/824))

### Fixed

- Fix Layout issues ([#821](https://github.com/laravel/horizon/pull/821))
- Prevent `horizon:purge` from killing too many processes ([#820](https://github.com/laravel/horizon/pull/820))

## [v4.2.1 (2020-04-02)](https://github.com/laravel/horizon/compare/v4.2.0...v4.2.1)

### Fixed

- Revert dark mode breaking change ([fdfbd5b](https://github.com/laravel/horizon/commit/fdfbd5b2f7bb1dfdf2e40518b263337c07b6b22c))

## [v4.2.0 (2020-03-31)](https://github.com/laravel/horizon/compare/v4.1.0...v4.2.0)

### Added

- Support ramsey/uuid v4 ([#807](https://github.com/laravel/horizon/pull/807))

### Fixed

- Fix for job rows on monitoring tags screen ([#814](https://github.com/laravel/horizon/pull/814), [066ada5](https://github.com/laravel/horizon/commit/066ada5e52f18f1ebec7909f838d43aa2a6cf065))

## [v4.1.0 (2020-03-24)](https://github.com/laravel/horizon/compare/v4.0.2...v4.1.0)

### Added

- Add metrics options and dark mode config ([#795](https://github.com/laravel/horizon/pull/795))

### Fixed

- Align design with Pending/Completed jobs screen ([#802](https://github.com/laravel/horizon/pull/802))

## [v4.0.2 (2020-03-17)](https://github.com/laravel/horizon/compare/v4.0.1...v4.0.2)

### Changed

- Show warning when manifest is outdated ([#783](https://github.com/laravel/horizon/pull/783))

## [v4.0.1 (2020-03-05)](https://github.com/laravel/horizon/compare/v4.0.0...v4.0.1)

### Fixed

- Add margin to retries table and make exceptions stacktrace responsive ([ff625c5](https://github.com/laravel/horizon/commit/ff625c5255cf7bfa186634b660b5ee844d6fe4b7))

## [v4.0.0 (2020-03-03)](https://github.com/laravel/horizon/compare/v3.7.2...v4.0.0)

### Added

- Add 'view details' to tag-jobs screen ([#775](https://github.com/laravel/horizon/pull/775))
- Add separate screen for completed jobs ([#767](https://github.com/laravel/horizon/pull/767))

### Changed

- Bumped minimum dependencies to Laravel 7.0 ([#710](https://github.com/laravel/horizon/pull/710))
- Changed default Redis prefix ([#643](https://github.com/laravel/horizon/pull/643))
- Suggest predis instead of requiring it ([#531](https://github.com/laravel/horizon/pull/531))

## [v3.7.2 (2020-02-25)](https://github.com/laravel/horizon/compare/v3.7.1...v3.7.2)

### Fixed

- Use provided timezone for delay due ([aa69357](https://github.com/laravel/horizon/commit/aa6935737c093c9abae26f7ebe16980b4b7803e9))

## [v3.7.1 (2020-02-18)](https://github.com/laravel/horizon/compare/v3.7.0...v3.7.1)

### Fixed

- Use 'default' connection as a default redis connection ([#765](https://github.com/laravel/horizon/pull/765))

## [v3.7.0 (2020-02-14)](https://github.com/laravel/horizon/compare/v3.6.1...v3.7.0)

### Added

- Ability to see if a job is delayed ([#755](https://github.com/laravel/horizon/pull/755))
- Allow trimming of completed jobs ([#720](https://github.com/laravel/horizon/pull/720))

## [v3.6.1 (2020-02-12)](https://github.com/laravel/horizon/compare/v3.6.0...v3.6.1)

### Fixed

- Fix wrong value for failedJobs periods ([#757](https://github.com/laravel/horizon/pull/757))

## [v3.6.0 (2020-02-04)](https://github.com/laravel/horizon/compare/v3.5.0...v3.6.0)

### Added

- Ability to view job details in recent jobs overview ([#751](https://github.com/laravel/horizon/pull/751))
- Another way for tags displaying & collapsible panels ([#754](https://github.com/laravel/horizon/pull/754))

## [v3.5.0 (2020-01-28)](https://github.com/laravel/horizon/compare/v3.4.7...v3.5.0)

### Added

- Allow Horizon to be used on a subdomain without a subfolder ([#749](https://github.com/laravel/horizon/pull/749))

## [v3.4.7 (2020-01-14)](https://github.com/laravel/horizon/compare/v3.4.6...v3.4.7)

### Fixed

- Revert filtering by tag ([#741](https://github.com/laravel/horizon/pull/741))

## [v3.4.6 (2019-12-30)](https://github.com/laravel/horizon/compare/v3.4.5...v3.4.6)

### Changed

- Reset the retryUntil value ([#736](https://github.com/laravel/horizon/pull/736))

## [v3.4.5 (2019-12-23)](https://github.com/laravel/horizon/compare/v3.4.4...v3.4.5)

### Fixed

- Fix php 7.4 tagged model typed props ([#732](https://github.com/laravel/horizon/pull/732), [025f953](https://github.com/laravel/horizon/commit/025f953b4902a9b0b172e6d5fe19d0809701259e))

### Removed

- Remove unused functions ([#722](https://github.com/laravel/horizon/pull/722))

## [v3.4.4 (2019-12-10)](https://github.com/laravel/horizon/compare/v3.4.3...v3.4.4)

### Fixed

- Scale to the maxProcesses if timeToClearAll is zero ([#718](https://github.com/laravel/horizon/pull/718))
- Handle js `phpunserialize` not working with closures ([9c3a75a](https://github.com/laravel/horizon/commit/9c3a75a0f3cc1a2d1805f48c8aab49469cb4ab33))

## [v3.4.3 (2019-11-19)](https://github.com/laravel/horizon/compare/v3.4.2...v3.4.3)

### Changed

- Set default 'tries' to 1 ([#704](https://github.com/laravel/horizon/pull/704))

## [v3.4.2 (2019-10-21)](https://github.com/laravel/horizon/compare/v3.4.1...v3.4.2)

### Fixed

- Prevent 'memory exhausted' when deleting monitored tag ([#690](https://github.com/laravel/horizon/pull/690), [1532f9c](https://github.com/laravel/horizon/commit/1532f9c32d9739a1886357108fe5c4e1dc9b8e78))
- Set same ttl for tags as same as recent jobs ([#692](https://github.com/laravel/horizon/pull/692))

## [v3.4.1 (2019-10-08)](https://github.com/laravel/horizon/compare/v3.4.0...v3.4.1)

### Fixed

- Fix pagination for recent jobs ([#678](https://github.com/laravel/horizon/pull/678))

## [v3.4.0 (2019-10-01)](https://github.com/laravel/horizon/compare/v3.3.2...v3.4.0)

### Added

- Filter recent jobs by tag ([#665](https://github.com/laravel/horizon/pull/665))

## [v3.3.2 (2019-08-27)](https://github.com/laravel/horizon/compare/v3.3.1...v3.3.2)

### Fixed

- Fix worker command binding ([3b53310](https://github.com/laravel/horizon/commit/3b533104caa299761ce6a1c41438bdab1e2e246f))

## [v3.3.1 (2019-08-20)](https://github.com/laravel/horizon/compare/v3.3.0...v3.3.1)

### Fixed

- Fix autobalancer ([#651](https://github.com/laravel/horizon/pull/651))

## [v3.3.0 (2019-08-13)](https://github.com/laravel/horizon/compare/v3.2.8...v3.3.0)

### Added

- Allow custom dashboard failed jobs metric period ([#644](https://github.com/laravel/horizon/pull/644))

## [v3.2.8 (2019-08-06)](https://github.com/laravel/horizon/compare/v3.2.7...v3.2.8)

### Fixed

- Forcing Vue to use the X-Requested-With header, set to XMLHttpRequest ([#642](https://github.com/laravel/horizon/pull/642))

## [v3.2.7 (2019-07-30)](https://github.com/laravel/horizon/compare/v3.2.6...v3.2.7)

### Changed

- Updated version constraints for Laravel 6.0 ([b547cb2](https://github.com/laravel/horizon/commit/b547cb2a3668d3e83a3bc9ecac3aab67873c330c))

## [v3.2.6 (2019-07-11)](https://github.com/laravel/horizon/compare/v3.2.5...v3.2.6)

### Fixed

- Correct dashboard "Failed Jobs Past 7 Days" metric ([#633](https://github.com/laravel/horizon/pull/633))

## [v3.2.5 (2019-07-02)](https://github.com/laravel/horizon/compare/v3.2.4...v3.2.5)

### Fixed

- Adjust auto scaling to always use the max processes ([#627](https://github.com/laravel/horizon/pull/627))

## [v3.2.4 (2019-06-25)](https://github.com/laravel/horizon/compare/v3.2.3...v3.2.4)

### Fixed

- Custom MasterSupervisor name breaks dashboard ([#619](https://github.com/laravel/horizon/pull/619))

## [v3.2.3 (2019-06-14)](https://github.com/laravel/horizon/compare/v3.2.2...v3.2.3)

### Fixed

- Reverted "Display worker CPU and memory utilization in supervisor list" ([#616](https://github.com/laravel/horizon/pull/616), [#614](https://github.com/laravel/horizon/pull/614))

## [v3.2.2 (2019-06-04)](https://github.com/laravel/horizon/compare/v3.2.1...v3.2.2)

### Changed

- Add app name to dashboard ([#605](https://github.com/laravel/horizon/pull/605))

### Fixed

- Properly format numeric values ([#606](https://github.com/laravel/horizon/pull/606))

## [v3.2.1 (2019-05-21)](https://github.com/laravel/horizon/compare/v3.2.0...v3.2.1)

### Fixed

- Compiled assets ([1dcbb96](https://github.com/laravel/horizon/commit/1dcbb96a5aa1dd7c4e55017782ce981b2f296223))

## [v3.2.0 (2019-05-21)](https://github.com/laravel/horizon/compare/v3.1.2...v3.2.0)

### Added

- Display worker CPU and memory utilization in supervisor list ([#589](https://github.com/laravel/horizon/pull/589))

### Fixed

- Fix for missing first failed job when searching ([#593](https://github.com/laravel/horizon/pull/593))
- Switch to browser timezone ([8ee690a](https://github.com/laravel/horizon/commit/8ee690a763bd4473398a4ff7a303eb5a5a712fdd), [c7a4739](https://github.com/laravel/horizon/commit/c7a4739ba9f2bc89326eba1088e91e16916f8812))

## [v3.1.2 (2019-04-30)](https://github.com/laravel/horizon/compare/v3.1.1...v3.1.2)

### Fixed

- Fix false jobs causing front-end not to display them ([#582](https://github.com/laravel/horizon/pull/582))

## [v3.1.1 (2019-04-02)](https://github.com/laravel/horizon/compare/v3.1.0...v3.1.1)

### Fixed

- Fix failed jobs retrying ([7d28272](https://github.com/laravel/horizon/commit/7d282723792f3dd6d058b8d7b87a18350635c029))

## [v3.1.0 (2019-03-26)](https://github.com/laravel/horizon/compare/v3.0.6...v3.1.0)

### Added

- Add support for Supervisor "nice" option ([#551](https://github.com/laravel/horizon/pull/551), [8d0034b](https://github.com/laravel/horizon/commit/8d0034bd6d72450be8cdba8e874656d3e704306d), [#556](https://github.com/laravel/horizon/pull/556))

## [v3.0.6 (2019-03-28)](https://github.com/laravel/horizon/compare/v3.0.5...v3.0.6)

### Fixed

- Add ability to configure route domain setting ([#550](https://github.com/laravel/horizon/pull/550))

## [v3.0.5 (2019-03-12)](https://github.com/laravel/horizon/compare/v3.0.4...v3.0.5)

### Fixed

- Fix URL for retrying jobs ([#547](https://github.com/laravel/horizon/pull/547))

## [v3.0.4 (2019-03-08)](https://github.com/laravel/horizon/compare/v3.0.3...v3.0.4)

### Added

- Adding `horizon:status` command ([#545](https://github.com/laravel/horizon/pull/545))

## [v3.0.3 (2019-03-06)](https://github.com/laravel/horizon/compare/v3.0.2...v3.0.3)

### Fixed

- Fix recent jobs screen ([1ab5749](https://github.com/laravel/horizon/commit/1ab57492f66014849b51364315c875b7f76bb435))

## [v3.0.2 (2019-03-05)](https://github.com/laravel/horizon/compare/v3.0.1...v3.0.2)

### Fixed

- Compile assets ([57814a0](https://github.com/laravel/horizon/commit/57814a058b5baf53defaf1af813f7f0862331d00))

## [v3.0.1 (2019-03-04)](https://github.com/laravel/horizon/compare/v3.0.0...v3.0.1)

### Fixed

- Fix no failing jobs problem ([#532](https://github.com/laravel/horizon/pull/532))
- Make Year of readableTimestamp more readable ([#535](https://github.com/laravel/horizon/pull/535))
- `Horizon::night()` move to `boot()`([#537](https://github.com/laravel/horizon/pull/537))

### Removed

- Remove unnecessary register method ([7134324](https://github.com/laravel/horizon/commit/7134324d51b3bc917fd8fdb0e3e48f2b7f98475a))

## [v3.0.0 (2019-02-27)](https://github.com/laravel/horizon/compare/v2.2.2...v3.0.0)

### Added

- New UI ([#522](https://github.com/laravel/horizon/pull/522))
- Set the Horizon environment via command option ([#523](https://github.com/laravel/horizon/pull/523))

### Changed

- Require latest symfony/debug version ([72cc3a7](https://github.com/laravel/horizon/commit/72cc3a7e250f1f03c20b0f8e65989a3b0a7d5148))
- Require symfony/process ([f2a214c](https://github.com/laravel/horizon/commit/f2a214c65cf265a5144fb8e8472d991aa8f4e71a))
- Require ext-json ([5a54d27](https://github.com/laravel/horizon/commit/5a54d2710d4a33eb7399ddd8e31b3cbc4dcb1dc0))

### Fixed

- Fix deprecated process calls ([#515](https://github.com/laravel/horizon/pull/515))
- Add missing createPayloadArray arg ([#516](https://github.com/laravel/horizon/pull/516))

### Removed

- Removed support for Laravel 5.5 & 5.6 ([8a92e09](https://github.com/laravel/horizon/commit/8a92e099ecaad6d0a2823a89f860fd8f8fab51bf))

## [v2.2.2 (2019-02-21)](https://github.com/laravel/horizon/compare/v2.2.1...v2.2.2)

### Fixed

- Fix breaking change with `createPayload` method on `RedisQueue` ([b79bb27](https://github.com/laravel/horizon/commit/b79bb2762ed3f234125892c811d3a73d13bf66cc))

## [v2.2.1 (2019-02-15)](https://github.com/laravel/horizon/compare/v2.2.0...v2.2.1)

### Changed

- Adjust configuration for 5.8 default configuration ([f1f830e](https://github.com/laravel/horizon/commit/f1f830e8de84c2827c9e155a6abd5cc4576b498e))

## [v2.2.0 (2019-02-12)](https://github.com/laravel/horizon/compare/v2.1.0...v2.2.0)

### Added

- Laravel 5.8 support ([292bbc1](https://github.com/laravel/horizon/commit/292bbc15ce814ae24e0b47d749631cf45a827bc1))

### Fixed

- Fix Failed Jobs page showing no results when failed jobs do exist ([#511](https://github.com/laravel/horizon/pull/511))

## [v2.1.0 (2019-02-11)](https://github.com/laravel/horizon/compare/v2.0.3...v2.1.0)

### Added

- Let user choose custom env ([#483](https://github.com/laravel/horizon/pull/483))

### Changed

- Expire monitored jobs ([#484](https://github.com/laravel/horizon/pull/484))
- Updated axios to v0.18 ([#491](https://github.com/laravel/horizon/pull/491))
- Updated laravel-mix to v4.0 ([#490](https://github.com/laravel/horizon/pull/490))
- Updated vue-router to v3.0 ([#493](https://github.com/laravel/horizon/pull/493))
- Optimized Horizon SVG logo ([#489](https://github.com/laravel/horizon/pull/489))

### Fixed

- Fix findFailed method ([#478](https://github.com/laravel/horizon/pull/478))
- Fix storing floats in Redis ([#477](https://github.com/laravel/horizon/pull/477))
- Fix incorrect processes count ([#481](https://github.com/laravel/horizon/pull/481))
- Fix jobs per minute over estimation ([#502](https://github.com/laravel/horizon/pull/502))
- Prevent horizontal scrolling in Dashboard supervisors section ([#506](https://github.com/laravel/horizon/pull/506))

## [v2.0.3 (2019-01-22)](https://github.com/laravel/horizon/compare/v2.0.2...v2.0.3)

### Changed

- Updated overview stats labels on the dashboard ([#461](https://github.com/laravel/horizon/pull/461))
- Use router-link for recent failed jobs ([#466](https://github.com/laravel/horizon/pull/466))

### Fixed

- Check for existence of `stats` before calling toLocaleString ([#469](https://github.com/laravel/horizon/pull/469))

## [v2.0.2 (2019-01-10)](https://github.com/laravel/horizon/compare/v2.0.1...v2.0.2)

### Fixed

- Update outdated compiled assets ([2a420af](https://github.com/laravel/horizon/commit/2a420af4bb3d79785ef7ff7cd27f75a1c027ab19))

## [v2.0.1 (2019-01-10)](https://github.com/laravel/horizon/compare/v2.0.0...v2.0.1)

### Added

- Added memory usage to the config ([#463](https://github.com/laravel/horizon/pull/463))

### Changed

- Format numbers on the stats dashboard for better readability ([#462](https://github.com/laravel/horizon/pull/462))

### Fixed

- Fixed invalid `doctype` declaration ([#448](https://github.com/laravel/horizon/pull/448))

## [v2.0.0 (2018-11-14)](https://github.com/laravel/horizon/compare/v1.4.3...v2.0.0)

### Added

- Added `horizon:install` command  ([#422](https://github.com/laravel/horizon/pull/422))
- Added middleware to the config ([#432](https://github.com/laravel/horizon/pull/432))

### Changed

- Added new application level `HorizonServiceProvider` and authorization method for consistency with Nova and Telescope ([#422](https://github.com/laravel/horizon/pull/422))
