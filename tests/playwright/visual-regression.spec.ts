import { test, expect } from '@playwright/test';

test.describe('Horizon Visual Regression', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to Horizon dashboard
    await page.goto('/horizon', { waitUntil: 'domcontentloaded' });
    // Wait for the sidebar to be visible
    await page.waitForSelector('.sidebar', { state: 'visible' });
    // Wait a bit for any animations to complete
    await page.waitForTimeout(1000);
  });

  test('dashboard screen', async ({ page }) => {
    // Already on dashboard
    await page.waitForSelector('.card-header:has-text("Overview")');
    await expect(page).toHaveScreenshot('dashboard.png', { fullPage: true });
  });

  test('pending jobs screen', async ({ page }) => {
    await page.click('text=Pending Jobs');
    await page.waitForTimeout(1000);
    await expect(page).toHaveScreenshot('pending-jobs.png', { fullPage: true });
  });

  test('completed jobs screen', async ({ page }) => {
    await page.click('text=Completed Jobs');
    await page.waitForTimeout(1000);
    await expect(page).toHaveScreenshot('completed-jobs.png', { fullPage: true });
  });

  test('silenced jobs screen', async ({ page }) => {
    await page.click('text=Silenced Jobs');
    await page.waitForTimeout(1000);
    await expect(page).toHaveScreenshot('silenced-jobs.png', { fullPage: true });
  });

  test('failed jobs screen', async ({ page }) => {
    await page.click('text=Failed Jobs');
    await page.waitForTimeout(1000);
    await expect(page).toHaveScreenshot('failed-jobs.png', { fullPage: true });
  });

  test('batches screen', async ({ page }) => {
    await page.click('text=Batches');
    await page.waitForTimeout(1000);
    await expect(page).toHaveScreenshot('batches-pending.png', { fullPage: true });
  });

  test('batches completed screen', async ({ page }) => {
    await page.click('text=Batches');
    await page.waitForTimeout(500);
    // Click on completed tab if it exists
    const completedTab = page.locator('text=Completed').first();
    if (await completedTab.count() > 0) {
      await completedTab.click();
      await page.waitForTimeout(500);
    }
    await expect(page).toHaveScreenshot('batches-completed.png', { fullPage: true });
  });

  test('metrics jobs screen', async ({ page }) => {
    await page.click('text=Metrics');
    await page.waitForTimeout(1000);
    await expect(page).toHaveScreenshot('metrics-jobs.png', { fullPage: true });
  });

  test('metrics queues screen', async ({ page }) => {
    await page.click('text=Metrics');
    await page.waitForTimeout(500);
    // Click on queues tab if it exists
    const queuesTab = page.locator('text=Queues').first();
    if (await queuesTab.count() > 0) {
      await queuesTab.click();
      await page.waitForTimeout(500);
    }
    await expect(page).toHaveScreenshot('metrics-queues.png', { fullPage: true });
  });

  test('monitoring screen', async ({ page }) => {
    await page.click('text=Monitoring');
    await page.waitForTimeout(1000);
    await expect(page).toHaveScreenshot('monitoring.png', { fullPage: true });
  });

  test('dark mode toggle', async ({ page }) => {
    // Click dark mode toggle - look for the theme toggle button in the header
    const themeToggle = page.locator('.header button').filter({ hasText: /Light|Dark/i }).first();
    if (await themeToggle.count() > 0) {
      await themeToggle.click();
      await page.waitForTimeout(500); // Wait for theme transition
    }
    await expect(page).toHaveScreenshot('dashboard-dark.png', { fullPage: true });
  });
});

test.describe('Horizon Component Interactions', () => {
  test('search functionality', async ({ page }) => {
    await page.goto('/horizon', { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('.sidebar', { state: 'visible' });
    await page.click('text=Pending Jobs');
    await page.waitForTimeout(1000);
    
    // Type in search box if it exists
    const searchBox = page.locator('input[type="text"], input[placeholder*="Search"], input.form-control');
    if (await searchBox.count() > 0) {
      await searchBox.first().fill('test-search');
      await page.waitForTimeout(500); // Wait for debounced search
      await expect(page).toHaveScreenshot('search-results.png', { fullPage: true });
    }
  });

  test('table sorting', async ({ page }) => {
    await page.goto('/horizon', { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('.sidebar', { state: 'visible' });
    await page.click('text=Failed Jobs');
    await page.waitForTimeout(1000);
    
    // Click on a sortable column header if exists
    const sortableHeader = page.locator('th').first();
    if (await sortableHeader.count() > 0) {
      await sortableHeader.click();
      await page.waitForTimeout(500); // Wait for sort
      await expect(page).toHaveScreenshot('sorted-table.png', { fullPage: true });
    }
  });
});