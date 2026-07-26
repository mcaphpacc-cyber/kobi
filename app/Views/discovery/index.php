<?php

/**
 * KOBI Knowledge Discovery Dashboard
 *
 * @var array $dashboard
 */

$pageTitle = 'Knowledge Discovery';

?>

<div class="container py-4">

    <!-- Hero -->

    <div class="card border-0 shadow-sm discovery-hero mb-4">

        <div class="card-body text-center py-5">

            <h1 class="display-6 fw-bold mb-3">
                Knowledge Discovery
            </h1>

            <p class="lead text-muted mb-4">
                Explore diseases, symptoms, body systems and trusted medical knowledge.
            </p>

            <div class="row justify-content-center">

                <div class="col-lg-6">

                    <form
                        action="<?= url('/diseases') ?>"
                        method="get">

                        <div class="input-group input-group-lg">

                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Search diseases...">

                            <button
                                class="btn btn-primary">

                                Search

                            </button>

                        </div>

                    </form>

                </div>

            </div>

            <div class="row mt-5">

                <div class="col-md-3 col-6 mb-3">

                    <div class="small text-success fw-semibold">

                        ✓ Explore

                    </div>

                </div>

                <div class="col-md-3 col-6 mb-3">

                    <div class="small text-success fw-semibold">

                        ✓ Learn

                    </div>

                </div>

                <div class="col-md-3 col-6 mb-3">

                    <div class="small text-success fw-semibold">

                        ✓ Compare

                    </div>

                </div>

                <div class="col-md-3 col-6 mb-3">

                    <div class="small text-success fw-semibold">

                        ✓ Discover

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- Statistics -->

    <div class="row g-3 mb-4">

        <div class="col-lg-3 col-md-6">

            <div class="card discovery-stat shadow-sm h-100">

                <div class="card-body text-center">

                    <h2 class="display-6 fw-bold">

                        <?= e($dashboard['statistics']['diseases']) ?>

                    </h2>

                    <div class="text-muted">

                        Diseases

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="card discovery-stat shadow-sm h-100">

                <div class="card-body text-center">

                    <h2 class="display-6 fw-bold">

                        <?= e($dashboard['statistics']['symptoms']) ?>

                    </h2>

                    <div class="text-muted">

                        Symptoms

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="card discovery-stat shadow-sm h-100">

                <div class="card-body text-center">

                    <h2 class="display-6 fw-bold">

                        <?= e($dashboard['statistics']['bodyParts']) ?>

                    </h2>

                    <div class="text-muted">

                        Body Systems

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="card discovery-stat shadow-sm h-100">

                <div class="card-body text-center">

                    <h2 class="display-6 fw-bold">

                        ✓

                    </h2>

                    <div class="text-muted">

                        Compare Ready

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- Dashboard Widgets -->

    <!-- Dashboard Widgets -->

    <!-- Dashboard Widgets -->

    <div class="row g-4">

        <?php foreach ($dashboard['widgets'] as $widget): ?>

            <div class="col-lg-6">

                <?php \App\Core\View::component(
                    'components/discovery/widget',
                    [
                        'widget' => $widget
                    ]
                ); ?>

            </div>

        <?php endforeach; ?>

    </div>


    <!-- Quick Actions -->

    <div class="card shadow-sm mt-4">

        <div class="card-header fw-semibold">

            Quick Actions

        </div>

        <div class="card-body">

            <div class="d-grid gap-3 d-md-flex">

                <a
                    href="<?= url('/diseases') ?>"
                    class="btn btn-outline-primary">

                    Disease Catalog

                </a>

                <a
                    href="<?= url('/symptom-checker') ?>"
                    class="btn btn-outline-success">

                    Symptom Checker

                </a>

                <a
                    href="<?= url('/compare') ?>"
                    class="btn btn-outline-warning">

                    Compare Diseases

                </a>

                <a
                    href="javascript:void(0)"
                    class="btn btn-outline-secondary">

                    Browse Body Systems

                </a>

            </div>

        </div>

    </div>

</div>
