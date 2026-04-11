<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the Honey Bee artisan soap store with collections and products.
 *
 * Creates the store if it does not yet exist, then seeds catalog data.
 * Safe to re-run: skips seeding if catalog data already exists.
 *
 * Images sourced from Pexels CDN (free for commercial use).
 */
class HoneyBeeCatalogSeeder extends Seeder
{
    private const STORE_SLUG = 'honey-bee';

    /** Resolved at runtime from DB, not hardcoded. */
    private int $storeId;

    /**
     * Pexels CDN image URLs mapped to product roles.
     * These are stored as file_path values; ProductImage::getUrlAttribute()
     * detects full URLs and returns them as-is.
     */
    private array $images = [
        // Hero / general soap bars
        'soap_hero'       => 'https://images.pexels.com/photos/8285564/pexels-photo-8285564.jpeg?auto=compress&cs=tinysrgb&w=800',
        // Honey & oat bars
        'honey_oat'       => 'https://images.pexels.com/photos/4041392/pexels-photo-4041392.jpeg?auto=compress&cs=tinysrgb&w=800',
        // Floral / rose soaps
        'rose_soap'       => 'https://images.pexels.com/photos/10853724/pexels-photo-10853724.jpeg?auto=compress&cs=tinysrgb&w=800',
        // Lavender soap
        'lavender_soap'   => 'https://images.pexels.com/photos/7055696/pexels-photo-7055696.jpeg?auto=compress&cs=tinysrgb&w=800',
        // Herbal / botanical bars
        'herbal_soap'     => 'https://images.pexels.com/photos/6694189/pexels-photo-6694189.jpeg?auto=compress&cs=tinysrgb&w=800',
        // Turmeric / brightening
        'turmeric_soap'   => 'https://images.pexels.com/photos/6220707/pexels-photo-6220707.jpeg?auto=compress&cs=tinysrgb&w=800',
        // Eco / charcoal / neem
        'charcoal_soap'   => 'https://images.pexels.com/photos/7263027/pexels-photo-7263027.jpeg?auto=compress&cs=tinysrgb&w=800',
        // Botanical flat lay
        'botanical'       => 'https://images.pexels.com/photos/3699859/pexels-photo-3699859.jpeg?auto=compress&cs=tinysrgb&w=800',
        // Dried herbal ingredients
        'dried_herbs'     => 'https://images.pexels.com/photos/6694166/pexels-photo-6694166.jpeg?auto=compress&cs=tinysrgb&w=800',
        // Artisan soap making / process
        'soap_making'     => 'https://images.pexels.com/photos/7262930/pexels-photo-7262930.jpeg?auto=compress&cs=tinysrgb&w=800',
        // Goat milk soap
        'goat_milk'       => 'https://images.pexels.com/photos/8285641/pexels-photo-8285641.jpeg?auto=compress&cs=tinysrgb&w=800',
        // Oat milk soap
        'oat_milk'        => 'https://images.pexels.com/photos/5216832/pexels-photo-5216832.jpeg?auto=compress&cs=tinysrgb&w=800',
        // Gift set / packaging
        'gift_set'        => 'https://images.pexels.com/photos/7693203/pexels-photo-7693203.jpeg?auto=compress&cs=tinysrgb&w=800',
        // Spa ritual set
        'spa_ritual'      => 'https://images.pexels.com/photos/1706795033849-7ca391f007c5?w=800&q=80&fm=jpg',
        // Floral soap bar (marble)
        'floral_marble'   => 'https://images.pexels.com/photos/6765188/pexels-photo-6765188.jpeg?auto=compress&cs=tinysrgb&w=800',
    ];

