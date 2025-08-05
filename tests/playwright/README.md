# Horizon Visual Regression Testing

This directory contains visual regression tests for Laravel Horizon using Playwright. These tests ensure that UI changes don't introduce unintended visual regressions.

## Setup

1. Install dependencies:
   ```bash
   npm install
   ```

2. Install Playwright browsers:
   ```bash
   npx playwright install
   ```

## Running Tests

### Generate baseline snapshots (first run):
```bash
npm run test:visual
```

This will fail the first time as it generates the baseline screenshots.

### Update baseline snapshots:
```bash
npm run test:visual:update
```

### Run visual regression tests:
```bash
npm run test:visual
```

### Run tests with UI mode:
```bash
npm run test:visual:ui
```

## Test Coverage

The visual regression tests cover:

- **Dashboard**: Main overview screen
- **Jobs**: Pending, Completed, and Silenced jobs screens
- **Failed Jobs**: Failed jobs listing
- **Batches**: Pending and Completed batches
- **Metrics**: Jobs and Queues metrics with charts
- **Monitoring**: Monitoring configuration screen
- **Dark Mode**: Theme switching functionality
- **Interactions**: Search and table sorting

## CI Integration

To run visual regression tests in CI:

1. Store baseline snapshots in version control
2. Run tests with `CI=true npm run test:visual`
3. Review any visual differences in the test report

## Comparing TypeScript Version

To compare the TypeScript version with the original:

1. Generate baseline snapshots on the `5.x` branch
2. Switch to `typescript-ui-update` branch
3. Run `npm run test:visual` to compare

Any visual differences will be highlighted in the test report.

## GitHub Actions Integration

### Automatic Testing

Visual regression tests run automatically on:
- Push to main branches (`main`, `master`, `5.x`, `typescript-ui-update`)
- Pull requests to main branches

Failed tests will upload screenshots and diffs as artifacts for review.

### Updating Snapshots

To update snapshots in a pull request:

1. **Via Comment**: Comment `/update-snapshots` on the PR
2. **Via Workflow**: Run the "Update Visual Snapshots" workflow manually from the Actions tab

The workflow will:
- Check out your PR branch
- Run tests with `--update-snapshots`
- Commit and push any changes
- Comment on the PR with the result

### Viewing Test Results

When tests fail in CI:
1. Check the workflow run details
2. Download the `playwright-report` artifact
3. Extract and open `index.html` to see visual diffs
4. Download `visual-snapshots` to see all current snapshots