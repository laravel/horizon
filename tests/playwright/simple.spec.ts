import { test, expect } from '@playwright/test';

test('basic Horizon access', async ({ page }) => {
  // Go to Horizon
  const response = await page.goto('/horizon');
  
  // Check if we get a successful response
  expect(response?.status()).toBeLessThan(400);
  
  // Take a screenshot of whatever we see
  await page.screenshot({ path: 'horizon-test.png', fullPage: true });
  
  // Log the page content for debugging
  const content = await page.content();
  console.log('Page title:', await page.title());
  console.log('Has #app element:', await page.locator('#app').count() > 0);
  console.log('Has .sidebar element:', await page.locator('.sidebar').count() > 0);
});