    public function run(): void
    {
        // Ensure the Honey Bee store exists before seeding catalog data.
        $store = Store::updateOrCreate(
            ['slug' => self::STORE_SLUG],
            [
                'name'     => 'Honey Bee Artisan Soaps',
                'domain'   => env('HB_STORE_DOMAIN', 'honey-bee.localhost'),
                'status'   => 'active',
                'email'    => env('HB_STORE_EMAIL', 'hello@honeybeesoaps.com'),
                'phone'    => env('HB_STORE_PHONE', '+12025550200'),
                'address'  => [
                    'street'  => '12 Apiary Lane',
                    'city'    => 'Portland',
                    'state'   => 'OR',
                    'country' => 'USA',
                    'zip'     => '97201',
                ],
                'currency' => 'USD',
                'timezone' => 'America/Los_Angeles',
                'language' => 'en',
                'settings' => [
                    'theme'     => 'honey-bee',
                    'logo_text' => 'Honey Bee',
                    'tagline'   => 'Artisan soaps crafted with love and beeswax.',
                ],
            ]
        );

        $this->storeId = $store->id;

        // Idempotency guard — skip if catalog data already exists.
        if (Category::withoutGlobalScopes()->where('store_id', $this->storeId)->exists()) {
            $this->command->info("⏭  Honey Bee catalog already seeded (store #{$this->storeId}). Skipping.");
            return;
        }

        $this->command->info('🍯 Seeding Honey Bee catalog (store #' . $this->storeId . ')...');

        $categoryMap = $this->seedCollections();
        $this->seedProducts($categoryMap);

        $catCount  = Category::withoutGlobalScopes()->where('store_id', $this->storeId)->count();
        $prodCount = Product::withoutGlobalScopes()->where('store_id', $this->storeId)->count();
        $this->command->info("✅ Honey Bee: {$catCount} collections, {$prodCount} products seeded.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // COLLECTIONS (Categories)
    // ──────────────────────────────────────────────────────────────────────────

    private function seedCollections(): array
    {
        $map = [];

        $structure = [
            [
                'name'        => 'Honey & Beeswax',
                'slug'        => 'honey-beeswax',
                'description' => 'Pure honey and beeswax artisan soaps — rich, moisturising and naturally antibacterial.',
                'image'       => $this->images['honey_oat'],
                'children'    => [
                    ['name' => 'Pure Honey Soaps',   'slug' => 'pure-honey-soaps',   'description' => 'Single-origin honey soap bars, cold-process crafted.'],
                    ['name' => 'Honey & Oat Bars',   'slug' => 'honey-oat-bars',     'description' => 'Soothing oat and honey combinations for sensitive skin.'],
                ],
            ],
            [
                'name'        => 'Botanical Collection',
                'slug'        => 'botanical-collection',
                'description' => 'Plant-powered soaps infused with real herbs, florals, and essential oils.',
                'image'       => $this->images['dried_herbs'],
                'children'    => [
                    ['name' => 'Floral Soaps',  'slug' => 'floral-soaps',  'description' => 'Rose, lavender, chamomile and other petal-rich bars.'],
                    ['name' => 'Herbal Soaps',  'slug' => 'herbal-soaps',  'description' => 'Rosemary, mint, tea tree and therapeutic herbal blends.'],
                ],
            ],
            [
                'name'        => 'Therapeutic Range',
                'slug'        => 'therapeutic-range',
                'description' => 'Targeted formulations for specific skin concerns.',
                'image'       => $this->images['botanical'],
                'children'    => [
                    ['name' => 'Face Care',      'slug' => 'face-care',      'description' => 'Gentle face bars with active botanicals and anti-age compounds.'],
                    ['name' => 'Sensitive Skin', 'slug' => 'sensitive-skin', 'description' => 'Fragrance-free, hypoallergenic bars for delicate skin.'],
                ],
            ],
            [
                'name'        => 'Gift Sets',
                'slug'        => 'gift-sets',
                'description' => 'Curated gift collections — perfect for any occasion.',
                'image'       => $this->images['gift_set'],
                'children'    => [
                    ['name' => 'Discovery Sets', 'slug' => 'discovery-sets', 'description' => 'Try-before-you-commit multi-bar sampler sets.'],
                    ['name' => 'Luxury Gifts',   'slug' => 'luxury-gifts',   'description' => 'Premium spa gift sets beautifully presented.'],
                ],
            ],
        ];

        $parentSort = 0;
        foreach ($structure as $parent) {
            $parentCat = Category::create([
                'store_id'    => $this->storeId,
                'parent_id'   => null,
                'name'        => $parent['name'],
                'slug'        => $parent['slug'],
                'description' => $parent['description'],
                'image'       => $parent['image'],
                'sort_order'  => $parentSort++,
                'is_active'   => true,
            ]);
            $map[$parent['slug']] = $parentCat->id;

            $childSort = 0;
            foreach ($parent['children'] as $child) {
                $childCat = Category::create([
                    'store_id'    => $this->storeId,
                    'parent_id'   => $parentCat->id,
                    'name'        => $child['name'],
                    'slug'        => $child['slug'],
                    'description' => $child['description'],
                    'sort_order'  => $childSort++,
                    'is_active'   => true,
                ]);
                $map[$child['slug']] = $childCat->id;
            }
        }

        return $map;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRODUCTS
    // ──────────────────────────────────────────────────────────────────────────

    private function seedProducts(array $categoryMap): void
    {
        $products = $this->productDefinitions();

        foreach ($products as $def) {
            $price = $def['price'];

            $product = Product::create([
                'store_id'            => $this->storeId,
                'name'                => $def['name'],
                'slug'                => Str::slug($def['name']),
                'sku'                 => $def['sku'],
                'description'         => $def['description'],
                'short_description'   => $def['short_description'],
                'price'               => $price,
                'compare_price'       => $def['compare_price'] ?? null,
                'cost_price'          => round($price * 0.45, 2),
                'track_inventory'     => true,
                'stock_quantity'      => $def['stock'] ?? rand(15, 80),
                'low_stock_threshold' => 5,
                'weight'              => $def['weight'] ?? 0.15,
                'weight_unit'         => 'kg',
                'dimensions'          => ['length' => 9, 'width' => 6, 'height' => 3, 'unit' => 'cm'],
                'status'              => $def['status'] ?? 'active',
                'is_featured'         => $def['is_featured'] ?? false,
                'meta_title'          => $def['name'] . ' | Honey Bee Artisan Soaps',
                'meta_description'    => $def['short_description'],
            ]);

            // Attach collections
            $catIds = array_map(fn($slug) => $categoryMap[$slug] ?? null, $def['categories']);
            $product->categories()->attach(array_filter($catIds));

            // Primary image
            ProductImage::create([
                'product_id' => $product->id,
                'store_id'   => $this->storeId,
                'file_path'  => $def['image'],
                'alt_text'   => $def['name'],
                'sort_order' => 0,
                'is_primary' => true,
            ]);

            // Secondary image (if provided)
            if (!empty($def['image2'])) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'store_id'   => $this->storeId,
                    'file_path'  => $def['image2'],
                    'alt_text'   => $def['name'] . ' – detail',
                    'sort_order' => 1,
                    'is_primary' => false,
                ]);
            }

            // Variants (scent/size) where applicable
            if (!empty($def['variants'])) {
                foreach ($def['variants'] as $i => $variant) {
                    ProductVariant::create([
                        'product_id'     => $product->id,
                        'store_id'       => $this->storeId,
                        'name'           => $variant['name'],
                        'sku'            => $def['sku'] . '-' . Str::upper(Str::slug($variant['name'])),
                        'price'          => $variant['price'] ?? null,
                        'compare_price'  => null,
                        'stock_quantity' => rand(5, 30),
                        'attributes'     => $variant['attributes'],
                        'image'          => null,
                        'is_active'      => true,
                    ]);
                }
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRODUCT DEFINITIONS (25 products)
    // ──────────────────────────────────────────────────────────────────────────

    private function productDefinitions(): array
    {
        $img   = $this->images;
        $soap  = $img['soap_hero'];

        return [
            // ── HONEY & OAT ──────────────────────────────────────────────────
            [
                'name'              => 'Wild Honey & Oat Bar',
                'sku'               => 'HB-HOA-001',
                'price'             => 14.50,
                'compare_price'     => null,
                'stock'             => 60,
                'is_featured'       => true,
                'categories'        => ['pure-honey-soaps', 'honey-oat-bars'],
                'image'             => $img['honey_oat'],
                'image2'            => $img['oat_milk'],
                'short_description' => 'Raw wildflower honey blended with colloidal oat for deeply nourishing cleanse.',
                'description'       => "Our Wild Honey & Oat Bar is a celebration of nature's finest ingredients. We blend locally sourced wildflower honey with finely milled colloidal oats to create a bar that cleanses, exfoliates gently, and locks in moisture. Cold-process crafted in small batches, this bar is suitable for all skin types, including sensitive and dry skin.\n\nIngredients: Saponified olive oil, coconut oil, palm oil (RSPO certified), wildflower honey, colloidal oatmeal, shea butter, vitamin E.\n\nNet weight: 130 g | Cured for 6 weeks.",
                'weight'            => 0.13,
                'variants'          => [
                    ['name' => 'Single Bar',    'price' => null,  'attributes' => ['size' => 'single']],
                    ['name' => 'Twin Pack',     'price' => 26.00, 'attributes' => ['size' => 'twin']],
                    ['name' => 'Value 4-Pack',  'price' => 48.00, 'attributes' => ['size' => '4-pack']],
                ],
            ],
            [
                'name'              => 'Manuka Honey Cleansing Bar',
                'sku'               => 'HB-MHC-002',
                'price'             => 18.00,
                'compare_price'     => 22.00,
                'stock'             => 40,
                'is_featured'       => true,
                'categories'        => ['pure-honey-soaps'],
                'image'             => $soap,
                'image2'            => $img['honey_oat'],
                'short_description' => 'Premium UMF 15+ Manuka honey bar — antibacterial, anti-inflammatory artisan soap.',
                'description'       => "Manuka honey is one of nature's most potent antibacterial ingredients. Our Manuka Honey Cleansing Bar uses certified UMF 15+ Manuka honey to help calm breakouts, reduce skin inflammation, and deeply moisturise. The result is skin that feels plumped, clear, and naturally radiant.\n\nIngredients: Saponified olive oil, coconut oil, Manuka honey (UMF 15+), propolis extract, shea butter, castor oil.\n\nNet weight: 115 g | Hand-crafted in New Zealand.",
                'weight'            => 0.115,
                'variants'          => [],
            ],
            [
                'name'              => 'Raw Honey & Propolis Soap',
                'sku'               => 'HB-RHP-003',
                'price'             => 16.00,
                'compare_price'     => null,
                'stock'             => 50,
                'categories'        => ['pure-honey-soaps', 'honey-beeswax'],
                'image'             => $img['honey_oat'],
                'short_description' => 'Unheated raw honey with bee propolis — nature\'s own antiseptic powerhouse.',
                'description'       => "Propolis is the protective resin of the beehive. Pair it with unheated raw honey and you have one of nature's most effective antibacterial combinations in a gentle daily soap. This bar is particularly popular for blemish-prone and acne-affected skin.\n\nIngredients: Saponified coconut oil, palm oil, olive oil, raw unfiltered honey, bee propolis extract, beeswax, lavender essential oil.\n\nNet weight: 120 g",
                'weight'            => 0.12,
            ],
            [
                'name'              => 'Honeycomb Exfoliating Scrub Bar',
                'sku'               => 'HB-HES-004',
                'price'             => 15.00,
                'compare_price'     => null,
                'stock'             => 45,
                'categories'        => ['honey-oat-bars', 'honey-beeswax'],
                'image'             => $soap,
                'image2'            => $img['oat_milk'],
                'short_description' => 'Crushed honeycomb pieces suspend in this rich scrub bar for polished, glowing skin.',
                'description'       => "Tiny pieces of real honeycomb are suspended in our base to create a naturally exfoliating bar. As you lather, the honeycomb softens slightly, releasing honey while gently buffing away dead skin cells. Your skin is left smooth, polished and glowing.\n\nIngredients: Saponified coconut oil, olive oil, shea butter, raw beeswax, crushed honeycomb, sweet almond oil, vitamin E, calendula extract.\n\nNet weight: 125 g",
                'weight'            => 0.125,
            ],

            // ── BOTANICAL / FLORAL ───────────────────────────────────────────
            [
                'name'              => 'Lavender & Honey Soap',
                'sku'               => 'HB-LHS-005',
                'price'             => 14.00,
                'compare_price'     => null,
                'stock'             => 75,
                'is_featured'       => true,
                'categories'        => ['floral-soaps', 'botanical-collection'],
                'image'             => $img['lavender_soap'],
                'image2'            => $img['honey_oat'],
                'short_description' => 'Classic lavender essential oil with raw honey — calming, soothing, deeply aromatic.',
                'description'       => "Our bestselling Lavender & Honey bar combines the timeless scent of true French lavender with the skin-nourishing power of raw honey. The calming aroma helps ease stress, making this the perfect bar for an evening wind-down ritual.\n\nIngredients: Saponified olive oil, coconut oil, lavender essential oil (Lavandula angustifolia), raw honey, shea butter, dried lavender buds.\n\nNet weight: 130 g | Bestseller",
                'weight'            => 0.13,
                'variants'          => [
                    ['name' => 'Single Bar',   'price' => null,  'attributes' => ['size' => 'single']],
                    ['name' => 'Triple Pack',  'price' => 38.00, 'attributes' => ['size' => 'triple']],
                ],
            ],
            [
                'name'              => 'Rose Petal & Honey Bar',
                'sku'               => 'HB-RPH-006',
                'price'             => 15.50,
                'compare_price'     => 18.00,
                'stock'             => 55,
                'is_featured'       => true,
                'categories'        => ['floral-soaps'],
                'image'             => $img['rose_soap'],
                'image2'            => $img['floral_marble'],
                'short_description' => 'Bulgarian rose otto and dried rose petals — a romantic, skin-softening soap bar.',
                'description'       => "We use only genuine Bulgarian rose otto in this luxurious bar, paired with real dried rose petals embedded in the surface. Honey amplifies the moisturising properties, leaving skin silky-soft and delicately scented.\n\nIngredients: Saponified olive oil, coconut oil, Bulgarian rose otto essential oil, raw honey, rose hip seed oil, dried rose petals, rose clay.\n\nNet weight: 120 g",
                'weight'            => 0.12,
            ],
            [
                'name'              => 'Chamomile Honey Soothing Soap',
                'sku'               => 'HB-CHS-007',
                'price'             => 13.50,
                'compare_price'     => null,
                'stock'             => 60,
                'categories'        => ['floral-soaps', 'sensitive-skin'],
                'image'             => $img['floral_marble'],
                'image2'            => $img['botanical'],
                'short_description' => 'German chamomile and honey — ultra-gentle for reactive, rosacea-prone skin.',
                'description'       => "German chamomile contains bisabolol and azulene — powerful anti-inflammatory compounds that calm redness and irritation. Combined with raw honey, this bar is our most recommended choice for sensitive, rosacea-prone, or eczema-affected skin.\n\nIngredients: Saponified sunflower oil, olive oil, coconut oil, German chamomile essential oil, raw honey, shea butter, kaolin clay.\n\nNet weight: 115 g | Fragrance-free alternative available",
                'weight'            => 0.115,
            ],
            [
                'name'              => 'Calendula & Oat Gentle Bar',
                'sku'               => 'HB-COG-008',
                'price'             => 14.50,
                'compare_price'     => null,
                'stock'             => 50,
                'categories'        => ['honey-oat-bars', 'sensitive-skin'],
                'image'             => $img['oat_milk'],
                'image2'            => $img['botanical'],
                'short_description' => 'Golden calendula petals with soothing oatmeal for skin that needs extra TLC.',
                'description'       => "Calendula (pot marigold) has been used for centuries to soothe skin irritations, wounds, and rashes. Paired with colloidal oatmeal, this gentle bar is suitable even for baby-soft skin.\n\nIngredients: Saponified olive oil, coconut oil, colloidal oatmeal, calendula-infused olive oil, raw honey, shea butter, dried calendula petals.\n\nNet weight: 120 g",
                'weight'            => 0.12,
            ],

            // ── HERBAL / BOTANICAL ───────────────────────────────────────────
            [
                'name'              => 'Rosemary & Mint Herbal Bar',
                'sku'               => 'HB-RMH-009',
                'price'             => 13.50,
                'compare_price'     => null,
                'stock'             => 65,
                'categories'        => ['herbal-soaps', 'botanical-collection'],
                'image'             => $img['herbal_soap'],
                'image2'            => $img['dried_herbs'],
                'short_description' => 'Invigorating rosemary and peppermint — the energising morning wake-up bar.',
                'description'       => "Start your morning with a burst of clarity. Rosemary stimulates blood flow and mental alertness while peppermint delivers an invigorating cooling sensation. Tiny dried herbs speckle the bar for a textural, garden-fresh appearance.\n\nIngredients: Saponified coconut oil, olive oil, palm oil (RSPO), rosemary essential oil, peppermint essential oil, raw honey, dried rosemary, spirulina (natural colour).\n\nNet weight: 125 g",
                'weight'            => 0.125,
            ],
            [
                'name'              => 'Tea Tree Clarifying Soap',
                'sku'               => 'HB-TTC-010',
                'price'             => 14.00,
                'compare_price'     => null,
                'stock'             => 70,
                'categories'        => ['herbal-soaps', 'sensitive-skin'],
                'image'             => $img['charcoal_soap'],
                'image2'            => $img['herbal_soap'],
                'short_description' => 'Australian tea tree oil with kaolin clay — clears pores and fights bacterial breakouts.',
                'description'       => "Tea tree oil is nature's most studied antiseptic, proven to kill bacteria that cause acne. Our clarifying bar pairs pure Australian tea tree with kaolin clay to draw out impurities without over-drying. Suitable for oily, combination, and blemish-prone skin.\n\nIngredients: Saponified coconut oil, olive oil, tea tree essential oil (Melaleuca alternifolia), kaolin clay, raw honey, activated charcoal.\n\nNet weight: 120 g",
                'weight'            => 0.12,
            ],
            [
                'name'              => 'Turmeric & Honey Brightening Bar',
                'sku'               => 'HB-THB-011',
                'price'             => 16.00,
                'compare_price'     => 19.00,
                'stock'             => 45,
                'is_featured'       => true,
                'categories'        => ['herbal-soaps', 'face-care'],
                'image'             => $img['turmeric_soap'],
                'image2'            => $img['soap_making'],
                'short_description' => 'Golden turmeric and raw honey — the ancient Ayurvedic brightening duo in a modern bar.',
                'description'       => "Turmeric has been used in Indian beauty rituals for thousands of years to brighten, even skin tone, and reduce hyperpigmentation. We blend organic turmeric powder with raw honey for a brightening bar that also deeply moisturises.\n\nIngredients: Saponified olive oil, coconut oil, organic turmeric powder, raw honey, sweet almond oil, vitamin E, frankincense essential oil.\n\nNet weight: 115 g | Lathers with a subtle golden tint (no staining)",
                'weight'            => 0.115,
            ],
            [
                'name'              => 'Activated Charcoal Detox Bar',
                'sku'               => 'HB-ACD-012',
                'price'             => 15.00,
                'compare_price'     => null,
                'stock'             => 55,
                'categories'        => ['herbal-soaps', 'face-care'],
                'image'             => $img['charcoal_soap'],
                'short_description' => 'Food-grade activated charcoal deep-draws impurities from pores. Detox for your skin.',
                'description'       => "Activated charcoal acts like a magnet for toxins, excess sebum, and environmental pollutants. Our dramatic black bar is particularly popular for congested, urban skin that needs a serious deep cleanse without harsh chemicals.\n\nIngredients: Saponified coconut oil, olive oil, activated charcoal (food grade), bentonite clay, tea tree essential oil, raw honey, peppermint essential oil.\n\nNet weight: 120 g",
                'weight'            => 0.12,
            ],
            [
                'name'              => 'Patchouli & Sandalwood Bar',
                'sku'               => 'HB-PSW-013',
                'price'             => 15.00,
                'compare_price'     => null,
                'stock'             => 40,
                'categories'        => ['herbal-soaps', 'botanical-collection'],
                'image'             => $img['herbal_soap'],
                'image2'            => $img['botanical'],
                'short_description' => 'Deep earthy patchouli with warm sandalwood — grounding, meditative, and richly moisturising.',
                'description'       => "For those who love earthy, woody fragrance, our Patchouli & Sandalwood bar is a meditation in a bar. Patchouli oil is deeply skin-nourishing, while sandalwood adds a warm, creamy depth. Castor oil creates a luxurious, stable lather.\n\nIngredients: Saponified olive oil, coconut oil, castor oil, patchouli essential oil, sandalwood essential oil, raw honey, shea butter, rhassoul clay.\n\nNet weight: 125 g",
                'weight'            => 0.125,
            ],

            // ── THERAPEUTIC / FACE CARE ──────────────────────────────────────
            [
                'name'              => 'Goat Milk & Honey Bar',
                'sku'               => 'HB-GMH-014',
                'price'             => 17.00,
                'compare_price'     => 20.00,
                'stock'             => 50,
                'is_featured'       => true,
                'categories'        => ['sensitive-skin', 'honey-oat-bars'],
                'image'             => $img['goat_milk'],
                'image2'            => $img['oat_milk'],
                'short_description' => 'Fresh goat milk with raw honey — intensely creamy lather for dry, mature skin.',
                'description'       => "Goat milk contains lactic acid (a gentle AHA) plus fat molecules that closely mimic the natural lipid layer of human skin. This makes it exceptional for dryness, eczema, and mature skin. Paired with raw honey, the result is a bar with an unbelievably creamy, cocoon-like lather.\n\nIngredients: Saponified coconut oil, olive oil, goat milk, raw honey, shea butter, oat silk protein, vanilla extract.\n\nNet weight: 120 g",
                'weight'            => 0.12,
            ],
            [
                'name'              => 'Green Tea & Honey Face Bar',
                'sku'               => 'HB-GTH-015',
                'price'             => 17.50,
                'compare_price'     => null,
                'stock'             => 40,
                'categories'        => ['face-care'],
                'image'             => $img['botanical'],
                'image2'            => $img['dried_herbs'],
                'short_description' => 'Antioxidant-rich green tea with honey — anti-pollution daily face wash for city skin.',
                'description'       => "Green tea is one of the most antioxidant-dense botanicals on earth, protecting skin from free radical damage and environmental pollution. Our morning face bar uses a concentrated green tea infusion as the water base, ensuring maximum antioxidant delivery.\n\nIngredients: Saponified jojoba oil, argan oil, coconut oil, green tea infusion, raw Manuka honey, vitamin C ester, kaolin clay.\n\nNet weight: 100 g | Specifically sized for face use",
                'weight'            => 0.10,
            ],
            [
                'name'              => 'Frankincense Anti-Age Face Bar',
                'sku'               => 'HB-FAF-016',
                'price'             => 19.00,
                'compare_price'     => 24.00,
                'stock'             => 30,
                'is_featured'       => true,
                'categories'        => ['face-care'],
                'image'             => $img['soap_making'],
                'image2'            => $img['botanical'],
                'short_description' => 'Sacred Boswellia frankincense with Manuka honey — lift, firm and regenerate mature skin.',
                'description'       => "Frankincense (Boswellia sacra) is the gold standard of anti-ageing botanicals. It promotes cellular regeneration, firms skin, reduces fine lines, and deeply hydrates. Our premium face bar sources authentic Omani frankincense resin. Suitable for 40+ skin.\n\nIngredients: Saponified argan oil, rosehip oil, shea butter, Manuka honey (UMF 20+), frankincense essential oil, myrrh essential oil, pomegranate seed oil.\n\nNet weight: 100 g | Award-winning formula",
                'weight'            => 0.10,
            ],
            [
                'name'              => 'Dead Sea Mineral Cleanse Bar',
                'sku'               => 'HB-DSM-017',
                'price'             => 16.50,
                'compare_price'     => null,
                'stock'             => 35,
                'categories'        => ['face-care', 'therapeutic-range'],
                'image'             => $img['charcoal_soap'],
                'image2'            => $img['soap_making'],
                'short_description' => 'Authentic Dead Sea salt and minerals with honey — detoxifying and pore-refining.',
                'description'       => "Dead Sea salt contains 21 minerals not found elsewhere, including magnesium, calcium, and potassium — all essential for healthy skin function. Fine Dead Sea salt crystals give this bar light exfoliating properties while drawing out impurities.\n\nIngredients: Saponified coconut oil, olive oil, Dead Sea salt (fine ground), raw honey, activated charcoal, jojoba beads, eucalyptus essential oil.\n\nNet weight: 130 g",
                'weight'            => 0.13,
            ],
            [
                'name'              => 'Aloe Vera & Cucumber Soothing Bar',
                'sku'               => 'HB-AVC-018',
                'price'             => 12.50,
                'compare_price'     => null,
                'stock'             => 65,
                'categories'        => ['sensitive-skin', 'botanical-collection'],
                'image'             => $img['herbal_soap'],
                'image2'            => $img['botanical'],
                'short_description' => 'Pure aloe vera gel with cucumber extract — cooling, hydrating relief for sunburned skin.',
                'description'       => "Fresh aloe vera gel constitutes 30% of our water ratio in this bar, delivering intense soothing properties for irritated, sunburned, or overheated skin. Cucumber extract adds additional cooling and anti-inflammatory effects.\n\nIngredients: Saponified coconut oil, olive oil, aloe vera gel (cold-pressed), cucumber extract, raw honey, silk proteins, green clay.\n\nNet weight: 115 g",
                'weight'            => 0.115,
                'status'            => 'active',
            ],
            [
                'name'              => 'Coconut & Lime Scrub Bar',
                'sku'               => 'HB-CLS-019',
                'price'             => 13.00,
                'compare_price'     => null,
                'stock'             => 55,
                'categories'        => ['herbal-soaps', 'botanical-collection'],
                'image'             => $img['oat_milk'],
                'image2'            => $img['floral_marble'],
                'short_description' => 'Desiccated coconut and zesty lime zest — a tropical exfoliating body bar.',
                'description'       => "The fresh, tropical scent of this bar will transport you to a beachside paradise. Desiccated coconut flakes provide gentle physical exfoliation while coconut oil delivers deep moisture. Fresh-pressed lime essential oil adds an uplifting zing.\n\nIngredients: Saponified coconut oil (90%), olive oil, desiccated coconut flakes, lime essential oil, raw honey, vitamin E, coconut milk powder.\n\nNet weight: 130 g",
                'weight'            => 0.13,
            ],
            [
                'name'              => 'Shea Butter & Vanilla Luxury Bar',
                'sku'               => 'HB-SVL-020',
                'price'             => 15.00,
                'compare_price'     => null,
                'stock'             => 50,
                'categories'        => ['sensitive-skin', 'honey-oat-bars'],
                'image'             => $img['goat_milk'],
                'image2'            => $img['oat_milk'],
                'short_description' => 'Unrefined shea butter with Madagascar vanilla — incredibly rich, indulgent daily bar.',
                'description'       => "Our most indulgent everyday bar. Unrefined shea butter is used at a rate of 20% in this formula, well above the industry standard, creating an exceptionally moisturising bar with a dense, creamy lather. Madagascar vanilla absolute rounds it with a warm, sweet scent.\n\nIngredients: Saponified shea butter (20%), coconut oil, olive oil, castor oil, raw honey, Madagascar vanilla absolute, vitamin E.\n\nNet weight: 125 g",
                'weight'            => 0.125,
            ],

            // ── GIFT SETS ────────────────────────────────────────────────────
            [
                'name'              => 'Honey Bee Discovery Set (3 Bars)',
                'sku'               => 'HB-DS3-021',
                'price'             => 38.00,
                'compare_price'     => 44.00,
                'stock'             => 25,
                'is_featured'       => true,
                'categories'        => ['discovery-sets', 'gift-sets'],
                'image'             => $img['gift_set'],
                'image2'            => $img['honey_oat'],
                'short_description' => 'Three bestselling bars in a kraft gift box — ideal first introduction to Honey Bee.',
                'description'       => "Can't decide? Our Discovery Set includes three of our bestselling soap bars so you can explore the Honey Bee range. Each set is packaged in a recyclable kraft gift box lined with tissue paper and includes a card explaining each bar's ingredients.\n\nIncludes: Wild Honey & Oat Bar + Lavender & Honey Soap + Tea Tree Clarifying Soap\n\nGift box dimensions: 20 × 10 × 6 cm",
                'weight'            => 0.42,
            ],
            [
                'name'              => 'Botanical Collection Gift Box (5 Bars)',
                'sku'               => 'HB-BCG-022',
                'price'             => 65.00,
                'compare_price'     => 76.00,
                'stock'             => 18,
                'is_featured'       => true,
                'categories'        => ['luxury-gifts', 'gift-sets', 'botanical-collection'],
                'image'             => $img['gift_set'],
                'image2'            => $img['floral_marble'],
                'short_description' => 'Five botanical bars in a luxury linen-lined box — the perfect gift for natural-beauty lovers.',
                'description'       => "Our most popular gift. Five handpicked bars from the Botanical Collection, each carefully wrapped in recycled wax paper and nestled in a reusable linen-lined wooden box. A beautiful and thoughtful gift for any occasion.\n\nIncludes: Lavender & Honey + Rose Petal & Honey + Chamomile Honey + Rosemary & Mint + Calendula & Oat\n\nBox dimensions: 28 × 14 × 7 cm | Ribbon tied",
                'weight'            => 0.70,
            ],
            [
                'name'              => 'Honey Ritual Spa Set (4 Bars)',
                'sku'               => 'HB-SPA-023',
                'price'             => 55.00,
                'compare_price'     => 66.00,
                'stock'             => 20,
                'is_featured'       => true,
                'categories'        => ['luxury-gifts', 'gift-sets'],
                'image'             => $img['spa_ritual'],
                'image2'            => $img['gift_set'],
                'short_description' => 'A curated spa ritual in a gift box — face bar, body bar, cleanse bar and lip scrub bar.',
                'description'       => "Gift the full spa experience. This curated set includes four bars designed to be used in sequence as part of a luxurious at-home spa ritual, wrapped in silk-lined black box.\n\nIncludes: Frankincense Anti-Age Face Bar + Goat Milk & Honey Bar + Activated Charcoal Detox Bar + Rose Petal & Honey Bar\n\nBox includes: Ritual guide card, reusable soap dish, muslin soap bag\n\nDimensions: 24 × 18 × 8 cm",
                'weight'            => 0.60,
            ],
            [
                'name'              => 'Sweet Lavender Garden Set (3 Bars)',
                'sku'               => 'HB-SLG-024',
                'price'             => 42.00,
                'compare_price'     => null,
                'stock'             => 22,
                'categories'        => ['discovery-sets', 'gift-sets', 'floral-soaps'],
                'image'             => $img['lavender_soap'],
                'image2'            => $img['gift_set'],
                'short_description' => 'Three lavender-forward bars — a serene, aromatic collection for bedtime rituals.',
                'description'       => "All the calming power of lavender in three companion bars. Perfect as a gift for a stressed friend or as your own evening wind-down collection. Presented in a lavender-sprig decorated kraft box.\n\nIncludes: Lavender & Honey Soap + Chamomile Honey Soothing Soap + Calendula & Oat Gentle Bar\n\nBox includes: Dried lavender sachet, soap-saving pouch",
                'weight'            => 0.40,
            ],
            [
                'name'              => 'Natural Baby Care Gift Set (3 Bars)',
                'sku'               => 'HB-NBC-025',
                'price'             => 48.00,
                'compare_price'     => 54.00,
                'stock'             => 15,
                'is_featured'       => false,
                'status'            => 'active',
                'categories'        => ['discovery-sets', 'sensitive-skin', 'gift-sets'],
                'image'             => $img['oat_milk'],
                'image2'            => $img['gift_set'],
                'short_description' => 'Fragrance-free, ultra-gentle bars for baby and new-mum skin — a heartfelt new arrival gift.',
                'description'       => "Our gentlest bars selected specifically for new babies and postpartum skin. All three bars are fragrance-free, hypoallergenic, and use only the mildest cleansing ingredients at low levels. Dermatologist-tested and suitable from birth.\n\nIncludes: Calendula & Oat Gentle Bar + Chamomile Honey Soothing Soap + Aloe Vera & Cucumber Soothing Bar\n\nPresented in white gift box with watercolour bee motif. Includes care card.",
                'weight'            => 0.42,
            ],
        ];
    }
}
