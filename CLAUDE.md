# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel Horizon is a dashboard and code-driven configuration system for Laravel-powered Redis/Valkey queues. It provides real-time monitoring of job metrics, throughput, runtime, and failures.

## Essential Commands

### Development
```bash
# Install dependencies
composer install
npm install

# Build frontend assets
npm run build      # Production build
npm run watch      # Watch mode for development

# Run development server
composer serve
```

### Testing
```bash
# Run all tests
composer test

# Run specific test file
vendor/bin/phpunit tests/Feature/YourTestFile.php

# Run specific test method
vendor/bin/phpunit --filter testMethodName

# Run static analysis
composer lint
```

### Working with Horizon
```bash
# Publish Horizon assets (when developing)
php artisan horizon:publish

# Start Horizon
php artisan horizon

# Terminate Horizon
php artisan horizon:terminate
```

## Architecture Overview

### Backend Structure
- **Controllers** (`src/Http/Controllers/`) - API endpoints for the dashboard, all return JSON responses
- **Repositories** (`src/Repositories/`) - Data access layer using Redis/Valkey, implement contracts from `src/Contracts/`
- **Supervisors** (`src/Supervisor*.php`) - Process management for queue workers
- **Events/Listeners** (`src/Events/`, `src/Listeners/`) - Event-driven architecture for job monitoring
- **Console Commands** (`src/Console/`) - CLI commands for managing Horizon

### Frontend Structure
- **Vue 3 SPA** (`resources/js/`) - Single-page application with Vue Router
- **API Client** (`resources/js/api.js`) - Axios-based API communication
- **Components** (`resources/js/components/`) - Reusable Vue components
- **Screens** (`resources/js/screens/`) - Page-level components

### Key Design Patterns
1. **Repository Pattern**: All data access goes through repository contracts
2. **Event-Driven**: Job lifecycle events trigger monitoring and metrics collection
3. **Process Supervision**: Master/Supervisor/Worker hierarchy for queue processing
4. **Service Provider**: `HorizonServiceProvider` registers all bindings and configurations

## Redis/Valkey Integration

The codebase now supports both Redis and Valkey:
- Connection factory in `src/RedisHorizonConnectionFactory.php`
- Valkey-specific logic uses `horizon.valkey_horizon` config
- Tests include both Redis and Valkey scenarios

## Development Notes

### When Adding New Features
1. Check if it affects both Redis and Valkey compatibility
2. Add corresponding tests in both `tests/Feature/` and `tests/Unit/`
3. Update API documentation if adding new endpoints
4. Follow existing repository pattern for data access

### Frontend Development
1. Vue components should follow existing naming patterns (PascalCase)
2. API routes are defined in `routes/web.php` with `/horizon/api` prefix
3. Use existing Chart.js setup for new visualizations
4. Maintain dark/light theme compatibility

### Testing Requirements
1. Redis/Valkey service must be running
2. Tests use SQLite in-memory database
3. Feature tests often require queue worker simulation
4. Controller tests should verify JSON structure

## Configuration

Main configuration file: `config/horizon.php`
- Defines queue connections, supervisors, and environments
- Balance strategies: simple, auto, false
- Memory and time limits for workers
- Metrics and monitoring settings

## Branch Information
- Main branch for PRs: `5.x`
- Current feature branch: `valkey-support`