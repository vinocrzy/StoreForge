// Script to update PROGRESS.md with discovery findings
const fs = require('fs');
const filePath = 'e:/Projects/StoreForge/PROGRESS.md';
let text = fs.readFileSync(filePath, 'utf8');

function replaceOnce(from, to, label) {
  if (!text.includes(from)) {
    console.log('NOT FOUND:', label);
    return;
  }
  text = text.replace(from, to);
  console.log('OK:', label);
}

// --- Extract exact mojibake chars from file ---
// checkmark emoji line: "- âœ… Multi-tenant"
const checkLine = text.split('\n').find(l => l.includes('Multi-tenant backend'));
const CHECK = checkLine ? checkLine.slice(2, 2 + 4) : ''; // 'âœ…'

// construction emoji: find "ðŸš§"
const constructLine = text.split('\n').find(l => l.includes('IN PROGRESS') && l.includes('Phase 6'));
const CONSTRUCT = constructLine ? constructLine.slice(constructLine.indexOf('Phase 6') - 5, constructLine.indexOf('Phase 6') - 1) : '';

// em dash — extract from a line we know has it
const emDashLine = text.split('\n').find(l => l.includes('Product listing page with filters'));
const EM = emDashLine ? emDashLine.slice(emDashLine.indexOf('shop') + 6, emDashLine.indexOf('shop') + 10) : '';
const ED = EM.trim(); // em dash mojibake: â€"

// clock emoji (â³) — extract from "Production deployment" line if it has one, else use literal 
const clockLine = text.split('\n').find(l => l.includes('Production deployment') && /[\u00e2]/.test(l.slice(0,6)));
const CLOCK = clockLine ? clockLine.slice(2, 2 + 4) : '(pending)';

// cross mark — hardcode known bytes c3a2c29dc592 = âŒ (double-encoded ❌)
const CROSS = '\u00e2\u009d\u008c'; // ❌ double-encoded

console.log('CHECK bytes:', Buffer.from(CHECK).toString('hex'));
console.log('CONSTRUCT bytes:', Buffer.from(CONSTRUCT).toString('hex'));
console.log('EM bytes:', Buffer.from(EM).toString('hex'));
console.log('CLOCK bytes:', Buffer.from(CLOCK).toString('hex'));
console.log('CROSS bytes:', Buffer.from(CROSS).toString('hex'));

// Read the exact segments we want to replace from the file and check
// ===================================================
// 1. Phase 7.1 pages section
// ===================================================
const SHOP_MARKER = '- [ ] `/shop` ';
const shopIdx = text.indexOf(SHOP_MARKER);
if (shopIdx >= 0) {
  // find end of this section (next blank + ### line)
  const sectionEnd = text.indexOf('\r\n\r\n###', shopIdx);
  const oldSection = text.slice(shopIdx, sectionEnd);
  console.log('Phase 7 pages section found, length:', oldSection.length);

  const ED = EM.trim();  // em dash: â€" (already defined above, but re-set locally for clarity)

  const newSection = [
    `- [x] \`/shop\` (\`products/page.tsx\`) ${ED} UI complete ${CHECK.trim()}, hardcoded \`PRODUCTS\` array (awaiting public API)`,
    `- [x] \`/shop/[slug]\` (\`products/[slug]/page.tsx\`) ${ED} UI complete ${CHECK.trim()}, mock data only`,
    `- [x] \`/our-story\` ${ED} Static page ${CHECK.trim()} (no API needed)`,
    `- [x] \`/cart\` (\`cart/page.tsx\`) ${ED} UI complete ${CHECK.trim()}, hardcoded \`INITIAL_CART\` state`,
    `- [x] \`/checkout\` (\`checkout/page.tsx\`) ${ED} UI complete ${CHECK.trim()}, hardcoded \`ORDER_ITEMS\``,
    `- [x] \`/account\`, \`/orders\` ${ED} UI structure ready ${CHECK.trim()}`,
    `- [x] \`/contact\` ${ED} Static page ${CHECK.trim()}`,
    ``,
    `**API Infrastructure (Ready, blocked by missing backend):**`,
    `- ${CHECK.trim()} \`src/lib/apiClient.ts\` ${ED} Axios with correct base URL + \`X-Store-ID\` header`,
    `- ${CHECK.trim()} \`src/services/products.ts\` ${ED} \`getProducts()\`, \`getProductBySlug()\`, \`getCategories()\` all defined`,
    `- ${CHECK.trim()} \`.env.local\` ${ED} \`NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1\`, \`NEXT_PUBLIC_STORE_ID=1\``,
    `- ZERO public backend APIs ${ED} all product/category endpoints are admin-only behind \`auth:sanctum\``,
    ``,
    `**Discovery (April 10, 2026)**: Frontend is 90% wired. Entire blocker is missing backend public APIs. Page wiring is ~2 days work once APIs exist.`,
  ].join('\r\n');

  text = text.slice(0, shopIdx) + newSection + text.slice(shopIdx + oldSection.length);
  console.log('OK: Phase 7.1 pages section');
} else {
  console.log('NOT FOUND: Phase 7 pages section');
}

