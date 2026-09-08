<?php

$profileId =
    (int) ($profile['id'] ?? 0);

$profileName =
    trim((string) ($profile['full_name'] ?? 'Health Profile'));

$timeline =
    is_array($timeline ?? null)
        ? $timeline
        : [];

$eventIcons = [
    'condition' => 'bi-heart-pulse',
    'medicine' => 'bi-capsule',
    'allergy' => 'bi-exclamation-triangle',
    'procedure' => 'bi-hospital'
];

$eventLabels = [
    'condition' => 'Condition',
    'medicine' => 'Medicine',
    'allergy' => 'Allergy',
    'procedure' => 'Procedure'
];
?>

<div class="container py-4">

    <!-- Page Header -->
    <div
        class="d-flex flex-column flex-md-row
               justify-content-between
               align-items-md-center
               gap-3 mb-4"
    >

        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a
                    href="<?= url(
                        '/account/health-records/' .
                        $profileId
                    ) ?>"
                    class="text-decoration-none"
                >
                    <i class="bi bi-arrow-left"></i>
                    Back to Health Record
                </a>
            </div>

            <h1 class="h3 mb-1">
                Medical Timeline
            </h1>

            <p class="text-muted mb-0">
                <?= e($profileName) ?>
            </p>
        </div>

    </div>


    <?php if (empty($timeline)): ?>

        <!-- Empty State -->
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">

                <div class="mb-3">
                    <i
                        class="bi bi-clock-history
                               display-4 text-muted"
                    ></i>
                </div>

                <h2 class="h5 mb-2">
                    No Medical Timeline Yet
                </h2>

                <p class="text-muted mb-4">
                    Medical events will appear here once
                    dated health records have been added.
                </p>

                <a
                    href="<?= url(
                        '/account/health-records/' .
                        $profileId
                    ) ?>"
                    class="btn btn-primary"
                >
                    Back to Health Record
                </a>

            </div>
        </div>

    <?php else: ?>

        <!-- Timeline -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">

                <div class="health-timeline">

                    <?php foreach ($timeline as $event): ?>

                        <?php
                        $eventType =
                            (string) (
                                $event['event_type'] ?? ''
                            );

                        $eventDate =
                            (string) (
                                $event['event_date'] ?? ''
                            );

                        $eventTitle =
                            trim(
                                (string) (
                                    $event['title'] ?? ''
                                )
                            );

                        $eventSubtitle =
                            trim(
                                (string) (
                                    $event['subtitle'] ?? ''
                                )
                            );

                        $sourceType =
                            (string) (
                                $event['source_type'] ?? ''
                            );

                        $sourceId =
                            (int) (
                                $event['source_id'] ?? 0
                            );

                        $metadata =
                            is_array(
                                $event['metadata'] ?? null
                            )
                                ? $event['metadata']
                                : [];

                        $icon =
                            $eventIcons[$eventType]
                            ?? 'bi-circle';

                        $eventLabel =
                            $eventLabels[$eventType]
                            ?? 'Medical Event';

                        $formattedDate =
                            $eventDate !== ''
                                ? date(
                                    'd M Y',
                                    strtotime($eventDate)
                                )
                                : '';

                        $sourceUrl = null;

                        if (
                            $sourceId > 0 &&
                            $profileId > 0
                        ) {
                            switch ($sourceType) {

                                case 'condition':
                                    $sourceUrl =
                                        url(
                                            '/account/health-records/' .
                                            $profileId .
                                            '/conditions/' .
                                            $sourceId .
                                            '/edit'
                                        );
                                    break;

                                case 'medicine':
                                    $sourceUrl =
                                        url(
                                            '/account/health-records/' .
                                            $profileId .
                                            '/medicines/' .
                                            $sourceId .
                                            '/edit'
                                        );
                                    break;

                                case 'allergy':
                                    $sourceUrl =
                                        url(
                                            '/account/health-records/' .
                                            $profileId .
                                            '/allergies/' .
                                            $sourceId .
                                            '/edit'
                                        );
                                    break;

                                case 'procedure':
                                    $sourceUrl =
                                        url(
                                            '/account/health-records/' .
                                            $profileId .
                                            '/procedures/' .
                                            $sourceId .
                                            '/edit'
                                        );
                                    break;
                            }
                        }
                        ?>

                        <div class="health-timeline-item">

                            <!-- Timeline Marker -->
                            <div
                                class="health-timeline-marker
                                       d-flex
                                       align-items-center
                                       justify-content-center"
                            >
                                <i
                                    class="bi <?= e($icon) ?>"
                                ></i>
                            </div>


                            <!-- Event -->
                            <div class="health-timeline-content">

                                <div
                                    class="d-flex
                                           flex-column
                                           flex-md-row
                                           justify-content-between
                                           gap-2 mb-1"
                                >

                                    <div>
                                        <span
                                            class="small
                                                   text-muted"
                                        >
                                            <?= e($eventLabel) ?>
                                        </span>

                                        <?php if ($eventSubtitle !== ''): ?>

                                            <span
                                                class="small
                                                       text-muted"
                                            >
                                                ·
                                                <?= e(
                                                    $eventSubtitle
                                                ) ?>
                                            </span>

                                        <?php endif; ?>
                                    </div>

                                    <?php if ($formattedDate !== ''): ?>

                                        <div
                                            class="small
                                                   fw-semibold
                                                   text-nowrap"
                                        >
                                            <?= e($formattedDate) ?>
                                        </div>

                                    <?php endif; ?>

                                </div>


                                <?php if ($eventTitle !== ''): ?>

                                    <h2 class="h6 mb-3">
                                        <?= e($eventTitle) ?>
                                    </h2>

                                <?php endif; ?>


                                <?php
                                $metadataItems = [];

                                if (
                                    $sourceType === 'condition'
                                ) {
                                    if (
                                        !empty(
                                            $metadata['status']
                                        )
                                    ) {
                                        $metadataItems[] = [
                                            'label' => 'Status',
                                            'value' =>
                                                $metadata['status']
                                        ];
                                    }

                                    if (
                                        !empty(
                                            $metadata[
                                                'doctor_hospital'
                                            ]
                                        )
                                    ) {
                                        $metadataItems[] = [
                                            'label' =>
                                                'Doctor / Hospital',
                                            'value' =>
                                                $metadata[
                                                    'doctor_hospital'
                                                ]
                                        ];
                                    }
                                }

                                elseif (
                                    $sourceType === 'medicine'
                                ) {
                                    if (
                                        !empty(
                                            $metadata[
                                                'generic_name'
                                            ]
                                        )
                                    ) {
                                        $metadataItems[] = [
                                            'label' =>
                                                'Salt / Generic',
                                            'value' =>
                                                $metadata[
                                                    'generic_name'
                                                ]
                                        ];
                                    }

                                    if (
                                        !empty(
                                            $metadata['dose']
                                        )
                                    ) {
                                        $metadataItems[] = [
                                            'label' => 'Dose',
                                            'value' =>
                                                $metadata['dose']
                                        ];
                                    }

                                    if (
                                        !empty(
                                            $metadata['frequency']
                                        )
                                    ) {
                                        $metadataItems[] = [
                                            'label' =>
                                                'Frequency',
                                            'value' =>
                                                $metadata[
                                                    'frequency'
                                                ]
                                        ];
                                    }

                                    if (
                                        !empty(
                                            $metadata['status']
                                        )
                                    ) {
                                        $metadataItems[] = [
                                            'label' => 'Status',
                                            'value' =>
                                                $metadata['status']
                                        ];
                                    }
                                }

                                elseif (
                                    $sourceType === 'allergy'
                                ) {
                                    if (
                                        !empty(
                                            $metadata['category']
                                        )
                                    ) {
                                        $metadataItems[] = [
                                            'label' =>
                                                'Category',
                                            'value' =>
                                                $metadata[
                                                    'category'
                                                ]
                                        ];
                                    }

                                    if (
                                        !empty(
                                            $metadata['reaction']
                                        )
                                    ) {
                                        $metadataItems[] = [
                                            'label' =>
                                                'Reaction',
                                            'value' =>
                                                $metadata[
                                                    'reaction'
                                                ]
                                        ];
                                    }

                                    if (
                                        !empty(
                                            $metadata['severity']
                                        )
                                    ) {
                                        $metadataItems[] = [
                                            'label' =>
                                                'Severity',
                                            'value' =>
                                                $metadata[
                                                    'severity'
                                                ]
                                        ];
                                    }
                                }

                                elseif (
                                    $sourceType === 'procedure'
                                ) {
                                    if (
                                        !empty(
                                            $metadata['hospital']
                                        )
                                    ) {
                                        $metadataItems[] = [
                                            'label' =>
                                                'Hospital',
                                            'value' =>
                                                $metadata[
                                                    'hospital'
                                                ]
                                        ];
                                    }

                                    if (
                                        !empty(
                                            $metadata['doctor']
                                        )
                                    ) {
                                        $metadataItems[] = [
                                            'label' =>
                                                'Doctor',
                                            'value' =>
                                                $metadata['doctor']
                                        ];
                                    }

                                    if (
                                        !empty(
                                            $metadata['reason']
                                        )
                                    ) {
                                        $metadataItems[] = [
                                            'label' =>
                                                'Reason',
                                            'value' =>
                                                $metadata['reason']
                                        ];
                                    }
                                }
                                ?>


                                <?php if (!empty($metadataItems)): ?>

                                    <div
                                        class="row
                                               row-cols-1
                                               row-cols-md-2
                                               g-2 mb-3"
                                    >

                                        <?php foreach (
                                            $metadataItems
                                            as $metadataItem
                                        ): ?>

                                            <div class="col">

                                                <div
                                                    class="small
                                                           text-muted"
                                                >
                                                    <?= e(
                                                        $metadataItem[
                                                            'label'
                                                        ]
                                                    ) ?>
                                                </div>

                                                <div
                                                    class="small"
                                                >
                                                    <?= e(
                                                        (string)
                                                        $metadataItem[
                                                            'value'
                                                        ]
                                                    ) ?>
                                                </div>

                                            </div>

                                        <?php endforeach; ?>

                                    </div>

                                <?php endif; ?>


                                <?php if ($sourceUrl !== null): ?>

                                    <a
                                        href="<?= e(
                                            $sourceUrl
                                        ) ?>"
                                        class="btn
                                               btn-sm
                                               btn-outline-secondary"
                                    >
                                        View Record
                                        <i
                                            class="bi
                                                   bi-arrow-right"
                                        ></i>
                                    </a>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>
        </div>

    <?php endif; ?>

</div>


<style>
.health-timeline
{
    position: relative;
    padding-left: 3rem;
}

.health-timeline::before
{
    content: "";
    position: absolute;
    top: 0.75rem;
    bottom: 0.75rem;
    left: 1rem;
    width: 2px;
    background: var(--bs-border-color);
}

.health-timeline-item
{
    position: relative;
    padding-bottom: 2rem;
}

.health-timeline-item:last-child
{
    padding-bottom: 0;
}

.health-timeline-marker
{
    position: absolute;
    left: -3rem;
    top: 0;
    width: 2rem;
    height: 2rem;
    border: 2px solid var(--bs-border-color);
    border-radius: 50%;
    background: var(--bs-body-bg);
    z-index: 1;
}

.health-timeline-content
{
    padding: 0.1rem 0 0;
}

@media (max-width: 575.98px)
{
    .health-timeline
    {
        padding-left: 2.5rem;
    }

    .health-timeline-marker
    {
        left: -2.5rem;
    }
}
</style>