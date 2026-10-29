<?php
require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/activities.php';
$student = require_student();
$statement = database()->prepare(
    'SELECT a.*, c.name AS club, c.category, c.initials, r.attended, r.id AS registration_id, r.registered_at FROM registrations r JOIN activities a ON a.id = r.activity_id JOIN clubs c ON c.id = a.club_id WHERE r.student_id = ? ORDER BY a.starts_at DESC'
);
$statement->execute([$student['id']]);
$registrations = $statement->fetchAll();
$upcoming = 0;
$attended = 0;
foreach ($registrations as $registration) {
    if (!$registration['cancelled'] && strtotime($registration['ends_at']) > time()) {
        $upcoming++;
    }
    if ($registration['attended'] && !$registration['cancelled']) {
        $attended++;
    }
}
page_start('My registrations', 'registrations');
?>
<div class="page_intro">
    <p class="eyebrow">YOUR CAMPUS CALENDAR</p>
    <h1>You’re part of it,
        <br><?= escape(explode(' ', $student['name'])[0]) ?>.
    </h1>
    <p>Your plans, your places, and the things you showed up for.</p>
</div>
<div class="campus_strip">
    <div>
        <strong><?= $upcoming ?></strong> upcoming plans
    </div>
    <div>
        <strong><?= $attended ?></strong> activities attended
    </div>
    <div>
        <strong><?= count($registrations) ?></strong> total registrations
    </div>
</div>
<div class="section_heading">
    <h2>Your registrations.</h2>
    <p class="muted">All dates and times are in IST. Attendance is updated by activity organizers.
    </p>
</div>
<?php if (!$registrations): ?>
    <div class="empty paper">
        <h2>Your next story starts with a plan.</h2>
        <p>You haven’t registered for an activity yet. Find something that interests you.</p>
        <a
            class="button"
            href="/index.php#activities"
        >Explore activities ↗</a>
    </div>
<?php endif; ?>
<?php foreach ($registrations as $registration): ?>
    <div class="registration_row paper">
        <div class="date_tile">
            <strong><?= escape(date('d', strtotime($registration['starts_at']))) ?></strong><?= escape(strtoupper(date('M', strtotime($registration['starts_at'])))) ?>
        </div>
        <div class="registration_info">
            <p class="card_club"><?= escape($registration['club']) ?></p>
            <h3>
                <a href="/activity.php?id=<?= (int) $registration['id'] ?>"><?= escape($registration['title']) ?></a>
            </h3>
            <p class="muted"><?= escape(date('D, d M Y · g:i A', strtotime($registration['starts_at']))) ?> · <?= escape($registration['venue']) ?></p>
            <span class="badge"><?= $registration['cancelled'] ? 'Activity cancelled' : ($registration['attended'] ? 'Attended' : (strtotime($registration['ends_at']) <= time() ? 'Completed · attendance not recorded' : 'Registered')) ?></span>
            <span class="muted"> Reference CS-<?= (int) $registration['registration_id'] ?></span>
        </div>
        <div class="registration_actions">
            <a href="/activity.php?id=<?= (int) $registration['id'] ?>">View details ↗</a>
            <?php if (!$registration['cancelled'] && strtotime($registration['starts_at']) > time()): ?>
                <form
                    method="post"
                    action="/register.php"
                >
                    <?= token() ?>
                    <input
                        type="hidden"
                        name="activity_id"
                        value="<?= (int) $registration['id'] ?>"
                    >
                    <input
                        type="hidden"
                        name="action"
                        value="cancel"
                    >
                    <button
                        class="text_button"
                        type="submit"
                    >Cancel registration</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
<?php page_end(); ?>
