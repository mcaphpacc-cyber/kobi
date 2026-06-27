<h1><?= e($title) ?></h1>

<p class="mb-4">

Total Diseases

<span class="badge bg-primary">

<?= count($diseases) ?>

</span>

</p>

<table class="table table-striped table-hover">

<thead class="table-dark">

<tr>

<th>ID</th>

<th>Name</th>

<th>Slug</th>

<th>Gender</th>

</tr>

</thead>

<tbody>

<?php foreach ($diseases as $disease): ?>

<tr>

<td><?= e((string)$disease['id']) ?></td>

<td><?= e($disease['name']) ?></td>

<td><?= e($disease['slug']) ?></td>

<td><?= e($disease['gender']) ?></td>

</tr>

<?php endforeach; ?>

</tbody>

</table>