// Singleton Playwright browser with per-request isolated contexts

import { chromium, type Browser, type Page } from 'playwright';

let _browser: Browser | null = null;

const LAUNCH_ARGS = [
  '--no-sandbox',
  '--disable-setuid-sandbox',
  '--disable-dev-shm-usage',
  '--disable-gpu',
  '--no-first-run',
  '--no-zygote',
];

const USER_AGENT =
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 ' +
  '(KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

async function getBrowser(): Promise<Browser> {
  if (_browser && _browser.isConnected()) return _browser;
  _browser = await chromium.launch({
    headless: process.env.HEADLESS !== 'false',
    args: LAUNCH_ARGS,
  });
  return _browser;
}

/**
 * Create an isolated page. Caller MUST call `await page.context().close()` when done.
 */
export async function createPage(): Promise<Page> {
  const browser = await getBrowser();
  const ctx = await browser.newContext({
    userAgent: USER_AGENT,
    viewport: { width: 1920, height: 1080 },
    acceptDownloads: false,
    // Block unnecessary resource types to speed up page loads
    ...(process.env.BLOCK_MEDIA === 'true'
      ? {}
      : {}),
  });

  const page = ctx.newPage();

  // Abort resource types that are never needed for extraction
  await (await page).route('**/*.{woff,woff2,ttf,otf,eot}', r => r.abort());

  return page;
}

export async function shutdown(): Promise<void> {
  if (_browser) {
    await _browser.close().catch(() => undefined);
    _browser = null;
  }
}
