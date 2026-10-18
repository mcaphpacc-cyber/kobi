<h2 class="h4">Internal governance history</h2>
<div class="table-responsive"><table class="table table-striped"><caption>Append-only events; timestamps are UTC</caption><thead><tr><th>Event</th><th>Revision / languages</th><th>Actor ID</th><th>Timestamp / due date</th><th>Rationale</th><th>Independence</th></tr></thead><tbody>
<?php foreach ($history as $event): ?><tr><td>#<?= (int)$event['id'] ?> <?= e($event['event_type']) ?></td><td><?= (int)$event['content_revision'] ?> / <?= e($event['locales']) ?></td><td><?= e((string)($event['actor_id']??'Legacy unknown')) ?></td><td><?= e($event['occurred_at']) ?><br><?= e($event['review_due_at']??'—') ?></td><td class="text-break"><?= nl2br(e($event['notes']??'')) ?></td><td><?= (int)$event['independence_confirmed']===1 ? 'Confirmed' : 'Not recorded' ?></td></tr><?php endforeach; ?>
<?php if (!$history): ?><tr><td colspan="6">No governance events recorded.</td></tr><?php endif; ?>
</tbody></table></div>
<?php if ($moreHistory && $history): ?><a class="btn btn-outline-secondary" href="<?= e(url($historyBase).'?before_event='.(int)$history[array_key_last($history)]['id']) ?>">Older events</a><?php endif; ?>
