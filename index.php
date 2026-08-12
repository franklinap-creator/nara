<?php
declare(strict_types=1);
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_start();
require __DIR__ . '/db.php';
securityHeaders();

$page = $_GET['page'] ?? 'home';
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $id = (int)($_POST['product_id'] ?? 0);
        $item = product($id);
        $requested = max(1, (int)($_POST['qty'] ?? 1));
        if ($item) {
            $available = (int)$item['stock'];
            $inCart = (int)($_SESSION['cart'][$id] ?? 0);
            $qty = min($requested, max(0, $available - $inCart));
            if ($qty > 0) $_SESSION['cart'][$id] = $inCart + $qty;
        }
        $from = $_POST['from'] ?? 'shop';
        if ($from === 'product' && $id) {
            header('Location: index.php?page=product&id=' . $id . '&added=1'); exit;
        }
        header('Location: index.php?page=shop&added=1'); exit;
    }
    if ($action === 'update') {
        foreach ($_POST['qty'] ?? [] as $id => $qty) { $qty = max(0, min(20, (int)$qty)); if ($qty === 0) unset($_SESSION['cart'][$id]); else $_SESSION['cart'][$id] = $qty; }
        header('Location: index.php?page=cart'); exit;
    }
    if ($action === 'checkout' && cartItems()) {
        $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? ''); $address = trim($_POST['address'] ?? '');
        if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && $address) {
            $_SESSION['pending_order'] = [
                'name' => $name,
                'email' => $email,
                'address' => $address,
            ];
            header('Location: payment-init.php'); exit;
        } else $notice = 'Please enter a name, valid email, and delivery address.';
    }
}
$cartCount = array_sum($_SESSION['cart'] ?? []); $cart = cartItems(); $total = cartTotal();
$category = $_GET['category'] ?? 'All'; $search = trim((string)($_GET['q'] ?? '')); $items = products($category, $search);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>nara — considered objects for everyday living</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="announcement">Free delivery on orders over ₦30,000 <span>✦</span> Thoughtfully made, always</div>
    <header><a class="logo" href="index.php"><img src="LOGO1.png" alt="nara"></a>
        <nav><a href="index.php">Home</a><a href="index.php?page=shop">Shop</a><a
                href="index.php?page=shop&category=Home">Homeware</a><a
                href="index.php?page=shop&category=Accessories">Accessories</a></nav><a class="cart-link"
            href="index.php?page=cart">Bag <b><?= $cartCount ?></b></a>
    </header>
    <?php if ($page === 'home'): ?><main>
        <section class="hero">
            <div>
                <p class="eyebrow">The new everyday</p>
                <h1>Objects with<br><em>a little soul.</em></h1>
                <p class="hero-copy">A considered collection for the way you live now. Made slowly, chosen carefully.
                </p><a class="button dark" href="index.php?page=shop">Explore the collection <span>↗</span></a>
            </div>
            <div class="hero-art"><img
                    src="https://images.unsplash.com/photo-1604014237800-1c9102c219da?auto=format&fit=crop&w=1200&q=85"
                    alt="Warmly styled home interior">
                <div class="art-tag">01 / 04<br><strong>Softly lived</strong></div>
            </div>
        </section>
        <section class="ticker"><span>Small batch</span><i>✦</i><span>Made to last</span><i>✦</i><span>Good things,
                slowly</span><i>✦</i><span>Small batch</span></section>
        <section class="intro">
            <p class="eyebrow">Why nara</p>
            <h2>Less, but better.</h2>
            <p>We believe the things around us should earn their place. Nara brings together useful, beautiful pieces
                that make ordinary rituals feel a little more special.</p>
        </section>
        <section class="featured">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Curated for you</p>
                    <h2>Our favorites</h2>
                </div><a href="index.php?page=shop">View all <span>↗</span></a>
            </div>
            <div class="product-grid">
                <?php foreach (array_slice(products(),0,4) as $item): ?><?php include __DIR__.'/product_card.php'; ?><?php endforeach; ?>
            </div>
        </section>
    </main>
    <?php elseif ($page === 'shop'): ?><main class="shop">
        <div class="shop-heading">
            <p class="eyebrow">The collection</p>
            <h1>Find your everyday.</h1>
            <p>Pieces with presence, made for the life you actually live.</p>
        </div>
        <form class="search-form" method="get">
            <input type="hidden" name="page" value="shop">
            <input type="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search products..." aria-label="Search products">
            <button type="submit">Search</button>
        </form>
        <div class="filters">
            <div><?php foreach (['All','Home','Textiles','Lighting','Kitchen','Accessories'] as $cat): ?><a
                    class="<?= $category === $cat ? 'active':'' ?>"
                    href="index.php?page=shop&category=<?= urlencode($cat) ?><?= $search !== '' ? '&q=' . urlencode($search) : '' ?>"><?= $cat ?></a><?php endforeach; ?></div>
            <small><?= count($items) ?> pieces</small>
        </div><?php if (isset($_GET['added'])): ?><div class="toast">Added to your bag </div><?php endif; ?><div
            class="product-grid">
            <?php foreach ($items as $item): ?><?php include __DIR__.'/product_card.php'; ?><?php endforeach; ?></div>
    </main>
    <?php elseif ($page === 'cart'): ?><main class="cart-page">
        <div class="shop-heading">
            <p class="eyebrow">Your bag</p>
            <h1>Take your time.</h1>
        </div><?php if (!$cart): ?><div class="empty">
            <p>Your bag is waiting for something lovely.</p><a class="button dark" href="index.php?page=shop">Continue
                shopping <span>↗</span></a>
        </div><?php else: ?><form method="post"><?= csrfField() ?><input type="hidden" name="action" value="update">
            <div class="cart-layout">
                <div><?php foreach ($cart as $item): ?><div class="cart-row"><img
                            src="<?= htmlspecialchars($item['image']) ?>" alt="">
                        <div>
                            <h3><?= htmlspecialchars($item['name']) ?></h3>
                            <p><?= htmlspecialchars($item['category']) ?></p>
                        </div>
                        <div class="qty"><button type="button" onclick="stepQty(this,-1)">−</button><input
                                name="qty[<?= $item['id'] ?>]" value="<?= $item['quantity'] ?>"><button type="button"
                                onclick="stepQty(this,1)">+</button></div>
                        <strong><?= money($item['price']*$item['quantity']) ?></strong>
                    </div><?php endforeach; ?><button class="text-button" type="submit">Update bag</button></div>
                <aside class="summary">
                    <p class="eyebrow">Summary</p>
                    <div><span>Subtotal</span><strong><?= money($total) ?></strong></div>
                    <div><span>Delivery</span><span><?= $total >= 30000 ? 'Free' : money(1800) ?></span></div>
                    <hr>
                    <div class="grand">
                        <span>Total</span><strong><?= money($total + ($total >= 30000 ? 0 : 1800)) ?></strong>
                    </div><a class="button dark full" href="index.php?page=checkout">Checkout <span>↗</span></a>
                </aside>
            </div>
        </form><?php endif; ?>
    </main>
    <?php elseif ($page === 'checkout'): ?><main class="checkout">
        <div class="shop-heading">
            <p class="eyebrow">Almost there</p>
            <h1>Make it yours.</h1>
        </div><?php if ($notice): ?><div class="notice"><?= htmlspecialchars($notice) ?></div><?php endif; ?><div
            class="checkout-layout">
            <form method="post" class="checkout-form"><?= csrfField() ?><input type="hidden" name="action" value="checkout"><label>Full
                    name<input name="name" required></label><label>Email address<input type="email" name="email"
                        required></label><label>Delivery address<textarea name="address" rows="4"
                        required></textarea></label><button class="button dark" type="submit">Place order
                    <span>↗</span></button></form>
            <aside class="summary">
                <p class="eyebrow">Your order</p><?php foreach($cart as $item): ?><div><span><?= $item['quantity'] ?> ×
                        <?= htmlspecialchars($item['name']) ?></span><strong><?= money($item['price']*$item['quantity']) ?></strong>
                </div><?php endforeach; ?>
                <hr>
                <div class="grand">
                    <span>Total</span><strong><?= money($total + ($total >= 30000 ? 0 : 1800)) ?></strong>
                </div>
            </aside>
        </div>
    </main>
    <?php elseif ($page === 'success'): ?><main class="success">
        <div class="success-mark">✓</div>
        <p class="eyebrow">Order received</p>
        <h1>Thank you, <?= htmlspecialchars($_GET['name'] ?? 'friend') ?>.</h1>
        <p>Your order <strong>#<?= (int)($_GET['order'] ?? 0) ?></strong> is on its way to becoming something special.
            We'll be in touch with delivery details soon.</p><a class="button dark" href="index.php?page=shop">Keep
            browsing <span>↗</span></a>
    </main>
    <?php elseif ($page === 'product'): ?><?php
        $productId = (int)($_GET['id'] ?? 0);
        $singleProduct = product($productId);
    ?><?php if (!$singleProduct): ?><main class="empty">
        <p>We couldn't find that piece.</p><a class="button dark" href="index.php?page=shop">Back to shop
            <span>↗</span></a>
    </main><?php else: ?><?php
        $stock = (int)$singleProduct['stock'];
        $inCart = (int)($_SESSION['cart'][$singleProduct['id']] ?? 0);
        $canAdd = max(0, $stock - $inCart);
        $fullStars = (int)round((float)$singleProduct['rating']);
        $discountPct = $singleProduct['compare_price']
            ? (int)round((($singleProduct['compare_price'] - $singleProduct['price']) / $singleProduct['compare_price']) * 100)
            : 0;
    ?><main class="product-detail">
        <?php if (isset($_GET['added'])): ?><div class="toast">Added to your bag </div><?php endif; ?>
        <div class="product-buy-layout">
            <div class="product-detail-media"><img src="<?= htmlspecialchars($singleProduct['image']) ?>"
                    alt="<?= htmlspecialchars($singleProduct['name']) ?>">
            </div>
            <div class="product-detail-body">
                <p class="eyebrow"><?= htmlspecialchars($singleProduct['category']) ?></p>
                <?php if ($singleProduct['badge']): ?><span
                    class="badge static"><?= htmlspecialchars($singleProduct['badge']) ?></span><?php endif; ?>
                <h1><?= htmlspecialchars($singleProduct['name']) ?></h1>
                <p class="product-detail-rating"><?= str_repeat('★', $fullStars) . str_repeat('☆', 5 - $fullStars) ?>
                    <span><?= number_format((float)$singleProduct['rating'], 1) ?> out of 5</span>
                </p>
                <p class="product-detail-description"><?= htmlspecialchars($singleProduct['description']) ?></p>
            </div>
            <aside class="buy-box">
                <?php if ($discountPct > 0): ?><p class="buy-discount">−<?= $discountPct ?>%</p><?php endif; ?>
                <p class="buy-price"><strong><?= money($singleProduct['price']) ?></strong>
                    <?php if ($singleProduct['compare_price']): ?><s><?= money($singleProduct['compare_price']) ?></s><?php endif; ?>
                </p>
                <p class="buy-stock <?= $stock <= 0 ? 'out' : ($stock <= 5 ? 'low' : 'in') ?>">
                    <?= $stock <= 0 ? 'Out of stock' : ($stock <= 5 ? 'Only ' . $stock . ' left in stock' : 'In stock') ?>
                </p>
                <?php if ($canAdd > 0): ?>
                <form method="post"><?= csrfField() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $singleProduct['id'] ?>">
                    <input type="hidden" name="from" value="product">
                    <label class="qty-label">Quantity
                        <select name="qty">
                            <?php for ($n = 1; $n <= min(10, $canAdd); $n++): ?>
                            <option value="<?= $n ?>"><?= $n ?></option>
                            <?php endfor; ?>
                        </select>
                    </label>
                    <button class="button dark full" type="submit">Add to bag <span>↗</span></button>
                </form>
                <?php else: ?>
                <button class="button dark full" type="button" disabled>Out of stock</button>
                <?php endif; ?>
                <p class="buy-delivery">Free delivery on orders over ₦30,000</p>
            </aside>
        </div>
        <section class="product-information">
            <div class="product-tabs" role="tablist">
                <button class="product-tab active" type="button" data-panel="description">▤ Description</button>
                <button class="product-tab" type="button" data-panel="specifications">☷ Specifications</button>
                <button class="product-tab" type="button" data-panel="box">◇ What's in the box</button>
                <button class="product-tab" type="button" data-panel="use">? How to use</button>
                <button class="product-tab" type="button" data-panel="reviews">☆ Reviews
                    (<?= number_format((float)$singleProduct['rating'], 1) ?>)</button>
                <button class="product-tab" type="button" data-panel="shipping">♧ Shipping & returns</button>
            </div>
            <div class="product-panels">
                <div class="product-panel active" data-panel-content="description">
                    <div class="info-card">
                        <h2>Product Overview</h2>
                        <p><?= htmlspecialchars($singleProduct['description']) ?></p>
                        <p><?= htmlspecialchars($singleProduct['name']) ?> is made to bring a considered, useful detail
                            to your everyday routine. Its simple form, lasting materials, and easy styling make it a
                            piece you can live with for years.</p>
                    </div>
                    <div class="info-card">
                        <h2>Key Features</h2>
                        <ul class="feature-list">
                            <li>Thoughtfully designed for everyday living</li>
                            <li>Durable materials and careful finishing</li>
                            <li>Easy to use, style, and care for</li>
                            <li>Made to complement a considered home</li>
                            <li>Quality checked before dispatch</li>
                        </ul>
                    </div>
                    <div class="info-card">
                        <h2>Benefits</h2>
                        <ul class="feature-list">
                            <li>Brings beauty and function to your routine</li>
                            <li>Comfortable, practical, and easy to maintain</li>
                            <li>Works beautifully with your existing pieces</li>
                            <li>Suitable for home, work, and everyday use</li>
                        </ul>
                    </div>
                    <div class="info-card">
                        <h2>Product Specifications</h2>
                        <dl class="spec-list">
                            <div>
                                <dt>Brand</dt>
                                <dd>nara</dd>
                            </div>
                            <div>
                                <dt>Category</dt>
                                <dd><?= htmlspecialchars($singleProduct['category']) ?></dd>
                            </div>
                            <div>
                                <dt>Product type</dt>
                                <dd><?= htmlspecialchars($singleProduct['name']) ?></dd>
                            </div>
                            <div>
                                <dt>Condition</dt>
                                <dd>Brand new</dd>
                            </div>
                            <div>
                                <dt>Availability</dt>
                                <dd><?= $stock > 0 ? 'In stock' : 'Out of stock' ?></dd>
                            </div>
                            <div>
                                <dt>Rating</dt>
                                <dd><?= number_format((float)$singleProduct['rating'], 1) ?> / 5</dd>
                            </div>
                        </dl>
                    </div>
                    <div class="info-card">
                        <h2>What's in the Box</h2>
                        <ul class="feature-list numbered">
                            <li>1 × <?= htmlspecialchars($singleProduct['name']) ?></li>
                            <li>nara care and product guide</li>
                        </ul>
                    </div>
                    <div class="info-card">
                        <h2>How to Use</h2>
                        <ol class="feature-list numbered">
                            <li>Unpack carefully and remove all protective material.</li>
                            <li>Place or use the piece according to your daily routine.</li>
                            <li>Follow the care guidance below to keep it looking its best.</li>
                        </ol>
                    </div>
                    <div class="info-card">
                        <h2>Care & Maintenance</h2>
                        <ul class="feature-list">
                            <li>Wipe clean with a soft, dry cloth.</li>
                            <li>Do not use abrasive cleaners or harsh chemicals.</li>
                            <li>Keep away from prolonged moisture and direct heat.</li>
                            <li>Store in a cool, dry place when not in use.</li>
                        </ul>
                    </div>
                    <div class="info-card">
                        <h2>Important Information</h2>
                        <p>Colours may appear slightly different depending on your screen and lighting. Natural
                            materials may show small variations in tone and texture; these are part of the character of
                            each piece.</p>
                    </div>
                    <div class="info-card split-card">
                        <div>
                            <h2>Warranty</h2>
                            <p>Covered by a 1-year limited warranty against manufacturing defects.</p>
                        </div>
                        <div>
                            <h2>Returns</h2>
                            <p>Return unused items within 7 days in their original condition and packaging.</p>
                        </div>
                    </div>
                </div>
                <div class="product-panel" data-panel-content="specifications">
                    <div class="info-card wide-card">
                        <h2>Product Specifications</h2>
                        <dl class="spec-list">
                            <div>
                                <dt>Brand</dt>
                                <dd>nara</dd>
                            </div>
                            <div>
                                <dt>Category</dt>
                                <dd><?= htmlspecialchars($singleProduct['category']) ?></dd>
                            </div>
                            <div>
                                <dt>Product</dt>
                                <dd><?= htmlspecialchars($singleProduct['name']) ?></dd>
                            </div>
                            <div>
                                <dt>Condition</dt>
                                <dd>Brand new</dd>
                            </div>
                            <div>
                                <dt>Stock available</dt>
                                <dd><?= $stock ?></dd>
                            </div>
                        </dl>
                    </div>
                </div>
                <div class="product-panel" data-panel-content="box">
                    <div class="info-card wide-card">
                        <h2>What's in the Box</h2>
                        <ul class="feature-list numbered">
                            <li>1 × <?= htmlspecialchars($singleProduct['name']) ?></li>
                            <li>nara care and product guide</li>
                        </ul>
                    </div>
                </div>
                <div class="product-panel" data-panel-content="use">
                    <div class="info-card wide-card">
                        <h2>How to Use</h2>
                        <ol class="feature-list numbered">
                            <li>Unpack carefully and remove all protective material.</li>
                            <li>Use and enjoy the piece as part of your everyday routine.</li>
                            <li>Follow the care guidance to keep it looking its best.</li>
                        </ol>
                    </div>
                </div>
                <div class="product-panel" data-panel-content="reviews">
                    <div class="info-card wide-card">
                        <h2>Customer Reviews</h2>
                        <p class="review-score">☆ <?= number_format((float)$singleProduct['rating'], 1) ?> <span>out of
                                5</span></p>
                        <p>Reviews for this product will appear here after verified purchases.</p>
                    </div>
                </div>
                <div class="product-panel" data-panel-content="shipping">
                    <div class="info-card wide-card">
                        <h2>Shipping & Returns</h2>
                        <p>Orders over ₦30,000 qualify for free delivery. Unused items may be returned within 7 days in
                            their original condition and packaging.</p>
                    </div>
                </div>
            </div>
        </section>
    </main><?php endif; ?>
    <?php else: ?><main class="empty">
        <p>Page not found.</p><a class="button dark" href="index.php">Back home <span>↗</span></a>
    </main><?php endif; ?><footer><a class="logo" href="index.php">nara<span>®</span></a>
        <p>Considered objects for everyday living.</p><small>© <?= date('Y') ?> nara studio</small>
    </footer>
    <script src="app.js"></script>
</body>

</html>
