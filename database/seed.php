<?php
require_once __DIR__ . '/../config/db.php';
try {
    $pdo = getDB();

    echo "Connected to database successfully.\n";

    // Clear existing data to allow re-runs
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE reviews;");
    $pdo->exec("TRUNCATE TABLE wishlists;");
    $pdo->exec("TRUNCATE TABLE order_items;");
    $pdo->exec("TRUNCATE TABLE orders;");
    $pdo->exec("TRUNCATE TABLE products;");
    $pdo->exec("TRUNCATE TABLE coupons;");
    $pdo->exec("TRUNCATE TABLE blogs;");
    $pdo->exec("TRUNCATE TABLE newsletter;");
    $pdo->exec("TRUNCATE TABLE contacts;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "Existing tables truncated.\n";

    // 1. Seed Products
    $products = [
        [
            'brand' => 'Elixir & Co.',
            'name' => 'Rose Elixir',
            'gender' => 'Women',
            'fragrance_type' => 'Floral',
            'price' => 3500.00,
            'discount_price' => 2999.00,
            'description' => 'Soft floral luxury. A delicate yet captivating blend of fresh roses, sweet jasmine, and warm white musk designed to leave a lasting impression.',
            'top_notes' => 'Rose, Jasmine',
            'heart_notes' => 'White Musk',
            'base_notes' => 'Vanilla, Sandalwood',
            'image_url' => 'coco_mademoiselle.jpg',
            'stock' => 50,
            'rating' => 4.9,
            'is_best_seller' => 1,
            'is_new_arrival' => 1
        ],
        [
            'brand' => 'Elixir & Co.',
            'name' => 'Amber Noir',
            'gender' => 'Unisex',
            'fragrance_type' => 'Oriental',
            'price' => 4200.00,
            'discount_price' => 3500.00,
            'description' => 'Warm and sophisticated. An opulent scent featuring rich notes of golden amber, creamy vanilla, and deep sandalwood for the modern individual.',
            'top_notes' => 'Amber, Bergamot',
            'heart_notes' => 'Vanilla, Jasmine',
            'base_notes' => 'Sandalwood, Patchouli',
            'image_url' => 'creed_aventus.jpg',
            'stock' => 40,
            'rating' => 4.8,
            'is_best_seller' => 1,
            'is_new_arrival' => 1
        ],
        [
            'brand' => 'Elixir & Co.',
            'name' => 'Citrus Essence',
            'gender' => 'Men',
            'fragrance_type' => 'Fresh',
            'price' => 3800.00,
            'discount_price' => 3200.00,
            'description' => 'Fresh and vibrant. A sparkling fragrance capturing the essence of sunny bergamot, zesty lemon, and a warm, clean musk trail.',
            'top_notes' => 'Bergamot, Lemon',
            'heart_notes' => 'Orange Blossom, Grapefruit',
            'base_notes' => 'White Musk, Cedarwood',
            'image_url' => 'acqua_di_gio.jpg',
            'stock' => 60,
            'rating' => 4.7,
            'is_best_seller' => 1,
            'is_new_arrival' => 1
        ],
        [
            'brand' => 'Elixir & Co.',
            'name' => 'Velvet Bloom',
            'gender' => 'Women',
            'fragrance_type' => 'Floral',
            'price' => 2199.00,
            'discount_price' => 1899.00,
            'description' => 'A sweet, delicate floral blend highlighting tuberose, exotic ylang-ylang, and pure white musk for an elegant signature trail.',
            'top_notes' => 'Tuberose, Ylang-Ylang',
            'heart_notes' => 'Jasmine, Lily',
            'base_notes' => 'White Musk, Vanilla',
            'image_url' => 'jadore.jpg',
            'stock' => 35,
            'rating' => 4.9,
            'is_best_seller' => 1,
            'is_new_arrival' => 0
        ],
        [
            'brand' => 'Elixir & Co.',
            'name' => 'Oud Majesty',
            'gender' => 'Unisex',
            'fragrance_type' => 'Woody',
            'price' => 2499.00,
            'discount_price' => 2199.00,
            'description' => 'An opulent, deep fusion of precious agarwood (oud), dark patchouli, and rare saffron spices, embodying true majestic luxury.',
            'top_notes' => 'Oud, Patchouli',
            'heart_notes' => 'Saffron, Incense',
            'base_notes' => 'Amber, Leather',
            'image_url' => 'black_orchid.jpg',
            'stock' => 30,
            'rating' => 4.8,
            'is_best_seller' => 1,
            'is_new_arrival' => 0
        ],
        [
            'brand' => 'Elixir & Co.',
            'name' => 'Forest Essence',
            'gender' => 'Men',
            'fragrance_type' => 'Woody',
            'price' => 2099.00,
            'discount_price' => 1799.00,
            'description' => 'A crisp, bracing walk through a snow-peaked evergreen forest, blending fresh pine needles, rich cedarwood, and smoky vetiver.',
            'top_notes' => 'Pine, Cypress',
            'heart_notes' => 'Cedarwood, Juniper',
            'base_notes' => 'Vetiver, Oakmoss',
            'image_url' => 'versace_eros.jpg',
            'stock' => 45,
            'rating' => 4.7,
            'is_best_seller' => 1,
            'is_new_arrival' => 0
        ],
        [
            'brand' => 'Elixir & Co.',
            'name' => 'Musk Noir',
            'gender' => 'Unisex',
            'fragrance_type' => 'Musk',
            'price' => 2199.00,
            'discount_price' => 1899.00,
            'description' => 'A dark, sensual skin scent blending clean musk accords, warm tonka bean, and a deep golden amber glow.',
            'top_notes' => 'Musk, Bergamot',
            'heart_notes' => 'Tonka Bean, Rose',
            'base_notes' => 'Amber, Patchouli',
            'image_url' => 'ysl_libre.jpg',
            'stock' => 40,
            'rating' => 4.8,
            'is_best_seller' => 1,
            'is_new_arrival' => 0
        ],
        [
            'brand' => 'Elixir & Co.',
            'name' => 'Vanilla Luxe',
            'gender' => 'Women',
            'fragrance_type' => 'Oriental',
            'price' => 1999.00,
            'discount_price' => 1699.00,
            'description' => 'A decadent, gourmand masterpiece combining pure Madagascar vanilla pods, sweet chocolate praline, and warm, comforting musk.',
            'top_notes' => 'Vanilla, Coconut',
            'heart_notes' => 'Praline, Heliotrope',
            'base_notes' => 'Musk, Amber',
            'image_url' => 'chanel_no5.jpg',
            'stock' => 50,
            'rating' => 4.9,
            'is_best_seller' => 1,
            'is_new_arrival' => 0
        ],
        [
            'brand' => 'Dior',
            'name' => 'Sauvage Eau de Parfum',
            'gender' => 'Men',
            'fragrance_type' => 'Woody',
            'price' => 9999.00,
            'discount_price' => 9499.00,
            'description' => 'A powerful and noble fragrance. Raw and noble all at once, Sauvage features notes of Calabrian bergamot, Sichuan pepper, and ambroxan, creating a signature trail that is instantly recognizable.',
            'top_notes' => 'Reggio Bergamot, Sichuan Pepper',
            'heart_notes' => 'Lavender, Pink Pepper, Vetiver, Patchouli',
            'base_notes' => 'Ambroxan, Cedar, Labdanum',
            'image_url' => 'sauvage.jpg',
            'stock' => 25,
            'rating' => 4.8,
            'is_best_seller' => 1,
            'is_new_arrival' => 0
        ],
        [
            'brand' => 'Chanel',
            'name' => 'No. 5 L’Eau',
            'gender' => 'Women',
            'fragrance_type' => 'Floral',
            'price' => 12500.00,
            'discount_price' => 11999.00,
            'description' => 'The definition of elegance and luxury. Chanel No. 5 is an exquisite floral bouquet highlighted by synthetic aldehydes, delicate jasmine, May rose, and a base of soft vanilla and sandalwood.',
            'top_notes' => 'Aldehydes, Ylang-Ylang, Neroli, Bergamot, Lemon',
            'heart_notes' => 'Iris, Jasmine, Orris Root, Rose, Lily-of-the-Valley',
            'base_notes' => 'Amber, Sandalwood, Patchouli, Musk, Civet, Vanilla, Oakmoss, Vetiver',
            'image_url' => 'chanel_no5.jpg',
            'stock' => 15,
            'rating' => 4.9,
            'is_best_seller' => 1,
            'is_new_arrival' => 0
        ],
        [
            'brand' => 'Creed',
            'name' => 'Aventus Cologne',
            'gender' => 'Men',
            'fragrance_type' => 'Fresh',
            'price' => 27999.00,
            'discount_price' => 25999.00,
            'description' => 'The exceptional Aventus celebrates strength, power, and success. Featuring pineapple, birch, Moroccan jasmine, and ambergris, it is the ultimate status symbol in perfumery.',
            'top_notes' => 'Pineapple, Bergamot, Blackcurrant, Apple',
            'heart_notes' => 'Birch, Patchouli, Moroccan Jasmine, Rose',
            'base_notes' => 'Musk, Oakmoss, Ambergris, Vanilla',
            'image_url' => 'creed_aventus.jpg',
            'stock' => 10,
            'rating' => 4.9,
            'is_best_seller' => 1,
            'is_new_arrival' => 1
        ],
        [
            'brand' => 'Tom Ford',
            'name' => 'Black Orchid',
            'gender' => 'Unisex',
            'fragrance_type' => 'Oriental',
            'price' => 15500.00,
            'discount_price' => 14500.00,
            'description' => 'A luxurious and sensual fragrance of rich, dark accords and an alluring potion of black orchids and spice, Tom Ford Black Orchid is both modern and timeless.',
            'top_notes' => 'Truffle, Gardenia, Blackcurrant, Ylang-Ylang, Jasmine, Bergamot, Mandarin Orange, Amalfi Lemon',
            'heart_notes' => 'Orchid, Spices, Gardenia, Fruity Notes, Ylang-Ylang, Jasmine, Lotus',
            'base_notes' => 'Mexican Chocolate, Patchouli, Vanille, Incense, Amber, Sandalwood, Vetiver, White Musk',
            'image_url' => 'black_orchid.jpg',
            'stock' => 18,
            'rating' => 4.7,
            'is_best_seller' => 0,
            'is_new_arrival' => 0
        ],
        [
            'brand' => 'Armani',
            'name' => 'Acqua Di Giò Profondo',
            'gender' => 'Men',
            'fragrance_type' => 'Citrus',
            'price' => 9500.00,
            'discount_price' => 8499.00,
            'description' => 'A contemporary and intense interpretation of Acqua Di Giò, blending marine notes with aromatic essences and a woody marine accord to bring the fragrance back to its origins: the sea.',
            'top_notes' => 'Marine Notes, Aquozone, Green Mandarin, Bergamot',
            'heart_notes' => 'Rosemary, Lavender, Cypress, Mastic or Lentisque',
            'base_notes' => 'Mineral Notes, Musk, Patchouli, Amber',
            'image_url' => 'acqua_di_gio.jpg',
            'stock' => 30,
            'rating' => 4.6,
            'is_best_seller' => 0,
            'is_new_arrival' => 1
        ],
        [
            'brand' => 'Versace',
            'name' => 'Eros Eau de Toilette',
            'gender' => 'Men',
            'fragrance_type' => 'Musk',
            'price' => 7999.00,
            'discount_price' => 6999.00,
            'description' => 'Love, passion, beauty and desire. Versace Eros is a fragrance for a strong, passionate man who is master of himself. Mint leaves, Italian lemon zest and green apple fuse to form a glowing aura.',
            'top_notes' => 'Mint, Green Apple, Lemon',
            'heart_notes' => 'Tonka Bean, Ambroxan, Geranium',
            'base_notes' => 'Madagascar Vanilla, Virginian Cedar, Atlas Cedar, Vetiver, Oakmoss',
            'image_url' => 'versace_eros.jpg',
            'stock' => 40,
            'rating' => 4.5,
            'is_best_seller' => 1,
            'is_new_arrival' => 0
        ],
        [
            'brand' => 'YSL',
            'name' => 'Libre Intense',
            'gender' => 'Women',
            'fragrance_type' => 'Floral',
            'price' => 11500.00,
            'discount_price' => 9999.00,
            'description' => 'The fragrance of freedom, Libre Intense features lavender essence from France combined with the sensuality of Moroccan orange blossom and a burning orchid accord.',
            'top_notes' => 'Lavender, Mandarin Orange, Bergamot',
            'heart_notes' => 'Lavender, Tunisian Orange Blossom, Orchid, Jasmine Sambac',
            'base_notes' => 'Madagascar Vanilla, Tonka Bean, Ambergris, Vetiver',
            'image_url' => 'ysl_libre.jpg',
            'stock' => 20,
            'rating' => 4.7,
            'is_best_seller' => 0,
            'is_new_arrival' => 1
        ],
        [
            'brand' => 'Dior',
            'name' => 'J’adore Eau de Parfum',
            'gender' => 'Women',
            'fragrance_type' => 'Floral',
            'price' => 12000.00,
            'discount_price' => 10999.00,
            'description' => 'An ode to women, their audacity and their beauty. J’adore is a grand feminine floral fragrance by Dior, featuring a bouquet finely crafted to the last detail, like a custom-made flower.',
            'top_notes' => 'Pear, Melon, Magnolia, Peach, Mandarin Orange, Bergamot',
            'heart_notes' => 'Jasmine, Lily-of-the-Valley, Tuberose, Freesia, Rose, Orchid, Plum',
            'base_notes' => 'Musk, Vanilla, Blackberry, Cedar',
            'image_url' => 'jadore.jpg',
            'stock' => 22,
            'rating' => 4.8,
            'is_best_seller' => 0,
            'is_new_arrival' => 0
        ],
        [
            'brand' => 'Chanel',
            'name' => 'Coco Mademoiselle',
            'gender' => 'Women',
            'fragrance_type' => 'Oriental',
            'price' => 13500.00,
            'discount_price' => 12499.00,
            'description' => 'Spirited and voluptuous. Coco Mademoiselle is a double name, a double personality. Independent and endearing, mischievous and provocative, free and reinventing herself.',
            'top_notes' => 'Orange, Mandarin Orange, Bergamot, Orange Blossom',
            'heart_notes' => 'Turkish Rose, Jasmine, Mimosa, Ylang-Ylang',
            'base_notes' => 'Patchouli, White Musk, Vanilla, Vetiver, Tonka Bean, Opoponax',
            'image_url' => 'coco_mademoiselle.jpg',
            'stock' => 16,
            'rating' => 4.9,
            'is_best_seller' => 1,
            'is_new_arrival' => 0
        ]
    ];

    $productStmt = $pdo->prepare("INSERT INTO products (brand, name, gender, fragrance_type, price, discount_price, description, top_notes, heart_notes, base_notes, image_url, image_url_2, image_url_3, image_url_4, stock, rating, is_best_seller, is_new_arrival) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($products as $p) {
        $productStmt->execute([
            $p['brand'], $p['name'], $p['gender'], $p['fragrance_type'], $p['price'], $p['discount_price'],
            $p['description'], $p['top_notes'], $p['heart_notes'], $p['base_notes'], $p['image_url'],
            null, null, null,
            $p['stock'], $p['rating'], $p['is_best_seller'], $p['is_new_arrival']
        ]);
    }
    echo "Products seeded successfully.\n";

    // 2. Seed Coupons
    $coupons = [
        ['code' => 'LUXURY10', 'discount_type' => 'percent', 'value' => 10.00, 'active' => 1],
        ['code' => 'GOLD2000', 'discount_type' => 'fixed', 'value' => 2000.00, 'active' => 1],
        ['code' => 'WELCOME20', 'discount_type' => 'percent', 'value' => 20.00, 'active' => 1]
    ];

    $couponStmt = $pdo->prepare("INSERT INTO coupons (code, discount_type, value, active) VALUES (?, ?, ?, ?)");
    foreach ($coupons as $c) {
        $couponStmt->execute([$c['code'], $c['discount_type'], $c['value'], $c['active']]);
    }
    echo "Coupons seeded successfully.\n";

    // 3. Seed Blogs
    $blogs = [
        [
            'title' => 'How to Choose Your Signature Scent',
            'excerpt' => 'Discover the secret to finding a fragrance that perfectly expresses your unique personality and style.',
            'content' => 'Your signature scent is more than just a perfume; it is a personal statement. To find it, you must understand fragrance families: Floral, Oriental, Woody, and Fresh. Start by testing lighter eau de toilettes before plunging into richer extraits. Always test on your skin rather than paper, as the chemistry of your body alters the scent profile. In this guide, we dive deep into the olfactory pyramid and explain how top notes give way to the long-lasting base notes that will linger on your skin throughout the day.',
            'image' => 'blog_signature.jpg',
            'category' => 'Fragrance Guides'
        ],
        [
            'title' => 'The Art of Gifting Luxury Perfumes',
            'excerpt' => 'Selecting a fragrance for someone else is a delicate art. Follow our master guide to perfect gifting.',
            'content' => 'Gifting perfume is an intimate gesture of affection, but it can be challenging. To select the perfect bottle, look for clues in the recipient\'s style: do they wear bold fashion statement pieces or lean towards classic tailoring? Woody and fresh scents are generally safe and sophisticated options, while opulent amber and spice fragrances make gorgeous evening gifts. Learn how packaging and personalized notes can turn a perfume bottle into an unforgettable luxury experience.',
            'image' => 'blog_gifting.jpg',
            'category' => 'Gift Ideas'
        ],
        [
            'title' => 'Behind the Bottles: The Craftsmanship of High Perfumery',
            'excerpt' => 'Go inside the world\'s most elite fragrance labs to see how precious raw materials become liquid gold.',
            'content' => 'High-end luxury perfumes utilize the rarest ingredients on earth: Jasmine from Grasse, Oud from Laos, and Rose de Mai. Harvesting these materials is a labor-intensive tradition that has survived for centuries. A single ounce of pure rose oil requires tens of thousands of handpicked petals. Discover how master perfumers, known as "noses", spend years balancing top, heart, and base notes to create sensory masterpieces that tell stories on your skin.',
            'image' => 'blog_craftsmanship.jpg',
            'category' => 'Luxury Lifestyle'
        ]
    ];

    $blogStmt = $pdo->prepare("INSERT INTO blogs (title, excerpt, content, image, category) VALUES (?, ?, ?, ?, ?)");
    foreach ($blogs as $b) {
        $blogStmt->execute([$b['title'], $b['excerpt'], $b['content'], $b['image'], $b['category']]);
    }
    echo "Blogs seeded successfully.\n";
    echo "All seed data applied.\n";

} catch (PDOException $e) {
    die("Database seeding failed: " . $e->getMessage() . "\n");
}
