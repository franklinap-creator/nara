<?php if (!isset($item)) return; ?>
<article class="product-card"><a href="index.php?page=product&id=<?= $item['id'] ?>">
        <div class="product-image"><img src="<?= htmlspecialchars($item['image']) ?>"
                alt="<?= htmlspecialchars($item['name']) ?>"><?php if ($item['badge']): ?><span
                class="badge"><?= htmlspecialchars($item['badge']) ?></span><?php endif; ?><form method="post"
                class="quick-add"><?= csrfField() ?><input type="hidden" name="action" value="add"><input type="hidden" name="product_id"
                    value="<?= $item['id'] ?>"><button
                    aria-label="Add <?= htmlspecialchars($item['name']) ?> to bag">+</button></form>
        </div>
    </a>
    <div class="product-info">
        <div>
            <h3><?= htmlspecialchars($item['name']) ?></h3>
            <p><?= htmlspecialchars($item['category']) ?></p>
        </div><strong><?= money((int)$item['price']) ?></strong>
    </div>
</article>
