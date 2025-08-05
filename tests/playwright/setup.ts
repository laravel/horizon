import { test as setup } from '@playwright/test';

setup('ensure Horizon is accessible', async ({ page }) => {
  // Try to access Horizon
  const response = await page.goto('/horizon', { waitUntil: 'networkidle' });
  
  if (!response || response.status() !== 200) {
    throw new Error(`Horizon is not accessible. Status: ${response?.status()}`);
  }

  // Wait for Vue app to mount
  await page.waitForFunction(() => {
    const app = document.querySelector('#app');
    return app && app.querySelector('.sidebar');
  }, { timeout: 10000 });
});