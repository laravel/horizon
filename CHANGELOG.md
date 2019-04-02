# Release Notes

## [Unreleased](https://github.com/laravel/horizon/compare/v3.1.1...3.0)


## [v3.1.1 (2019-04-02)](https://github.com/laravel/horizon/compare/v3.1.0...v3.1.1)

### Fixed
- Fix failed jobs retrying ([7d28272](https://github.com/laravel/horizon/commit/7d282723792f3dd6d058b8d7b87a18350635c029))


## [v3.1.0](https://github.com/laravel/horizon/compare/v3.0.6...v3.1.0)

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
