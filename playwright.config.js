import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: false, // Disable full parallelization to prevent race conditions
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 0 : 1, // No retries in CI for faster failure
  workers: 1, // Force single worker to prevent server conflicts
  maxFailures: process.env.CI ? 3 : undefined, // Stop after 3 failures in CI
  reporter: [
    ['html'],
    ['json', { outputFile: 'test-results/results.json' }],
    ['junit', { outputFile: 'test-results/results.xml' }],
    process.env.CI ? ['line'] : ['list'], // Simpler output in CI
  ],
  timeout: process.env.CI ? 30000 : 60000, // Shorter timeout in CI: 30 seconds
  expect: {
    timeout: process.env.CI ? 5000 : 15000, // Shorter timeout in CI: 5 seconds
  },
  
  use: {
    baseURL: process.env.BASE_URL || (process.env.CI ? 'http://localhost:8000' : 'http://127.0.0.1:18001'),
    trace: process.env.CI ? 'retain-on-failure' : 'on-first-retry',
    screenshot: 'only-on-failure',
    actionTimeout: process.env.CI ? 5000 : 15000, // Shorter action timeout in CI: 5 seconds
    navigationTimeout: process.env.CI ? 10000 : 20000, // Shorter navigation timeout in CI: 10 seconds
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    // {
    //   name: 'firefox',
    //   use: { ...devices['Desktop Firefox'] },
    // },
    // {
    //   name: 'webkit',
    //   use: { ...devices['Desktop Safari'] },
    // },
  ],

  // Server management handled by run-e2e-tests.sh script
  // This ensures proper environment isolation
  // Disable built-in server since it interferes with testing environment
  webServer: undefined,
});