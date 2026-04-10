const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, '..', 'PROGRESS.md');
let content = fs.readFileSync(filePath, 'utf8');
let changeCount = 0;

function replace(oldStr, newStr, label) {
  if (content.includes(oldStr)) {
    content = content.replace(oldStr, newStr);
    changeCount++;
    console.log('✅ Replaced:', label);
  } else {
    console.log('❌ NOT FOUND:', label);
    // Show context
    const first30 = oldStr.substring(0, 50);
    const idx = content.indexOf(first30);
    if (idx >= 0) {
      console.log('  Partial match at', idx, ':', JSON.stringify(content.slice(idx, idx+80)));
    }
  }
}

// --- 1. Fix "Current Phase" in the header ---
replace(
  '**Current Phase**: Phase 8 - Production Deployment ⏳',
  '**Current Phase**: Phase 8 - Public Storefront APIs + Production Deployment 🚧',
  'Header current phase'
);

// --- 2. Fix Phase 7 status in the phase list at the top ---
replace(
  '8. ✅ **Phase 7**: Storefront Implementation (COMPLETE — Honey Bee client storefront fully built)',
  '8. 🚧 **Phase 7**: Storefront Implementation (UI COMPLETE — API Integration BLOCKED by missing backend public APIs)',
  'Phase list Phase 7 status'
);

// --- 3. Fix "Production Readiness: 40%" percentage note ---
replace(
  '**Production Readiness**: 40% Complete (Phases 8-10 remaining)',
  '**Production Readiness**: 40% Complete\n- ✅ Phases 0-6 complete\n- 🚧 Phase 7 UI complete, API wiring blocked (ZERO public storefront APIs exist)\n- ⏳ Phases 8-10 not started',
  'Production readiness header summary'
);

// --- 4. Fix the Phase 7.1 storefront pages list ---
// em-dash in file is â€" (mojibake for —)
const emDash = '\u00e2\u20ac\u201c'; // â€" = UTF-8 double-encoded em dash
const oldPages = [
  `- [ ] \`/shop\` ${emDash} Product listing page with filters + grid`,
  `- [ ] \`/shop/[slug]\` ${emDash} Product detail page (12-col layout)`,
  `- [ ] \`/our-story\` ${emDash} Brand story page`,
  `- [ ] \`/categories\` ${emDash} Category listing`,
  `- [ ] \`/cart\` / \`/checkout\` ${emDash} Commerce flow`,
].join('\r\n');

const newPages = [
  `- [x] \`/shop\` (\`products/page.tsx\`) ${emDash} UI complete âœ…, hardcoded \`PRODUCTS\` array âŒ (awaiting public API)`,
  `- [x] \`/shop/[slug]\` (\`products/[slug]/page.tsx\`) ${emDash} UI complete âœ…, mock data âŒ`,
  `- [x] \`/our-story\` ${emDash} Static page âœ… (no API needed)`,
  `- [x] \`/cart\` (\`cart/page.tsx\`) ${emDash} UI complete âœ…, hardcoded \`INITIAL_CART\` state âŒ`,
  `- [x] \`/checkout\` (\`checkout/page.tsx\`) ${emDash} UI complete âœ…, hardcoded \`ORDER_ITEMS\` âŒ`,
  `- [x] \`/account\`, \`/orders\` ${emDash} UI structure ready âœ…`,
  `- [x] \`/contact\` ${emDash} Static page âœ…`,
  '',
  '**API Infrastructure (Ready, blocked by backend):**',
  '- âœ… `src/lib/apiClient.ts` â€" Axios with correct base URL + `X-Store-ID` header',
  '- âœ… `src/services/products.ts` â€" `getProducts()`, `getProductBySlug()`, `getCategories()` all defined',
  '- âœ… `.env.local` â€" `NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1`, `NEXT_PUBLIC_STORE_ID=1`',
  '- âŒ Backend public APIs DO NOT EXIST â€" all product/category endpoints are admin-only behind `auth:sanctum`',
  '',
  '**Discovery (April 10, 2026)**: Frontend is 90% wired. Hard-blocked by missing backend public APIs. Page wiring is a SMALL task (~2 days) once APIs exist.',
].join('\r\n');

