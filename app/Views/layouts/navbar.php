<nav class="navbar navbar-expand-lg navbar-dark bg-primary">

<div class="container">

<a class="navbar-brand fw-bold" href="<?= url('/') ?>">

KOBI

</a>

<button
class="navbar-toggler"
type="button"
data-bs-toggle="collapse"
data-bs-target="#navbar">

<span class="navbar-toggler-icon"></span>

</button>

<div
class="collapse navbar-collapse"
id="navbar">

<ul class="navbar-nav me-auto">

<li class="nav-item">

<a class="nav-link" href="<?= url('/') ?>">

Home

</a>

</li>

<li class="nav-item">

<a class="nav-link" href="<?= url('/diseases') ?>">

Diseases

</a>

</li>

<li class="nav-item">

<a class="nav-link disabled">

Body Parts

</a>

</li>

<li class="nav-item">

<a class="nav-link disabled">

Symptoms

</a>

</li>

<li class="nav-item">

<a class="nav-link disabled">

Search

</a>

</li>

</ul>

<span class="navbar-text">

v<?= e(config('version')) ?>

</span>

</div>

</div>

</nav>