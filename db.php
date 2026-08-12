<?php
$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_PORT = getenv('DB_PORT') ?: '3306';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_NAME = getenv('DB_NAME') ?: 'product-card';

function db(): PDO
{
    global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME;
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;

    try {
        $pdo = new PDO("mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec('CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        category VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        price INT NOT NULL,
        compare_price INT,
        image VARCHAR(500) NOT NULL,
        badge VARCHAR(100),
        rating DECIMAL(2,1) NOT NULL DEFAULT 5,
        stock INT NOT NULL DEFAULT 0,
        featured TINYINT(1) NOT NULL DEFAULT 0
    ) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        address TEXT NOT NULL,
        total INT NOT NULL,
        status ENUM(\'pending\',\'paid\',\'shipped\',\'delivered\',\'cancelled\') NOT NULL DEFAULT \'pending\',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price INT NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
    ) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB');

    if ((int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() === 0) {
        $products = [
            ['Luna Ceramic Vase','luna-vase','Home','A hand-finished stoneware vase with a quiet, sculptural silhouette.','4800',null,'https://images.unsplash.com/photo-1612196808214-b8e1d6145a8c?auto=format&fit=crop&w=900&q=85','New',4.9,20,1],
            ['Sienna Linen Throw','sienna-throw','Textiles','Soft, breathable linen woven in a warm clay tone for slow Sunday mornings.','7600','8900','https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?auto=format&fit=crop&w=900&q=85','Best seller',4.8,20,1],
            ['Arc Table Lamp','arc-lamp','Lighting','A softly curved lamp in brushed brass that brings a golden hour glow indoors.','12500',null,'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=900&q=85','',5.0,20,1],
            ['Dune Carryall','dune-carryall','Accessories','An everyday leather carryall with generous proportions and thoughtful pockets.','18900','21500','https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=900&q=85','Limited',4.9,20,1],
            ['Miro Glass Set','miro-glass','Kitchen','Four translucent tumblers made for sparkling water, long lunches, and good company.','6500',null,'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?auto=format&fit=crop&w=900&q=85','',4.7,20,0],
            ['Onda Serving Board','onda-board','Kitchen','A smooth oak serving board with a natural edge and a lifetime of stories ahead.','8200',null,'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=900&q=85','',4.8,20,0],
            ['Noma Candle','noma-candle','Home','Notes of cedar, fig, and black tea poured into a reusable amber glass.','3900',null,'https://images.unsplash.com/photo-1603006905003-be475563bc59?auto=format&fit=crop&w=900&q=85','',4.9,20,0],
            ['Solis Planter','solis-planter','Home','A tactile planter designed to let your favorite greens take center stage.','5400',null,'https://images.unsplash.com/photo-1485955900006-10f4d324d411?auto=format&fit=crop&w=900&q=85','',4.6,20,0],
        ];
        $stmt = $pdo->prepare('INSERT INTO products (name,slug,category,description,price,compare_price,image,badge,rating,stock,featured) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        foreach ($products as $product) $stmt->execute($product);
    }
    return $pdo;
}

function money(int $amount): string { return '₦' . number_format($amount); }

function securityHeaders(): void {
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Content-Security-Policy: default-src \'self\'; img-src \'self\' https://images.unsplash.com data:; style-src \'self\' \'unsafe-inline\' https://fonts.googleapis.com; font-src \'self\' https://fonts.gstatic.com; script-src \'self\' \'unsafe-inline\'; connect-src \'self\'; frame-ancestors \'self\'; base-uri \'self\'; form-action \'self\' https://checkout.paystack.com https://*.paystack.com');
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function requireCsrf(): void {
    $provided = $_POST['csrf_token'] ?? '';
    if (!is_string($provided) || !hash_equals(csrfToken(), $provided)) {
        http_response_code(403);
        exit('Invalid security token. Please go back and try again.');
    }
}

function products(string $category = '', string $search = ''): array {
    $query = 'SELECT * FROM products'; $params = []; $where = [];
    if ($category !== '' && $category !== 'All') { $where[] = 'category = ?'; $params[] = $category; }
    if ($search !== '') {
        $like = '%' . $search . '%';
        $where[] = '(name LIKE ? OR description LIKE ? OR category LIKE ?)';
        array_push($params, $like, $like, $like);
    }
    if ($where) $query .= ' WHERE ' . implode(' AND ', $where);
    $query .= ' ORDER BY featured DESC, id ASC';
    $stmt = db()->prepare($query); $stmt->execute($params); return $stmt->fetchAll();
}
function product(int $id): ?array { $stmt = db()->prepare('SELECT * FROM products WHERE id = ?'); $stmt->execute([$id]); return $stmt->fetch() ?: null; }
function cartItems(): array {
    $items = [];
    foreach ($_SESSION['cart'] ?? [] as $id => $qty) if ($item = product((int)$id)) { $item['quantity'] = (int)$qty; $items[] = $item; }
    return $items;
}
function cartTotal(): int { return array_reduce(cartItems(), fn($sum, $item) => $sum + ($item['price'] * $item['quantity']), 0); }

// ---------------------------------------------------------------------
// Admin: authentication
// ---------------------------------------------------------------------

function adminByEmail(string $email): ?array {
    $stmt = db()->prepare('SELECT * FROM admins WHERE email = ?');
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}

function admin(int $id): ?array {
    $stmt = db()->prepare('SELECT id, name, email, created_at FROM admins WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function requireAdmin(): array {
    if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
    $current = admin((int)$_SESSION['admin_id']);
    if (!$current) { unset($_SESSION['admin_id']); header('Location: login.php'); exit; }
    return $current;
}

// ---------------------------------------------------------------------
// Admin: product management
// ---------------------------------------------------------------------

function slugify(string $text): string {
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $text), '-'));
    return $slug !== '' ? $slug : 'item';
}

function saveProduct(array $data, ?int $id = null): int {
    $pdo = db();
    $existing = $id ? product($id) : null;
    $image = trim($data['image'] ?? '');
    if (!empty($_FILES['image_file']['name'])) {
        $image = storeProductImage($_FILES['image_file']);
    } elseif ($existing && $image === '') {
        $image = $existing['image'];
    }
    if ($image === '') throw new RuntimeException('Add an image URL or upload a product image.');
    $fields = [
        'name' => trim($data['name'] ?? ''),
        'category' => trim($data['category'] ?? ''),
        'description' => trim($data['description'] ?? ''),
        'price' => (int)($data['price'] ?? 0),
        'compare_price' => $data['compare_price'] !== '' ? (int)$data['compare_price'] : null,
        'image' => $image,
        'badge' => trim($data['badge'] ?? ''),
        'stock' => max(0, (int)($data['stock'] ?? 0)),
        'featured' => isset($data['featured']) ? 1 : 0,
    ];

    if ($id) {
        $stmt = $pdo->prepare('UPDATE products SET name=?, category=?, description=?, price=?, compare_price=?, image=?, badge=?, stock=?, featured=? WHERE id=?');
        $stmt->execute([...array_values($fields), $id]);
        return $id;
    }

    $slug = slugify($fields['name']);
    $base = $slug; $n = 2;
    while (product_by_slug($slug)) { $slug = $base . '-' . $n; $n++; }

    $stmt = $pdo->prepare('INSERT INTO products (name,slug,category,description,price,compare_price,image,badge,stock,featured) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([
        $fields['name'], $slug, $fields['category'], $fields['description'],
        $fields['price'], $fields['compare_price'], $fields['image'], $fields['badge'],
        $fields['stock'], $fields['featured'],
    ]);
    return (int)$pdo->lastInsertId();
}