replace(oldPages, newPages, 'Phase 7.1 pages');

// --- 5. Fix Phase 7 in the "In Progress" section ---
replace(
  '✅ client-honey-bee storefront pages (ALL COMPLETE: homepage, shop, product detail, our-story, cart, account, contact)',
  '🚧 client-honey-bee storefront pages — UI complete across all 10 pages BUT showing mock data (no public API)',
  'Phase 7 in-progress status line'
);

// --- 6. Replace the entire Production Readiness Status section ---
const oldStatus = `### 🚧 Production Readiness Status\r\n\r\n**Complete (Phases 0-5)**:\r\n- ✅ Multi-tenant backend with authentication\r\n- ✅ Product catalog (products, categories, variants, images)\r\n- ✅ Customer management APIs\r\n- ✅ Order management with manual payments\r\n- ✅ Store provisioning (super admin)\r\n- ✅ Admin panel core features (Products, Categories, Orders, Customers, Stores)\r\n- ✅ Storefront template structure\r\n- ✅ Infrastructure and monitoring docs\r\n\r\n**In Progress (Phases 6-7)**:\r\n- 🚧 Dashboard real data integration\r\n- 🚧 Inventory management system\r\n- 🚧 Store settings page\r\n- 🚧 Profile page real data\r\n- ✅ client-honey-bee storefront pages (ALL COMPLETE: homepage, shop, product detail, our-story, cart, account, contact)\r\n\r\n**Pending (Phases 8-10)**:\r\n- ⏳ Production deployment\r\n- ⏳ Comprehensive testing\r\n- ⏳ Launch preparation`;

const newStatus = `### 🚧 Production Readiness Status\r\n\r\n**Complete (Phases 0-6) ✅**:\r\n- ✅ Multi-tenant backend with authentication (phone-first)\r\n- ✅ Product catalog (products, categories, variants, images) — admin API\r\n- ✅ Customer management APIs (admin-side only)\r\n- ✅ Order management with manual payments\r\n- ✅ Store provisioning (super admin)\r\n- ✅ Admin panel — ALL modules: Dashboard, Products, Categories, Orders, Customers, Inventory, Settings, Profile, Stores\r\n- ✅ Export (CSV) and Bulk operations\r\n- ✅ Storefront design system + UI (client-honey-bee: Stitch/Luminous Alchemist, 10 pages designed)\r\n- ✅ Infrastructure docs, monitoring docs, deployment guides\r\n\r\n**Blocked — Needs Backend Work ❌ (Phase 8 P0)**:\r\n- ❌ ZERO public storefront APIs — all product/category endpoints are admin-only behind \`auth:sanctum\`\r\n- ❌ No customer auth for storefront (register/login for shoppers)\r\n- ❌ No cart API\r\n- ❌ No checkout/guest order creation\r\n- 🚧 client-honey-bee pages use hardcoded mock data — API client + services ARE ready, waiting on backend\r\n\r\n**Pending (Phases 8-10) ⏳**:\r\n- ⏳ P0: Public storefront APIs (products, categories, cart, checkout, customer auth) — ~1.5 weeks\r\n- ⏳ P0: Wire storefront pages to real API (small task: replace 5 hardcoded arrays)\r\n- ⏳ P1: Production server deployment + CI/CD pipeline — ~2 weeks\r\n- ⏳ P1: Test coverage (currently ~25%, needs 80%+) + E2E tests — ~2 weeks\r\n- ⏳ P2: Launch documentation + client onboarding runbook — ~1 week`;

replace(oldStatus, newStatus, 'Production Readiness Status section');

