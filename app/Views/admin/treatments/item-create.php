<div class="container py-4">
    <h1 class="h3">Add treatment item</h1>
    <p>Protocol #<?= (int)$protocol['id'] ?> — <?= e($identity['disease_en']??'Disease') ?> — <?= e($identity['system_name']??'Treatment system') ?></p>
    <p>This item starts excluded and unassessed. Enter a type and a title in English or Hindi. Adding it creates a new protocol revision and clears any existing approval.</p>
    <form method="post" action="<?= e(url('admin/treatments/'.$protocol['id'].'/items/create')) ?>" class="card card-body">
        <?= csrfField() ?>
        <input type="hidden" name="expected_revision" value="<?= (int)$protocol['content_revision'] ?>">
        <input type="hidden" name="expected_state" value="<?= e($protocol['review_state']) ?>">
        <div class="row"><?php $fieldNames=['item_type','title_en','title_hi','instructions_en','instructions_hi','sort_order']; $fieldPrefix='item'; $fieldValues=['sort_order'=>0]; require __DIR__.'/_fields.php'; ?></div>
        <div><button class="btn btn-primary">Add item to new draft revision</button> <a class="btn btn-outline-secondary" href="<?= e(url('admin/treatments/'.$protocol['id'].'/edit')) ?>">Cancel</a></div>
    </form>
</div>
