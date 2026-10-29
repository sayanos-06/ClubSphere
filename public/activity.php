<?php
require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/activities.php';
$id = (int) ($_GET['id'] ?? 0);
$statement = database()->prepare(activity_query() . ' WHERE a.id = ?');
$statement->execute([$id]);
$activity = $statement->fetch();
if (!$activity) {
    http_response_code(404);
    page_start('Activity not found');
    echo '<div class="empty"><h1>This activity is not here.</h1><p>It may have moved, or the link may be incorrect.</p><a class="button" href="/index.php">Explore activities</a></div>';
    page_end();
    exit;
}
$student = current_student();
$registration = false;
if ($student) {
    $statement = database()->prepare('SELECT * FROM registrations WHERE activity_id = ? AND student_id = ?');
    $statement->execute([$id, $student['id']]);
    $registration = $statement->fetch();
}
$state = activity_state($activity);
page_start($activity['title'], 'activities');
?>
<p class="breadcrumb"><a href="/index.php#activities">← All activities</a> / <?= escape($activity['club']) ?></p>
<div class="activity_detail"><p class="eyebrow"><?= escape($activity['category']) ?> · <?= escape($activity['club']) ?></p><h1><?= escape($activity['title']) ?></h1><div class="description"><?= nl2br(escape($activity['description'])) ?></div><div class="paper"><h2>Good to know</h2><p>All activity times are in Indian Standard Time. Please arrive a few minutes early and check the venue before you leave.</p><p>You can cancel your place before the activity begins. Attendance is recorded by the organizer.</p></div></div>
<div class="booking paper"><p class="eyebrow">YOUR NEXT CAMPUS PLAN</p><h2><?= escape(date('d M Y', strtotime($activity['starts_at']))) ?></h2><p><?= escape(date('l, g:i A', strtotime($activity['starts_at']))) ?> – <?= escape(date('d M, g:i A', strtotime($activity['ends_at']))) ?> IST</p><hr><p><strong>Venue</strong><br><?= escape($activity['venue']) ?></p><p><strong>Capacity</strong><br><?= (int) $activity['capacity'] ?> students</p><p class="seat_count" data-count="<?= $id ?>"><?= max(0, $activity['capacity'] - $activity['registered']) ?> places left</p><p class="status" data-state="<?= $id ?>"><?= escape($state) ?></p>
<p id="registration_message" aria-live="polite" class="muted"><?= $registration ? 'You have a confirmed place.' : 'Find something you like? Make it a plan.' ?></p>
<?php if (!$student): ?><a class="button wide" href="/login.php">Sign in to register →</a><?php elseif (in_array($state, ['Open', 'Full'], true)): ?><form method="post" action="/register.php" id="registration_form"><?= token() ?><input type="hidden" name="activity_id" value="<?= $id ?>"><input type="hidden" name="action" id="registration_action" value="<?= $registration ? 'cancel' : 'register' ?>"><button class="primary wide" id="registration_button" type="submit" <?= !$registration && $state === 'Full' ? 'disabled' : '' ?>><?= $registration ? 'Cancel my registration' : ($state === 'Full' ? 'Activity full' : 'Reserve my place →') ?></button></form><?php else: ?><p class="notice">Registration is closed.</p><?php endif; ?>
<?php if ($registration): ?><a class="detail_link" href="/registrations.php">View my registrations ↗</a><?php endif; ?>
<?php if ($student && $student['role'] === 'organizer' && (int) $activity['created_by'] === (int) $student['id']): ?><a class="detail_link" href="/manage.php?id=<?= $id ?>">Manage this activity ↗</a><?php endif; ?>
<p class="muted">No fee is collected through this portal.</p></div>
<?php page_end(); ?>
