<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the Demo Store / Storefront Template (ID=1) with fashion & lifestyle
 * collections and products — suitable for white-label client demonstrations.
 *
 * Images sourced from Pexels CDN (free for commercial use).
 */
class StorefrontTemplateCatalogSeeder extends Seeder
{
    private const STORE_ID = 1;

    /**
     * Pexels CDN URLs for each product.
     * ProductImage::getUrlAttribute() returns full URLs as-is.
     */
    private array $images = [
        'blouse'     => 'https://images.pexels.com/photos/996329/pexels-photo-996329.jpeg?auto=compress&cs=tinysrgb&w=800',
        'jeans'      => 'https://images.pexels.com/photos/1598507/pexels-photo-1598507.jpeg?auto=compress&cs=tinysrgb&w=800',
        'dress'      => 'https://images.pexels.com/photos/1755428/pexels-photo-1755428.jpeg?auto=compress&cs=tinysrgb&w=800',
        'blazer'     => 'https://images.pexels.com/photos/1043474/pexels-photo-1043474.jpeg?auto=compress&cs=tinysrgb&w=800',
        'handbag'    => 'https://images.pexels.com/photos/3769747/pexels-photo-3769747.jpeg?auto=compress&cs=tinysrgb&w=800',
        'earrings'   => 'https://images.pexels.com/photos/691046/pexels-photo-691046.jpeg?auto=compress&cs=tinysrgb&w=800',
        'necklace'   => 'https://images.pexels.com/photos/1152077/pexels-photo-1152077.jpeg?auto=compress&cs=tinysrgb&w=800',
        'sneakers'   => 'https://images.pexels.com/photos/2529148/pexels-photo-2529148.jpeg?auto=compress&cs=tinysrgb&w=800',
        'boots'      => 'https://images.pexels.com/photos/1127030/pexels-photo-1127030.jpeg?auto=compress&cs=tinysrgb&w=800',
        // Collection / lifestyle fallbacks
        'shopping'   => 'https://images.pexels.com/photos/36730644/pexels-photo-36730644/free-photo-of-woman-shopping-for-coats-in-a-clothing-store.jpeg?auto=compress&cs=tinysrgb&w=800',
        'unboxing'   => 'https://images.pexels.com/photos/6207704/pexels-photo-6207704.jpeg?auto=compress&cs=tinysrgb&w=800',
        'sweaters'   => 'https://images.pexels.com/photos/7514864/pexels-photo-7514864.jpeg?auto=compress&cs=tinysrgb&w=800',
        'laptop'     => 'https://images.pexels.com/photos/7679688/pexels-photo-7679688.jpeg?auto=compress&cs=tinysrgb&w=800',
    ];