// --- 7. Fix Critical path section ---
const oldPath = `**Critical Path to Production**:\r\n1. Complete Phase 6 (Admin Panel) - 3-4 weeks\r\n2. ✅ Phase 7 (Storefront pages) - COMPLETE (Honey Bee storefront fully built)\r\n3. Complete Phases 8-10 (Deploy, Test, Launch) - 5-6 weeks`;
const newPath = `**Critical Path to Production** (from April 10, 2026):\r\n1. ✅ Phase 6 (Admin Panel) - COMPLETE (all 6.x sub-phases done)\r\n2. 🚧 Phase 7/8.0 — Build public storefront APIs (P0 blocker) — 1.5 weeks\r\n3. 🚧 Wire storefront pages to real data — ~2 days (after APIs)\r\n4. ⏳ Phase 8 — Production server, CI/CD — 2 weeks\r\n5. ⏳ Phase 9 — Testing & QA — 2 weeks\r\n6. ⏳ Phase 10 — Launch prep — 1 week\r\n**Estimated launch**: ~7 weeks (by late May 2026)`;
replace(oldPath, newPath, 'Critical path section');

// --- 8. Add Discovery section before the progress chart ---
const chartStart = '## 📊 Overall Progress\n\n```';
const chartStartCRLF = '## \ud83d\udcca Overall Progress\r\n\r\n```';

const discoverySection = `## 🔍 Discovery Audit — April 10, 2026\r\n\r\n**Scope**: Full codebase audit of backend API routes and storefront data integration status.\r\n\r\n### Backend Public API Status\r\n\r\n| API Needed | Status | Notes |\r\n|---|---|---|\r\n| \`GET /v1/public/products\` | ❌ MISSING | \`ProductController\` is admin-only |\r\n| \`GET /v1/public/products/{slug}\` | ❌ MISSING | No public product detail |\r\n| \`GET /v1/public/categories\` | ❌ MISSING | \`CategoryController\` is admin-only |\r\n| Customer register/login (storefront) | ❌ MISSING | No customer auth endpoints |\r\n| Cart (session-based) | ❌ MISSING | No cart controller/routes |\r\n| Guest checkout (create order) | ❌ MISSING | No checkout routes |\r\n\r\n**All 12 existing controllers are admin-only behind \`auth:sanctum\`.** Only 3 public routes exist (admin login, forgot/reset password).\r\n\r\n### client-honey-bee Frontend Status\r\n\r\n| Component | Status | Notes |\r\n|---|---|---|\r\n| \`src/lib/apiClient.ts\` | ✅ Ready | Axios with \`NEXT_PUBLIC_API_URL\` + Store-ID header |\r\n| \`src/services/products.ts\` | ✅ Ready | All methods defined: \`getProducts()\`, \`getProductBySlug()\`, etc. |\r\n| \`src/services/store.ts\` | ✅ Ready | Store config service built |\r\n| \`.env.local\` | ✅ Ready | \`NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1\` |\r\n| \`products/page.tsx\` (shop) | ❌ Mock | Hardcoded \`PRODUCTS\` array with comment "replace with API call" |\r\n| \`cart/page.tsx\` | ❌ Mock | \`INITIAL_CART\` hardcoded via \`useState\` |\r\n| \`checkout/page.tsx\` | ❌ Mock | \`ORDER_ITEMS\` hardcoded array |\r\n| \`page.tsx\` (homepage) | ❌ Mock | \`features\`, \`collections\`, \`favorites\` hardcoded |\r\n\r\n**Conclusion**: Frontend wiring is 90% done. Replacing mock data with real API calls is a **small task** (~2 days). The entire blocker is the **missing backend public APIs**.\r\n\r\n**Next Action**: Assign Phase 8.1 (Public Storefront APIs) to Backend Developer agent.\r\n\r\n---\r\n\r\n`;

if (content.includes(chartStartCRLF)) {
  content = content.replace(chartStartCRLF, discoverySection + chartStartCRLF);
  changeCount++;
  console.log('✅ Added Discovery section');
} else if (content.includes(chartStart)) {
  content = content.replace(chartStart, discoverySection + chartStart);
  changeCount++;
  console.log('✅ Added Discovery section (LF)');
} else {
  console.log('❌ Chart start not found for Discovery section');
}

fs.writeFileSync(filePath, content, 'utf8');
console.log('\nTotal changes:', changeCount);
console.log('PROGRESS.md updated.');