// ===================================================
// 2. Production Readiness Status block
// ===================================================
const PROD_MARKER = 'Production Readiness Status\r\n';
const prodIdx = text.indexOf(PROD_MARKER);
const prodEnd = text.indexOf('\r\n**Critical Path', prodIdx);
if (prodIdx >= 0 && prodEnd >= 0) {
  const oldProd = text.slice(prodIdx, prodEnd);
  const newProd = [
    `Production Readiness Status`,
    ``,
    `**Complete (Phases 0-6) ${CHECK.trim()}**:`,
    `- ${CHECK.trim()} Multi-tenant backend with authentication (phone-first)`,
    `- ${CHECK.trim()} Product catalog (admin APIs)`,
    `- ${CHECK.trim()} Customer management (admin APIs)`,
    `- ${CHECK.trim()} Order management with manual payments`,
    `- ${CHECK.trim()} Store provisioning (super admin)`,
    `- ${CHECK.trim()} Admin panel ${ED} ALL modules: Dashboard, Products, Categories, Orders, Customers, Inventory, Settings, Profile, Stores`,
    `- ${CHECK.trim()} Export (CSV) and Bulk operations`,
    `- ${CHECK.trim()} Storefront UI ${ED} client-honey-bee 10 pages (Stitch/Luminous Alchemist design)`,
    `- ${CHECK.trim()} Infrastructure docs, monitoring, deployment guides`,
    ``,
    `**Blocked ${ED} P0 Blocker (Phase 8 Public APIs)**:`,
    `- ZERO public storefront APIs ${ED} all product/category endpoints admin-only behind \`auth:sanctum\``,
    `- No customer auth for storefront (register/login for shoppers)`,
    `- No cart API`,
    `- No checkout/guest order creation`,
    `- Storefront pages use hardcoded mock data (API client + services ARE ready)`,
    ``,
    `**Pending (Phases 8-10)**:`,
    `- P0: Public storefront APIs (products, categories, cart, checkout, customer auth) ${ED} ~1.5 weeks`,
    `- P0: Wire storefront pages to real API (replace 5 hardcoded arrays) ${ED} ~2 days`,
    `- P1: Production server + CI/CD pipeline ${ED} ~2 weeks`,
    `- P1: Test coverage (currently ~25%, target 80%+) + E2E tests ${ED} ~2 weeks`,
    `- P2: Launch documentation + client onboarding runbook ${ED} ~1 week`,
    ``,
  ].join('\r\n');

  text = text.slice(0, prodIdx) + newProd + text.slice(prodIdx + oldProd.length);
  console.log('OK: Production Readiness Status block');
} else {
  console.log('NOT FOUND: Production Readiness block; prodIdx=', prodIdx, 'prodEnd=', prodEnd);
}

// ===================================================
// 3. Critical path section
// ===================================================
const CRIT_MARKER = '**Critical Path to Production**:';
const critIdx = text.indexOf(CRIT_MARKER);
const critEnd = text.indexOf('\r\n\r\n**Estimated Production Launch**', critIdx);
if (critIdx >= 0 && critEnd >= 0) {
  const oldCrit = text.slice(critIdx, critEnd);
  const newCrit = [
    `**Critical Path to Production** (from April 10, 2026):`,
    `1. ${CHECK.trim()} Phase 6 (Admin Panel) ${ED} COMPLETE (all 6.x sub-phases done)`,
    `2. P0 NEXT: Build public storefront APIs ${ED} ~1.5 weeks`,
    `3. Wire storefront pages to real data ${ED} ~2 days (after APIs exist)`,
    `4. ${CLOCK.trim()} Phase 8 ${ED} Production server + CI/CD ${ED} ~2 weeks`,
    `5. ${CLOCK.trim()} Phase 9 ${ED} Testing & QA ${ED} ~2 weeks`,
    `6. ${CLOCK.trim()} Phase 10 ${ED} Launch prep ${ED} ~1 week`,
    `**Estimated launch**: ~7 weeks (late May 2026)`,
  ].join('\r\n');

  text = text.slice(0, critIdx) + newCrit + text.slice(critIdx + oldCrit.length);
  console.log('OK: Critical path section');
} else {
  console.log('NOT FOUND: Critical path; critIdx=', critIdx, 'critEnd=', critEnd);
}