    public function run(): void
    {
        $this->command->info('🛍️  Seeding Storefront Template catalog (store #' . self::STORE_ID . ')...');

        $categoryMap = $this->seedCollections();
        $this->seedProducts($categoryMap);

        $catCount  = Category::withoutGlobalScopes()->where('store_id', self::STORE_ID)->count();
        $prodCount = Product::withoutGlobalScopes()->where('store_id', self::STORE_ID)->count();
        $this->command->info("✅ Storefront Template: {$catCount} collections, {$prodCount} products seeded.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // COLLECTIONS
    // ──────────────────────────────────────────────────────────────────────────

    private function seedCollections(): array
    {
        $map = [];

        $structure = [
            [
                'name'        => 'New Arrivals',
                'slug'        => 'new-arrivals',
                'description' => 'The latest styles — freshly added to the store.',
                'image'       => $this->images['unboxing'],
                'children'    => [
                    ['name' => 'New In Women', 'slug' => 'new-in-women', 'description' => 'New womenswear arrivals.'],
                    ['name' => 'New In Men',   'slug' => 'new-in-men',   'description' => 'New menswear arrivals.'],
                ],
            ],
            [
                'name'        => 'Clothing',
                'slug'        => 'clothing',
                'description' => 'Clothing for every occasion — from casual to smart-casual.',
                'image'       => $this->images['shopping'],
                'children'    => [
                    ['name' => "Women's Clothing", 'slug' => 'womens-clothing', 'description' => 'Tops, dresses, trousers and more.'],
                    ['name' => "Men's Clothing",   'slug' => 'mens-clothing',   'description' => 'Shirts, trousers, outerwear and more.'],
                    ['name' => 'Outerwear',         'slug' => 'outerwear',        'description' => 'Jackets, coats and layering pieces.'],
                ],
            ],
            [
                'name'        => 'Accessories',
                'slug'        => 'accessories',
                'description' => 'Bags, jewellery, shoes, and finishing touches.',
                'image'       => $this->images['handbag'],
                'children'    => [
                    ['name' => 'Bags & Wallets', 'slug' => 'bags-wallets',  'description' => 'Handbags, totes, crossbody bags and wallets.'],
                    ['name' => 'Jewellery',       'slug' => 'jewellery',     'description' => 'Earrings, necklaces, rings and bracelets.'],
                    ['name' => 'Shoes',           'slug' => 'shoes',         'description' => 'Sneakers, boots, heels and flats.'],
                ],
            ],
            [
                'name'        => 'Sale',
                'slug'        => 'sale',
                'description' => 'Great prices on selected lines — while stocks last.',
                'image'       => $this->images['sweaters'],
                'children'    => [
                    ['name' => 'Up to 50% Off', 'slug' => 'up-to-50-off', 'description' => 'Premium picks at half price.'],
                    ['name' => 'Clearance',     'slug' => 'clearance',    'description' => 'Final reductions on last-season stock.'],
                ],
            ],
        ];

        $parentSort = 0;
        foreach ($structure as $parent) {
            $parentCat = Category::create([
                'store_id'    => self::STORE_ID,
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
                    'store_id'    => self::STORE_ID,
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
        foreach ($this->productDefinitions() as $def) {
            $price = $def['price'];

            $product = Product::create([
                'store_id'            => self::STORE_ID,
                'name'                => $def['name'],
                'slug'                => Str::slug($def['name']),
                'sku'                 => $def['sku'],
                'description'         => $def['description'],
                'short_description'   => $def['short_description'],
                'price'               => $price,
                'compare_price'       => $def['compare_price'] ?? null,
                'cost_price'          => round($price * 0.40, 2),
                'track_inventory'     => true,
                'stock_quantity'      => $def['stock'] ?? rand(20, 100),
                'low_stock_threshold' => 5,
                'weight'              => $def['weight'] ?? 0.30,
                'weight_unit'         => 'kg',
                'dimensions'          => $def['dimensions'] ?? ['length' => 30, 'width' => 20, 'height' => 5, 'unit' => 'cm'],
                'status'              => $def['status'] ?? 'active',
                'is_featured'         => $def['is_featured'] ?? false,
                'meta_title'          => $def['name'] . ' | Demo Store',
                'meta_description'    => $def['short_description'],
            ]);

            // Attach collections
            $catIds = array_map(fn($slug) => $categoryMap[$slug] ?? null, $def['categories']);
            $product->categories()->attach(array_filter($catIds));

            // Primary image
            ProductImage::create([
                'product_id' => $product->id,
                'store_id'   => self::STORE_ID,
                'file_path'  => $def['image'],
                'alt_text'   => $def['name'],
                'sort_order' => 0,
                'is_primary' => true,
            ]);

            // Secondary image
            if (!empty($def['image2'])) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'store_id'   => self::STORE_ID,
                    'file_path'  => $def['image2'],
                    'alt_text'   => $def['name'] . ' – detail',
                    'sort_order' => 1,
                    'is_primary' => false,
                ]);
            }

            // Variants
            if (!empty($def['variants'])) {
                foreach ($def['variants'] as $variant) {
                    ProductVariant::create([
                        'product_id'     => $product->id,
                        'store_id'       => self::STORE_ID,
                        'name'           => $variant['name'],
                        'sku'            => $def['sku'] . '-' . Str::upper(Str::slug($variant['name'])),
                        'price'          => $variant['price'] ?? null,
                        'compare_price'  => null,
                        'stock_quantity' => rand(5, 25),
                        'attributes'     => $variant['attributes'],
                        'image'          => null,
                        'is_active'      => true,
                    ]);
                }
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRODUCT DEFINITIONS (22 products)
    // ──────────────────────────────────────────────────────────────────────────

    private function productDefinitions(): array
    {
        $img = $this->images;
        $sizes = ['XS', 'S', 'M', 'L', 'XL'];

        return [
            // ── WOMEN'S CLOTHING ─────────────────────────────────────────────
            [
                'name'              => 'Classic White Linen Blouse',
                'sku'               => 'ST-WCL-001',
                'price'             => 49.99,
                'compare_price'     => null,
                'stock'             => 80,
                'is_featured'       => true,
                'categories'        => ['womens-clothing', 'new-in-women'],
                'image'             => $img['blouse'],
                'image2'            => $img['shopping'],
                'short_description' => 'Breathable linen blouse with relaxed silhouette — a wardrobe essential.',
                'description'       => "This effortlessly chic white linen blouse is cut from 100% European linen that softens with every wash. The relaxed silhouette suits a wide range of body shapes, while the collarless neckline keeps it versatile across casual and smart-casual occasions.\n\nFabric: 100% European linen | Machine washable at 30°C\nFit: Relaxed | Model wears size S",
                'weight'            => 0.22,
                'variants'          => array_map(fn($s) => ['name' => $s, 'price' => null, 'attributes' => ['size' => $s]], $sizes),
            ],
            [
                'name'              => 'Mid-Rise Slim Denim Jeans',
                'sku'               => 'ST-WDJ-002',
                'price'             => 69.99,
                'compare_price'     => 89.99,
                'stock'             => 60,
                'is_featured'       => true,
                'categories'        => ['womens-clothing', 'new-in-women'],
                'image'             => $img['jeans'],
                'image2'            => $img['shopping'],
                'short_description' => 'Mid-rise slim-fit jeans in classic dark indigo — flattering on every figure.',
                'description'       => "The go-to dark denim. Cut from a stretch-cotton blend for comfort without sacrificing that clean denim look. Mid-rise sits at the natural waist to create a flattering silhouette.\n\nFabric: 98% cotton, 2% elastane | Machine washable\nWaist sizes: 24–34 | Leg lengths: 28\", 30\", 32\"",
                'weight'            => 0.55,
                'variants'          => [
                    ['name' => 'W26 L30', 'price' => null, 'attributes' => ['waist' => '26', 'length' => '30']],
                    ['name' => 'W28 L30', 'price' => null, 'attributes' => ['waist' => '28', 'length' => '30']],
                    ['name' => 'W30 L30', 'price' => null, 'attributes' => ['waist' => '30', 'length' => '30']],
                    ['name' => 'W32 L32', 'price' => null, 'attributes' => ['waist' => '32', 'length' => '32']],
                    ['name' => 'W34 L32', 'price' => null, 'attributes' => ['waist' => '34', 'length' => '32']],
                ],
            ],
            [
                'name'              => 'Floral Wrap Summer Dress',
                'sku'               => 'ST-WFD-003',
                'price'             => 59.99,
                'compare_price'     => null,
                'stock'             => 45,
                'is_featured'       => true,
                'categories'        => ['womens-clothing', 'new-in-women'],
                'image'             => $img['dress'],
                'image2'            => $img['unboxing'],
                'short_description' => 'Floaty midi wrap dress in a garden-floral print — day-to-evening versatility.',
                'description'       => "The wrap silhouette flatters all body types, cinching at the waist while flowing freely below. Printed on a lightweight, breathable fabric, this midi-length dress transitions effortlessly from beach café to evening dinner.\n\nFabric: 100% viscose | Dry clean recommended\nFit: Wrap, adjustable tie | Midi length",
                'weight'            => 0.28,
                'variants'          => array_map(fn($s) => ['name' => $s, 'price' => null, 'attributes' => ['size' => $s]], $sizes),
            ],
            [
                'name'              => 'Structured Navy Blazer',
                'sku'               => 'ST-WNB-004',
                'price'             => 89.99,
                'compare_price'     => 120.00,
                'stock'             => 35,
                'is_featured'       => true,
                'categories'        => ['womens-clothing', 'outerwear'],
                'image'             => $img['blazer'],
                'image2'            => $img['shopping'],
                'short_description' => 'Tailored navy blazer with a slight stretch — sharp looks for office and beyond.',
                'description'       => "A well-cut blazer is the one item that instantly elevates any outfit. Our structured navy blazer features precision tailoring with a single-button fastening, two patch pockets, and a side vent. The stretch-wool blend means you can actually move in it.\n\nFabric: 95% wool, 5% elastane | Dry clean\nFit: Tailored | Sizes: XS–XL",
                'weight'            => 0.60,
                'variants'          => array_map(fn($s) => ['name' => $s, 'price' => null, 'attributes' => ['size' => $s]], $sizes),
            ],
            [
                'name'              => 'Cashmere V-Neck Sweater',
                'sku'               => 'ST-WCS-005',
                'price'             => 79.99,
                'compare_price'     => null,
                'stock'             => 40,
                'categories'        => ['womens-clothing', 'new-in-women'],
                'image'             => $img['sweaters'],
                'image2'            => $img['unboxing'],
                'short_description' => 'Lightweight cashmere blend V-neck — the season\'s most versatile piece.',
                'description'       => "80% cashmere, 20% silk creates an exceptionally lightweight and lustrous sweater that drapes beautifully. The deep V-neck makes it ideal layered over a shirt or worn alone with tailored trousers.\n\nFabric: 80% cashmere, 20% silk | Hand wash cold\nColours: Ivory, Camel, Dusty Rose, Midnight Navy",
                'weight'            => 0.25,
                'variants'          => [
                    ['name' => 'Ivory / S',       'price' => null, 'attributes' => ['colour' => 'Ivory',       'size' => 'S']],
                    ['name' => 'Camel / S',       'price' => null, 'attributes' => ['colour' => 'Camel',       'size' => 'S']],
                    ['name' => 'Ivory / M',       'price' => null, 'attributes' => ['colour' => 'Ivory',       'size' => 'M']],
                    ['name' => 'Camel / M',       'price' => null, 'attributes' => ['colour' => 'Camel',       'size' => 'M']],
                    ['name' => 'Dusty Rose / M',  'price' => null, 'attributes' => ['colour' => 'Dusty Rose',  'size' => 'M']],
                    ['name' => 'Midnight Navy / L','price' => null, 'attributes' => ['colour' => 'Midnight Navy','size' => 'L']],
                ],
            ],
            [
                'name'              => 'High-Waist Wide Leg Trousers',
                'sku'               => 'ST-WHT-006',
                'price'             => 64.99,
                'compare_price'     => null,
                'stock'             => 50,
                'categories'        => ['womens-clothing'],
                'image'             => $img['shopping'],
                'image2'            => $img['jeans'],
                'short_description' => 'Tailored high-waist wide-leg trousers in crêpe — polished and comfortable.',
                'description'       => "Wide-leg trousers are the season's defining silhouette. These are cut from a smooth crêpe fabric that falls beautifully and resists creasing — ideal for long working days. The high waist defines the figure while the wide leg creates an elongating effect.\n\nFabric: 100% polyester (crêpe) | Machine washable\nColours: Ecru, Charcoal, Ink Blue",
                'weight'            => 0.35,
                'variants'          => array_map(fn($s) => ['name' => $s, 'price' => null, 'attributes' => ['size' => $s]], $sizes),
            ],
            [
                'name'              => 'Ribbed Knit Cardigan',
                'sku'               => 'ST-WRK-007',
                'price'             => 54.99,
                'compare_price'     => 65.00,
                'stock'             => 55,
                'categories'        => ['womens-clothing', 'up-to-50-off'],
                'image'             => $img['sweaters'],
                'image2'            => $img['blouse'],
                'short_description' => 'Long ribbed-knit cardigan in a neutral palette — loungewear-to-streetwear staple.',
                'description'       => "This season's essential layering piece. The long length and deep ribbing create a cocooning silhouette that works equally well over a silk slip on weekends or buttoned up as a light outer layer. Available in four season-spanning neutrals.\n\nFabric: 60% cotton, 40% acrylic | Machine washable\nColours: Oatmeal, Stone, Charcoal, Sage",
                'weight'            => 0.45,
                'variants'          => array_map(fn($s) => ['name' => $s, 'price' => null, 'attributes' => ['size' => $s]], $sizes),
            ],
            [
                'name'              => 'Quilted Puffer Jacket',
                'sku'               => 'ST-WPJ-008',
                'price'             => 99.99,
                'compare_price'     => 130.00,
                'stock'             => 30,
                'is_featured'       => true,
                'categories'        => ['outerwear', 'womens-clothing'],
                'image'             => $img['shopping'],
                'image2'            => $img['blazer'],
                'short_description' => 'Lightweight quilted puffer — serious warmth without the bulk.',
                'description'       => "Down-filled yet incredibly packable, our quilted puffer jacket compresses to the size of a small pouch. 750-fill recycled down provides warmth down to -10°C while the ripstop outer shell is wind and shower-resistant.\n\nFabric: 100% recycled ripstop nylon shell | 750 fill recycled down\nColours: Black, Forest Green, Dusty Pink",
                'weight'            => 0.50,
                'dimensions'        => ['length' => 55, 'width' => 45, 'height' => 10, 'unit' => 'cm'],
                'variants'          => [
                    ['name' => 'Black / S',          'price' => null, 'attributes' => ['colour' => 'Black',        'size' => 'S']],
                    ['name' => 'Black / M',          'price' => null, 'attributes' => ['colour' => 'Black',        'size' => 'M']],
                    ['name' => 'Black / L',          'price' => null, 'attributes' => ['colour' => 'Black',        'size' => 'L']],
                    ['name' => 'Forest Green / M',   'price' => null, 'attributes' => ['colour' => 'Forest Green', 'size' => 'M']],
                    ['name' => 'Dusty Pink / M',     'price' => null, 'attributes' => ['colour' => 'Dusty Pink',   'size' => 'M']],
                ],
            ],
            [
                'name'              => 'Denim Cutoff Shorts',
                'sku'               => 'ST-WDS-009',
                'price'             => 39.99,
                'compare_price'     => null,
                'stock'             => 70,
                'categories'        => ['womens-clothing', 'clearance'],
                'image'             => $img['jeans'],
                'short_description' => 'Raw-hem denim cutoffs in a faded light wash — summer\'s most-worn essential.',
                'description'       => "Made from the same stretch-cotton denim as our full-length jeans, these cutoffs feature a raw, frayed hem and a slightly distressed wash for an effortlessly cool look. The 5-pocket design follows traditional denim styling.\n\nFabric: 98% cotton, 2% elastane | Machine washable\nWaist sizes: 24–34",
                'weight'            => 0.30,
                'variants'          => [
                    ['name' => 'W26', 'price' => null, 'attributes' => ['waist' => '26']],
                    ['name' => 'W28', 'price' => null, 'attributes' => ['waist' => '28']],
                    ['name' => 'W30', 'price' => null, 'attributes' => ['waist' => '30']],
                    ['name' => 'W32', 'price' => null, 'attributes' => ['waist' => '32']],
                ],
            ],
            [
                'name'              => 'Silk Printed Midi Skirt',
                'sku'               => 'ST-WSS-010',
                'price'             => 54.99,
                'compare_price'     => null,
                'stock'             => 35,
                'categories'        => ['womens-clothing', 'new-in-women'],
                'image'             => $img['dress'],
                'image2'            => $img['blouse'],
                'short_description' => 'Fluid silk-satin midi skirt in abstract botanical print — dinner-ready in seconds.',
                'description'       => "Silk-satin moves like nothing else. This printed midi skirt features an elasticated waist for an effortless fit and falls to a below-the-knee length that suits most heights. Style it casually with a tucked-in tee or dressed up with a heel.\n\nFabric: 100% silk satin | Dry clean\nClosure: Elasticated waist | One size fits XS–L",
                'weight'            => 0.20,
                'variants'          => array_map(fn($s) => ['name' => $s, 'price' => null, 'attributes' => ['size' => $s]], ['XS', 'S', 'M', 'L']),
            ],

            // ── MEN'S CLOTHING ───────────────────────────────────────────────
            [
                'name'              => "Men's Classic Oxford Shirt",
                'sku'               => 'ST-MOS-011',
                'price'             => 54.99,
                'compare_price'     => null,
                'stock'             => 65,
                'is_featured'       => true,
                'categories'        => ['mens-clothing', 'new-in-men'],
                'image'             => $img['blazer'],
                'image2'            => $img['jeans'],
                'short_description' => 'Classic fit Oxford shirt in 100% cotton — office to weekend without compromise.',
                'description'       => "Oxford cloth has been the fabric of choice for formal-casual shirts since the 1920s. Our version is cut with a classic fit that isn't too slim or too boxy, uses genuine mother-of-pearl buttons, and features a box pleat at the back for ease of movement.\n\nFabric: 100% cotton Oxford weave | Machine washable\nColours: White, Sky Blue, Pale Pink, Slate",
                'weight'            => 0.30,
                'variants'          => [
                    ['name' => 'XS', 'price' => null, 'attributes' => ['size' => 'XS']],
                    ['name' => 'S',  'price' => null, 'attributes' => ['size' => 'S']],
                    ['name' => 'M',  'price' => null, 'attributes' => ['size' => 'M']],
                    ['name' => 'L',  'price' => null, 'attributes' => ['size' => 'L']],
                    ['name' => 'XL', 'price' => null, 'attributes' => ['size' => 'XL']],
                    ['name' => 'XXL','price' => null, 'attributes' => ['size' => 'XXL']],
                ],
            ],
            [
                'name'              => "Men's Slim Chino Trousers",
                'sku'               => 'ST-MCT-012',
                'price'             => 59.99,
                'compare_price'     => 72.00,
                'stock'             => 50,
                'categories'        => ['mens-clothing', 'up-to-50-off'],
                'image'             => $img['jeans'],
                'image2'            => $img['shopping'],
                'short_description' => 'Slim-fit chino trousers in a stretch cotton twill — smart-casual perfection.',
                'description'       => "These slim chinos walk the fine line between smart and casual with ease. The stretch cotton twill means they're comfortable even after long wear, while the clean finish keeps them boardroom-appropriate.\n\nFabric: 97% cotton, 3% elastane | Machine washable\nColours: Khaki, Navy, Camel, Olive, Charcoal",
                'weight'            => 0.45,
                'variants'          => [
                    ['name' => 'W30 L30', 'price' => null, 'attributes' => ['waist' => '30', 'length' => '30']],
                    ['name' => 'W32 L30', 'price' => null, 'attributes' => ['waist' => '32', 'length' => '30']],
                    ['name' => 'W32 L32', 'price' => null, 'attributes' => ['waist' => '32', 'length' => '32']],
                    ['name' => 'W34 L32', 'price' => null, 'attributes' => ['waist' => '34', 'length' => '32']],
                    ['name' => 'W36 L32', 'price' => null, 'attributes' => ['waist' => '36', 'length' => '32']],
                ],
            ],
            [
                'name'              => 'Slim Crew Neck Sweatshirt',
                'sku'               => 'ST-MSW-013',
                'price'             => 44.99,
                'compare_price'     => null,
                'stock'             => 75,
                'categories'        => ['mens-clothing', 'new-in-men'],
                'image'             => $img['sweaters'],
                'image2'            => $img['shopping'],
                'short_description' => 'Heavyweight cotton crew neck sweatshirt — minimal, clean and endlessly wearable.',
                'description'       => "No graphics. No logos. Just a perfectly cut, heavyweight cotton sweatshirt in a tight-knit fleece that holds its shape wash after wash. The slim-but-not-tight cut makes this versatile from gym to street to sofa.\n\nFabric: 100% ring-spun cotton (380 gsm) | Machine washable\nColours: Black, White, Oatmeal, Stone Wash Blue",
                'weight'            => 0.55,
                'variants'          => array_map(fn($s) => ['name' => $s, 'price' => null, 'attributes' => ['size' => $s]], ['S', 'M', 'L', 'XL', 'XXL']),
            ],

            // ── ACCESSORIES: BAGS ────────────────────────────────────────────
            [
                'name'              => 'Tan Leather Crossbody Bag',
                'sku'               => 'ST-ACB-014',
                'price'             => 119.99,
                'compare_price'     => null,
                'stock'             => 25,
                'is_featured'       => true,
                'categories'        => ['bags-wallets', 'accessories'],
                'image'             => $img['handbag'],
                'image2'            => $img['unboxing'],
                'short_description' => 'Full-grain tan leather crossbody — compact enough for daily use, room for all essentials.',
                'description'       => "Crafted from full-grain Italian leather that develops a beautiful patina over time. The adjustable crossbody strap, slip pocket, and zip closure make this the ideal everyday carrying companion.\n\nMaterial: Full-grain Italian leather | Cotton lining\nDimensions: 22 × 16 × 6 cm | Interior: 1 main compartment + 2 pockets\nHardware: Antique brass",
                'weight'            => 0.35,
            ],
            [
                'name'              => 'Large Canvas Tote Bag',
                'sku'               => 'ST-ACT-015',
                'price'             => 39.99,
                'compare_price'     => null,
                'stock'             => 60,
                'categories'        => ['bags-wallets', 'accessories', 'clearance'],
                'image'             => $img['unboxing'],
                'image2'            => $img['handbag'],
                'short_description' => 'Heavy canvas tote with leather handles — carries everything including your laptop.',
                'description'       => "The utilitarian tote, elevated. 16 oz heavyweight canvas in a natural colour, with vegetable-tanned leather carry handles and a zip inner pocket for valuables. Large enough for a 15\" laptop.\n\nMaterial: 16oz natural canvas | Vegetable-tanned leather handles\nDimensions: 40 × 35 × 12 cm",
                'weight'            => 0.55,
                'variants'          => [
                    ['name' => 'Natural',   'price' => null,  'attributes' => ['colour' => 'Natural']],
                    ['name' => 'Black',     'price' => null,  'attributes' => ['colour' => 'Black']],
                    ['name' => 'Olive',     'price' => null,  'attributes' => ['colour' => 'Olive']],
                ],
            ],

            // ── ACCESSORIES: JEWELLERY ───────────────────────────────────────
            [
                'name'              => 'Gold Drop Earrings',
                'sku'               => 'ST-AJE-016',
                'price'             => 29.99,
                'compare_price'     => null,
                'stock'             => 45,
                'categories'        => ['jewellery', 'accessories', 'new-arrivals'],
                'image'             => $img['earrings'],
                'short_description' => '18ct gold-plated drop earrings with freshwater pearl — effortlessly elegant.',
                'description'       => "A timeless drop earring that works equally well with casual and dressed-up looks. 18ct gold plating over sterling silver, set with a genuine freshwater pearl.\n\nMaterial: Sterling silver with 18ct gold plate | Freshwater pearl\nEar fitting: French hook (with butterfly back)\nDimensions: 3.5 cm drop",
                'weight'            => 0.02,
                'dimensions'        => ['length' => 5, 'width' => 3, 'height' => 1, 'unit' => 'cm'],
            ],
            [
                'name'              => 'Delicate Gold Chain Necklace',
                'sku'               => 'ST-AJN-017',
                'price'             => 34.99,
                'compare_price'     => null,
                'stock'             => 50,
                'is_featured'       => true,
                'categories'        => ['jewellery', 'accessories'],
                'image'             => $img['necklace'],
                'image2'            => $img['earrings'],
                'short_description' => 'Minimal 18ct gold-plated thin chain necklace — the everyday necklace you\'ll never take off.',
                'description'       => "The ultimate everyday necklace. Fine-gauge chain in 18ct gold plate over sterling silver, finished with a secure lobster clasp. Layer it with other chains or wear alone for understated elegance.\n\nMaterial: Sterling silver with 18ct gold plate\nLength: 45 cm + 5 cm extender\nClosure: Lobster clasp",
                'weight'            => 0.01,
                'dimensions'        => ['length' => 10, 'width' => 5, 'height' => 1, 'unit' => 'cm'],
            ],

            // ── ACCESSORIES: SHOES ───────────────────────────────────────────
            [
                'name'              => 'White Canvas Slip-On Sneakers',
                'sku'               => 'ST-ASS-018',
                'price'             => 69.99,
                'compare_price'     => 85.00,
                'stock'             => 55,
                'is_featured'       => true,
                'categories'        => ['shoes', 'accessories'],
                'image'             => $img['sneakers'],
                'image2'            => $img['boots'],
                'short_description' => 'Clean white canvas slip-ons with vulcanised sole — the classic everyday trainer.',
                'description'       => "The perfect clean-cut canvas sneaker. Crafted from heavyweight unbleached cotton canvas with a classic rubber vulcanised sole. Slip-on design with elasticated gusset means you're ready in seconds.\n\nUpper: 100% cotton canvas | Sole: Vulcanised rubber\nSizes: EU 36–46 | Care: Spot clean with damp cloth",
                'weight'            => 0.45,
                'dimensions'        => ['length' => 32, 'width' => 12, 'height' => 12, 'unit' => 'cm'],
                'variants'          => [
                    ['name' => 'EU 38', 'price' => null, 'attributes' => ['size' => 'EU 38']],
                    ['name' => 'EU 39', 'price' => null, 'attributes' => ['size' => 'EU 39']],
                    ['name' => 'EU 40', 'price' => null, 'attributes' => ['size' => 'EU 40']],
                    ['name' => 'EU 41', 'price' => null, 'attributes' => ['size' => 'EU 41']],
                    ['name' => 'EU 42', 'price' => null, 'attributes' => ['size' => 'EU 42']],
                    ['name' => 'EU 43', 'price' => null, 'attributes' => ['size' => 'EU 43']],
                    ['name' => 'EU 44', 'price' => null, 'attributes' => ['size' => 'EU 44']],
                ],
            ],
            [
                'name'              => 'Tan Chelsea Ankle Boots',
                'sku'               => 'ST-ACA-019',
                'price'             => 119.99,
                'compare_price'     => null,
                'stock'             => 30,
                'is_featured'       => true,
                'categories'        => ['shoes', 'accessories', 'new-in-women'],
                'image'             => $img['boots'],
                'image2'            => $img['sneakers'],
                'short_description' => 'Leather Chelsea ankle boots in warm tan — the season\'s most-worn footwear.',
                'description'       => "Chelsea boots never go out of style. Our version is crafted from soft pebbled leather in a warm tan that pairs with everything from jeans to dresses. The block heel adds 4 cm of height with comfortable wearability all day.\n\nMaterial: 100% genuine leather upper | Leather lining | Rubber sole\nHeel height: 4 cm | Pull tabs at back\nSizes: EU 35–42",
                'weight'            => 0.65,
                'dimensions'        => ['length' => 35, 'width' => 15, 'height' => 15, 'unit' => 'cm'],
                'variants'          => [
                    ['name' => 'EU 36', 'price' => null, 'attributes' => ['size' => 'EU 36']],
                    ['name' => 'EU 37', 'price' => null, 'attributes' => ['size' => 'EU 37']],
                    ['name' => 'EU 38', 'price' => null, 'attributes' => ['size' => 'EU 38']],
                    ['name' => 'EU 39', 'price' => null, 'attributes' => ['size' => 'EU 39']],
                    ['name' => 'EU 40', 'price' => null, 'attributes' => ['size' => 'EU 40']],
                    ['name' => 'EU 41', 'price' => null, 'attributes' => ['size' => 'EU 41']],
                ],
            ],

            // ── EXTRAS / SALE ────────────────────────────────────────────────
            [
                'name'              => 'Patterned Silk Scarf',
                'sku'               => 'ST-APS-020',
                'price'             => 44.99,
                'compare_price'     => 60.00,
                'stock'             => 40,
                'categories'        => ['accessories', 'up-to-50-off'],
                'image'             => $img['unboxing'],
                'image2'            => $img['necklace'],
                'short_description' => '90 × 90 cm silk twill scarf in a hand-drawn botanical print — a gift-ready statement.',
                'description'       => "Pure silk twill in a 90 × 90 cm format — the classic square scarf that can be worn as a headscarf, neck scarf, bag accessory, or even a crop top. Printed with a hand-illustrated botanical garden print.\n\nMaterial: 100% silk twill | Dry clean\nDimensions: 90 × 90 cm | Rolled hem",
                'weight'            => 0.12,
                'dimensions'        => ['length' => 95, 'width' => 95, 'height' => 1, 'unit' => 'cm'],
            ],
            [
                'name'              => 'Classic Leather Belt',
                'sku'               => 'ST-ALB-021',
                'price'             => 34.99,
                'compare_price'     => null,
                'stock'             => 60,
                'categories'        => ['accessories', 'clearance'],
                'image'             => $img['boots'],
                'image2'            => $img['handbag'],
                'short_description' => 'Full-grain leather belt with a plain silver buckle — the essential finishing touch.',
                'description'       => "Crafted from the same full-grain Italian leather as our crossbody bag, this classic belt pairs with everything. The simple rectangular silver buckle keeps it timeless, and the belt is available in three widths for different applications.\n\nMaterial: Full-grain Italian leather\nWidth: 2.5 cm (trouser) | 3.5 cm (jeans)\nAvailable sizes: S (70–80 cm), M (80–90 cm), L (90–105 cm)",
                'weight'            => 0.20,
                'dimensions'        => ['length' => 100, 'width' => 4, 'height' => 1, 'unit' => 'cm'],
                'variants'          => [
                    ['name' => 'S – 2.5cm', 'price' => null, 'attributes' => ['size' => 'S', 'width' => '2.5cm']],
                    ['name' => 'M – 2.5cm', 'price' => null, 'attributes' => ['size' => 'M', 'width' => '2.5cm']],
                    ['name' => 'L – 2.5cm', 'price' => null, 'attributes' => ['size' => 'L', 'width' => '2.5cm']],
                    ['name' => 'S – 3.5cm', 'price' => null, 'attributes' => ['size' => 'S', 'width' => '3.5cm']],
                    ['name' => 'M – 3.5cm', 'price' => null, 'attributes' => ['size' => 'M', 'width' => '3.5cm']],
                ],
            ],
            [
                'name'              => 'Tortoiseshell Sunglasses',
                'sku'               => 'ST-ATS-022',
                'price'             => 39.99,
                'compare_price'     => 55.00,
                'stock'             => 35,
                'is_featured'       => false,
                'status'            => 'active',
                'categories'        => ['accessories', 'up-to-50-off'],
                'image'             => $img['unboxing'],
                'short_description' => 'Oversized tortoiseshell acetate frames with UV400 lenses — the summer essential.',
                'description'       => "Oversized round frames in a warm tortoiseshell acetate, fitted with gradient UV400 polarised lenses. Lightweight and comfortable for all-day wear.\n\nFrame: Italian acetate | Lenses: UV400 polarised (gradient brown)\nFrame width: 148 mm | Bridge: 20 mm | Temple: 145 mm\nIncludes: Case + cleaning cloth",
                'weight'            => 0.05,
                'dimensions'        => ['length' => 16, 'width' => 5, 'height' => 6, 'unit' => 'cm'],
            ],
        ];
    }
}
