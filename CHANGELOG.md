# Release Notes

## [Unreleased](https://github.com/laravel/horizon/compare/v3.6.1...master)

### Changed
- Dropped support for Laravel 5.8 ([8216c34](https://github.com/laravel/horizon/commit/8216c34736564998380f97252c117716020aee0a))
- Bumped minimum Symfony dependencies to 4.3 ([2eb9d6d](https://github.com/laravel/horizon/commit/2eb9d6d77acd5e8b42da0a8146c9e8d8105fbd8a))


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