function storeProductImage(array $file): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image upload failed.');
    }
    if ((int)($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Product images must be 5MB or smaller.');
    }
    $tmp = $file['tmp_name'] ?? '';
    $info = @getimagesize($tmp);
    $mime = $info['mime'] ?? '';
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if (!$info || !isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG, WEBP, and GIF images are allowed.');
    }
    $directory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products';
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        throw new RuntimeException('The product upload folder could not be created.');
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($tmp, $directory . DIRECTORY_SEPARATOR . $filename)) {
        throw new RuntimeException('The product image could not be saved.');
    }
    return 'uploads/products/' . $filename;
}

function product_by_slug(string $slug): ?array {
    $stmt = db()->prepare('SELECT * FROM products WHERE slug = ?');
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function deleteProduct(int $id): void {
    $stmt = db()->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$id]);
}

// ---------------------------------------------------------------------
// Admin: order management
// ---------------------------------------------------------------------

function orders(string $status = ''): array {
    $query = 'SELECT * FROM orders'; $params = [];
    if ($status !== '' && $status !== 'All') { $query .= ' WHERE status = ?'; $params[] = $status; }
    $query .= ' ORDER BY created_at DESC';
    $stmt = db()->prepare($query); $stmt->execute($params);
    $list = $stmt->fetchAll();
    foreach ($list as &$orderRow) {
        $orderRow['products'] = orderItemsFor((int)$orderRow['id']);
    }
    unset($orderRow);
    return $list;
}

function order(int $id): ?array {
    $stmt = db()->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function orderItemsFor(int $orderId): array {
    $stmt = db()->prepare('SELECT oi.*, p.name, p.image, p.category FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ? ORDER BY oi.id ASC');
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

function updateOrderStatus(int $id, string $status): void {
    $allowed = ['pending', 'paid', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($status, $allowed, true)) return;
    $stmt = db()->prepare('UPDATE orders SET status = ? WHERE id = ?');
    $stmt->execute([$status, $id]);
}

// ---------------------------------------------------------------------
// Admin: dashboard summary
// ---------------------------------------------------------------------

function dashboardStats(): array {
    $pdo = db();
    return [
        'pending_orders' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
        'low_stock' => (int)$pdo->query('SELECT COUNT(*) FROM products WHERE stock <= 5')->fetchColumn(),
        'today_revenue' => (int)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'")->fetchColumn(),
        'total_products' => (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    ];
}
