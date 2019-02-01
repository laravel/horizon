# Release Notes

## [Unreleased]
### Changed
- Flattened assets directory. ([#492](https://github.com/laravel/horizon/pull/492))
- Updated axios to v0.18. ([#491](https://github.com/laravel/horizon/pull/491))
- Updated laravel-mix to v4.0. ([#490](https://github.com/laravel/horizon/pull/490))
- Updated vue-router to v3.0. ([#493](https://github.com/laravel/horizon/pull/493))
- Optimized Horizon SVG logo. ([#489](https://github.com/laravel/horizon/pull/489))

## [v2.0.3] - 2019-01-22
### Changed
- Updated overview stats labels on the dashboard. ([#461](https://github.com/laravel/horizon/pull/461))
- Use router-link for recent failed jobs. ([#466](https://github.com/laravel/horizon/pull/466))

### Fixed
- Check for existence of `stats` before calling toLocaleString. ([#469](https://github.com/laravel/horizon/pull/469))

## [v2.0.2] - 2019-01-10

## [v2.0.1] - 2019-01-10
### Added
- Added memory usage to the config. ([#463](https://github.com/laravel/horizon/pull/463))

### Changed
- Format numbers on the stats dashboard for better readability. ([#462](https://github.com/laravel/horizon/pull/462))

### Fixed
- Fixed invalid `doctype` declaration. ([#448](https://github.com/laravel/horizon/pull/448))

## [v2.0.0] - 2018-11-14
### Added
- Added `horizon:install` command.  ([#422](https://github.com/laravel/horizon/pull/422))
- Added middleware to the config. ([#432](https://github.com/laravel/horizon/pull/432))
- Added new application level `HorizonServiceProvider` and authorization method for consistency with Nova and Telescope. ([#422](https://github.com/laravel/horizon/pull/422))

[Unreleased]: https://github.com/laravel/horizon/compare/v2.0.3...HEAD
[v2.0.3]: https://github.com/laravel/horizon/compare/v2.0.2...v2.0.3
[v2.0.2]: https://github.com/laravel/horizon/compare/v2.0.1...v2.0.2
[v2.0.1]: https://github.com/laravel/horizon/compare/v2.0.0...v2.0.1
[v2.0.0]: https://github.com/laravel/horizon/compare/v1.4.3...v2.0.0
