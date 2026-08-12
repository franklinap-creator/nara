<?php
declare(strict_types=1);
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_start();
require __DIR__ . '/../db.php';
securityHeaders();

$currentAdmin = requireAdmin();
$page = 'products';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        try {
            saveProduct($_POST, $id);
        } catch (Throwable $error) {
            header('Location: products.php?' . ($id ? 'edit=' . $id : 'new=1') . '&error=' . urlencode($error->getMessage())); exit;
        }
        header('Location: products.php?saved=1'); exit;
    }
    if ($action === 'delete') {
        deleteProduct((int)($_POST['id'] ?? 0));
        header('Location: products.php?deleted=1'); exit;
    }
}

$editing = isset($_GET['edit']) ? product((int)$_GET['edit']) : null;
$showForm = isset($_GET['new']) || $editing;
$pageTitle = $showForm ? ($editing ? 'Edit Product' : 'New Product') : 'Products';
$allProducts = products();
$categories = ['Home', 'Textiles', 'Lighting', 'Kitchen', 'Accessories'];
$formError = $_GET['error'] ?? '';

require __DIR__ . '/partials.php';
?>

<?php if ($showForm): ?>
<div class="page-head">
    <div>
        <p class="eyebrow">Catalog</p>
        <h1><?= $editing ? 'Edit Product' : 'New Product' ?></h1>
    </div>
    <a class="button ghost" href="products.php">Back to list</a>
</div>

<div class="form-card">
    <?php if ($formError): ?><div class="form-error"><?= htmlspecialchars($formError) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data"><?= csrfField() ?>
        <input type="hidden" name="action" value="save">
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>
        <div class="form-grid">
            <label class="full">Name
                <input name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required>
            </label>
            <label>Category
                <select name="category" required>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat ?>" <?= ($editing['category'] ?? '') === $cat ? 'selected' : '' ?>>
                        <?= $cat ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Badge <span style="text-transform:none;color:var(--line);">(optional)</span>
                <input name="badge" value="<?= htmlspecialchars($editing['badge'] ?? '') ?>"
                    placeholder="New, Best seller, Limited…">
            </label>
            <label>Price (₦)
                <input type="number" name="price" min="0" value="<?= $editing['price'] ?? '' ?>" required>
            </label>
            <label>Compare-at price (₦) <span style="text-transform:none;color:var(--line);">(optional)</span>
                <input type="number" name="compare_price" min="0" value="<?= $editing['compare_price'] ?? '' ?>">
            </label>
            <label>Stock on hand
                <input type="number" name="stock" min="0" value="<?= $editing['stock'] ?? 0 ?>" required>
            </label>
            <label>Image URL <span style="text-transform:none;color:var(--line);">(optional)</span>
                <input name="image" value="<?= htmlspecialchars($editing['image'] ?? '') ?>">
            </label>
            <label>Upload image <span style="text-transform:none;color:var(--line);">(JPG, PNG, WEBP, GIF, max 5MB)</span>
                <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif">
            </label>
            <label class="full">Description
                <textarea name="description" rows="3"
                    required><?= htmlspecialchars($editing['description'] ?? '') ?></textarea>
            </label>
            <label class="full checkbox-row">
                <input type="checkbox" name="featured" <?= !empty($editing['featured']) ? 'checked' : '' ?>>
                <span style="text-transform:none;">Show on the homepage "Our favorites" section</span>
            </label>
        </div>
        <div class="form-actions">
            <button class="button" type="submit">Save Product</button>
            <a class="button ghost" href="products.php">Cancel</a>
        </div>
    </form>
</div>

<?php else: ?>
<div class="page-head">
    <div>
        <p class="eyebrow">Catalog</p>
        <h1>Products</h1>
    </div>
    <a class="button" href="products.php?new=1">+ Add product</a>
</div>

<?php if (!$allProducts): ?>
<div class="empty-state">No products yet — add your first one.</div>
<?php else: ?>
<table class="admin-table">
    <thead>
        <tr>
            <th></th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Featured</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($allProducts as $p): ?>
        <tr>
            <td><img src="<?= preg_match('/^https?:\/\//i', $p['image']) ? htmlspecialchars($p['image']) : '../' . htmlspecialchars($p['image']) ?>" alt=""></td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= htmlspecialchars($p['category']) ?></td>
            <td><?= money((int)$p['price']) ?></td>
            <td class="<?= $p['stock'] <= 5 ? 'stock-low' : '' ?>"><?= $p['stock'] ?></td>
            <td><?= $p['featured'] ? 'Yes' : '—' ?></td>
            <td>
                <div class="row-actions">
                    <a href="products.php?edit=<?= $p['id'] ?>">Edit</a>
                    <form method="post" onsubmit="return confirm('Delete this product?');" style="display:inline;"><?= csrfField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button type="submit" class="danger">Delete</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
<?php endif; ?>
