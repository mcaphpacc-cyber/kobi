<?php

/**
 * KOBI Discovery Widget
 *
 * @var array $widget
 */

$type = $widget['type'] ?? 'text';

?>

<div class="card discovery-widget shadow-sm h-100">

    <div class="card-header fw-semibold">

        <?php if (!empty($widget['icon'])): ?>

            <i class="<?= e($widget['icon']) ?> me-2"></i>

        <?php endif; ?>

        <?= e($widget['title']) ?>

    </div>

    <div class="card-body">

    <?php if ($type === 'links'): ?>

        <ul class="list-group list-group-flush">

            <?php foreach (($widget['items'] ?? []) as $item): ?>

                <li class="list-group-item d-flex justify-content-between align-items-center">

                    <a href="<?= e($item['url']) ?>" class="text-decoration-none">
                        <?= e($item['title']) ?>
                    </a>

                    <?php if (!empty($item['badge'])): ?>
                        <span class="badge bg-primary rounded-pill">
                            <?= e($item['badge']) ?>
                        </span>
                    <?php endif; ?>

                </li>

            <?php endforeach; ?>

        </ul>

    <?php elseif ($type === 'list'): ?>

        <ul class="list-group list-group-flush">

            <?php foreach (($widget['items'] ?? []) as $item): ?>

                <li class="list-group-item">
                    <?= e($item) ?>
                </li>

            <?php endforeach; ?>

        </ul>

    <?php elseif ($type === 'text'): ?>

        <div class="text-muted">
            <?= e($widget['text'] ?? '') ?>
        </div>

    <?php else: ?>

        <div class="text-muted">
            No data available.
        </div>

    <?php endif; ?>

</div>

    <?php if (!empty($widget['footer'])): ?>

        <div class="card-footer bg-white">

            <a
                href="<?= e($widget['footer']) ?>"
                class="text-decoration-none">

                View All →

            </a>

        </div>

    <?php endif; ?>

</div>