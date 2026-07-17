<?php

if (!function_exists('renderSectionHeader')) {

    function renderSectionHeader(
        string $icon,
        string $title,
        ?string $subtitle = null
    ): void {

        ?>

        <div class="kobi-section-header">

            <div class="d-flex align-items-center">

                <i class="bi <?= e($icon); ?> kobi-section-icon"></i>

                <div>

                    <h2 class="kobi-section-title">

                        <?= e($title); ?>

                    </h2>

                    <?php if ($subtitle) : ?>

                        <div class="kobi-section-subtitle">

                            <?= e($subtitle); ?>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

        <?php

    }

}