// ===================================================
// 4. Fix Estimated Production Launch line
// ===================================================
replaceOnce(
  '**Estimated Production Launch**: Late July 2026 (12-15 weeks from now)',
  '**Estimated Production Launch**: Late May 2026 (~7 weeks from April 10, 2026)',
  'Estimated launch date'
);

// ===================================================
// 5. Add Discovery section before progress chart if not already present
// ===================================================
const CHART_MARKER = 'Overall Progress\r\n\r\n```';
const chartIdx = text.indexOf(CHART_MARKER);
const discoverySectionAlreadyAdded = text.includes('Discovery Audit');

if (chartIdx >= 0 && !discoverySectionAlreadyAdded) {
  const discoverySection = [
    `## Discovery Audit ${ED} April 10, 2026`,
    ``,
    `**Scope**: Full codebase audit of backend API routes and storefront data integration.`,
    ``,
    `### Backend: What Public APIs Exist?`,
    ``,
    `Only 3 unauthenticated routes exist in \`platform/backend/routes/api.php\`:`,
    `- \`POST /v1/auth/login\` (admin only)`,
    `- \`POST /v1/auth/forgot-password\``,
    `- \`POST /v1/auth/reset-password\``,
    ``,
    `**All 12 controllers in \`Api/V1/\`** are admin-only behind \`auth:sanctum\` + \`tenant\` middleware.`,
    `**ZERO public storefront APIs exist.** Products, categories, orders are all admin-gated.`,
    ``,
    `### Storefront: Real API or Mock Data?`,
    ``,
    `| Component | Status |`,
    `|---|---|`,
    `| \`src/lib/apiClient.ts\` | ${CHECK.trim()} Ready (Axios, correct base URL, Store-ID header) |`,
    `| \`src/services/products.ts\` | ${CHECK.trim()} Ready (getProducts, getProductBySlug, getCategories) |`,
    `| \`src/services/store.ts\` | ${CHECK.trim()} Ready |`,
    `| \`.env.local\` | ${CHECK.trim()} Configured (NEXT_PUBLIC_API_URL, NEXT_PUBLIC_STORE_ID=1) |`,
    `| \`products/page.tsx\` | Mock only ${ED} \`PRODUCTS\` hardcoded array, comment: "replace with API call" |`,
    `| \`cart/page.tsx\` | Mock only ${ED} \`INITIAL_CART\` via useState |`,
    `| \`checkout/page.tsx\` | Mock only ${ED} \`ORDER_ITEMS\` hardcoded |`,
    `| \`page.tsx\` (homepage) | Mock only ${ED} features/collections/favorites hardcoded |`,
    ``,
    `**Verdict**: Frontend is 90% wired. Replacing mock arrays with real API calls is ~2 days of work.`,
    `**The only blocker is the missing backend public APIs (Phase 8 P0).**`,
    ``,
    `---`,
    ``,
    ``,
  ].join('\r\n');

  const insertAt = text.lastIndexOf('---\r\n\r\n## ', chartIdx);
  if (insertAt >= 0) {
    const end = insertAt + 5; // after '---\r\n'
    text = text.slice(0, end) + '\r\n' + discoverySection + text.slice(end);
    console.log('OK: Discovery section inserted');
  } else {
    text = text.slice(0, chartIdx - 2) + '\r\n' + discoverySection + text.slice(chartIdx - 2);
    console.log('OK: Discovery section prepended to chart');
  }
} else if (discoverySectionAlreadyAdded) {
  console.log('SKIP: Discovery section already present');
} else {
  console.log('NOT FOUND: Chart marker for Discovery section');
}

fs.writeFileSync(filePath, text, 'utf8');
console.log('\nDone. Total file size:', text.length);
