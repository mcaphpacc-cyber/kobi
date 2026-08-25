<?php

$treatmentSystems = $treatmentSystems ?? [];
$preferences = $preferences ?? [];

/*
 * Build the user's preferred order.
 */
$preferredOrder = [];

foreach ($preferences as $preference)
{
    $preferredOrder[] =
        (int) $preference['treatment_system_id'];
}

/*
 * Map treatment systems by ID.
 */
$systemsById = [];

foreach ($treatmentSystems as $system)
{
    $systemsById[(int) $system['id']] = $system;
}

/*
 * Build display order:
 *
 * 1. User's saved preference.
 * 2. Any active systems not present in that preference,
 *    using KOBI's default order.
 */
$orderedSystems = [];

foreach ($preferredOrder as $systemId)
{
    if (isset($systemsById[$systemId]))
    {
        $orderedSystems[] =
            $systemsById[$systemId];

        unset($systemsById[$systemId]);
    }
}

foreach ($systemsById as $system)
{
    $orderedSystems[] = $system;
}

?>

<div class="container py-5">

    <div class="mb-4">

        <h1 class="mb-2">
            Treatment Preferences
        </h1>

        <p class="text-muted mb-0">
            Arrange treatment approaches in the order
            you prefer to see them on KOBI disease pages.
        </p>

    </div>

    <div class="card shadow-sm border-0">

        <div class="card-body">

            <div class="mb-4">

                <h2 class="h5 mb-2">
                    Treatment Display Order
                </h2>

                <p class="text-muted small mb-0">
                    Drag and drop the treatment approaches
                    to change their order.
                </p>

            </div>

            <form
                method="POST"
                action="<?= e(
                    url('/account/treatment-preferences')
                ); ?>"
                id="treatmentPreferenceForm"
            >

                <?= csrfField(); ?>

                <div
                    id="treatmentPreferenceList"
                    class="list-group mb-4"
                >

                    <?php foreach (
                        $orderedSystems
                        as $system
                    ) : ?>

                        <div
                            class="list-group-item
                                   d-flex
                                   align-items-center
                                   gap-3"
                            draggable="true"
                            data-treatment-system-id="<?= (int)
                                $system['id']; ?>"
                        >

                            <span
                                class="text-muted"
                                style="cursor: grab;"
                                aria-hidden="true"
                            >
                                <i class="bi bi-grip-vertical fs-5"></i>
                            </span>

                            <span class="flex-grow-1">

                                <?= e(
                                    $system['name_en']
                                ); ?>

                            </span>

                            <span
                                class="badge bg-light text-dark
                                       treatment-position"
                            >
                                1
                            </span>

                            <input
                                type="hidden"
                                name="treatment_system_ids[]"
                                value="<?= (int)
                                    $system['id']; ?>"
                            >

                        </div>

                    <?php endforeach; ?>

                </div>

                <div
                    class="d-flex
                           justify-content-between
                           align-items-center
                           flex-wrap
                           gap-2"
                >

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-check2 me-1"></i>
                        Save Preferences
                    </button>

                    <button
                        type="submit"
                        formaction="<?= e(
                            url(
                                '/account/treatment-preferences/reset'
                            )
                        ); ?>"
                        class="btn btn-outline-secondary"
                    >
                        <i class="bi bi-arrow-counterclockwise me-1"></i>
                        Reset to Default
                    </button>

                </div>

            </form>

        </div>

    </div>

    <div class="mt-4">

        <a
            href="<?= e(url('/account')); ?>"
            class="btn btn-outline-secondary btn-sm"
        >
            <i class="bi bi-arrow-left me-2"></i>
            Back to Account
        </a>

    </div>

</div>

<script>
document.addEventListener(
    'DOMContentLoaded',
    function ()
    {
        const list =
            document.getElementById(
                'treatmentPreferenceList'
            );

        if (!list)
        {
            return;
        }

        let draggedItem = null;

        const updatePositions = function ()
        {
            const items =
                list.querySelectorAll(
                    '[data-treatment-system-id]'
                );

            items.forEach(
                function (item, index)
                {
                    const position =
                        item.querySelector(
                            '.treatment-position'
                        );

                    if (position)
                    {
                        position.textContent =
                            index + 1;
                    }
                }
            );
        };

        list.addEventListener(
            'dragstart',
            function (event)
            {
                const item =
                    event.target.closest(
                        '[data-treatment-system-id]'
                    );

                if (!item)
                {
                    return;
                }

                draggedItem = item;

                item.classList.add(
                    'opacity-50'
                );

                event.dataTransfer.effectAllowed =
                    'move';
            }
        );

        list.addEventListener(
            'dragend',
            function ()
            {
                if (draggedItem)
                {
                    draggedItem.classList.remove(
                        'opacity-50'
                    );
                }

                draggedItem = null;

                updatePositions();
            }
        );

        list.addEventListener(
            'dragover',
            function (event)
            {
                event.preventDefault();

                if (!draggedItem)
                {
                    return;
                }

                const target =
                    event.target.closest(
                        '[data-treatment-system-id]'
                    );

                if (
                    !target ||
                    target === draggedItem
                )
                {
                    return;
                }

                const rect =
                    target.getBoundingClientRect();

                const before =
                    event.clientY <
                    rect.top +
                    rect.height / 2;

                if (before)
                {
                    list.insertBefore(
                        draggedItem,
                        target
                    );
                }
                else
                {
                    list.insertBefore(
                        draggedItem,
                        target.nextSibling
                    );
                }
            }
        );

        updatePositions();
    }
);
</script>