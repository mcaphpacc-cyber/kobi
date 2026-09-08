<?php

$error =
    trim(
        (string) (
            $error
            ?? 'This Medical Summary share is no longer available.'
        )
    );

?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-12 col-md-7 col-lg-5">

            <div class="card shadow-sm">

                <div class="card-body p-4 text-center">

                    <div
                        class="medical-share-error-icon
                               mb-3"
                    >
                        <i class="bi bi-link-45deg"></i>
                    </div>


                    <div
                        class="small
                               text-uppercase
                               text-muted
                               fw-semibold
                               mb-2"
                    >
                        KOBI
                    </div>


                    <h1 class="h4 mb-3">
                        Medical Summary Unavailable
                    </h1>


                    <p class="text-muted mb-4">
                        <?= e($error) ?>
                    </p>


                    <p class="small text-muted mb-0">
                        The link may be invalid, expired,
                        or revoked by the health profile owner.
                    </p>

                </div>

            </div>

        </div>

    </div>

</div>


<style>
.medical-share-error-icon
{
    width: 3.5rem;
    height: 3.5rem;
    margin-left: auto;
    margin-right: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: var(--bs-secondary-bg);
    color: var(--bs-secondary-color);
    font-size: 1.75rem;
}
</style>