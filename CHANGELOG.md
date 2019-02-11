# Release Notes

## [Unreleased](https://github.com/laravel/horizon/compare/v2.1.0...2.0)

## [v2.1.0 (11-02-2019)](https://github.com/laravel/horizon/compare/v2.0.3...v2.1.0)

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

## [v2.0.3 (22-01-2019)](https://github.com/laravel/horizon/compare/v2.0.2...v2.0.3)

### Changed
- Updated overview stats labels on the dashboard ([#461](https://github.com/laravel/horizon/pull/461))
- Use router-link for recent failed jobs ([#466](https://github.com/laravel/horizon/pull/466))

### Fixed
- Check for existence of `stats` before calling toLocaleString ([#469](https://github.com/laravel/horizon/pull/469))

## [v2.0.2 (10-01-2019)](https://github.com/laravel/horizon/compare/v2.0.1...v2.0.2)

### Fixed
- Update outdated compiled assets ([2a420af](https://github.com/laravel/horizon/commit/2a420af4bb3d79785ef7ff7cd27f75a1c027ab19))

## [v2.0.1 (10-01-2019)](https://github.com/laravel/horizon/compare/v2.0.0...v2.0.1)

### Added
- Added memory usage to the config ([#463](https://github.com/laravel/horizon/pull/463))

### Changed
- Format numbers on the stats dashboard for better readability ([#462](https://github.com/laravel/horizon/pull/462))

### Fixed
- Fixed invalid `doctype` declaration ([#448](https://github.com/laravel/horizon/pull/448))

## [v2.0.0 (14-11-2018)](https://github.com/laravel/horizon/compare/v1.4.3...v2.0.0)

### Added
- Added `horizon:install` command  ([#422](https://github.com/laravel/horizon/pull/422))
- Added middleware to the config ([#432](https://github.com/laravel/horizon/pull/432))

### Changed
- Added new application level `HorizonServiceProvider` and authorization method for consistency with Nova and Telescope ([#422](https://github.com/laravel/horizon/pull/422))
