<?php
require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/activities.php';
$student = require_student(true);
$pdo = database();
$id = (int) ($_GET['id'] ?? 0);
$error = '';
$activity = null;
if ($id) {
    $statement = $pdo->prepare(activity_query() . ' WHERE a.id = ? AND a.created_by = ?');
    $statement->execute([$id, $student['id']]);
    $activity = $statement->fetch();
    if (!$activity) {
        http_response_code(404);
        page_start('Activity not found');
        echo '<div class="empty"><h1>Activity not found.</h1><a href="/manage.php">Your activities</a></div>';
        page_end();
        exit();
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_token();
    $action = (string) ($_POST['action'] ?? 'save');
    try {
        $pdo->beginTransaction();
        if ($id) {
            $statement = $pdo->prepare(
                'SELECT * FROM activities WHERE id = ? AND created_by = ? FOR UPDATE'
            );
            $statement->execute([$id, $student['id']]);
            $locked = $statement->fetch();
            if (!$locked) {
                throw new DomainException('You can manage only your own activities.');
            }
        }
        if ($action === 'attendance' && $id) {
            if ($locked['cancelled'] || strtotime($locked['starts_at']) > time()) {
                throw new DomainException(
                    'Attendance can be recorded only after an active activity starts.'
                );
            }
            $pdo->prepare(
                'UPDATE registrations SET attended = ? WHERE id = ? AND activity_id = ?'
            )->execute([
                ($_POST['attended'] ?? '') === '1' ? 'true' : 'false',
                (int) ($_POST['registration_id'] ?? 0),
                $id
            ]);
            $message = 'Attendance updated.';
        } elseif ($action === 'cancel' && $id) {
            if (strtotime($locked['ends_at']) <= time()) {
                throw new DomainException('A completed activity cannot be cancelled.');
            }
            $pdo->prepare('UPDATE activities SET cancelled = TRUE WHERE id = ?')->execute([$id]);
            $message = 'Activity cancelled. Registered students will see the updated status.';
        } elseif ($action === 'save') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $venue = trim((string) ($_POST['venue'] ?? ''));
            $club_id = (int) ($_POST['club_id'] ?? 0);
            $capacity = filter_var($_POST['capacity'] ?? '', FILTER_VALIDATE_INT);
            $start = DateTime::createFromFormat(
                '!Y-m-d\TH:i',
                (string) ($_POST['starts_at'] ?? '')
            );
            $end = DateTime::createFromFormat('!Y-m-d\TH:i', (string) ($_POST['ends_at'] ?? ''));
            if (
                strlen($title) < 3 ||
                strlen($title) > 120 ||
                strlen($description) < 20 ||
                strlen($description) > 5000 ||
                strlen($venue) < 3 ||
                strlen($venue) > 150
            ) {
                throw new DomainException(
                    'Use a title of 3–120 characters, a description of 20–5,000 characters and a venue of 3–150 characters.'
                );
            }
            if (
                !$start ||
                !$end ||
                $start->format('Y-m-d\TH:i') !== ($_POST['starts_at'] ?? '') ||
                $end->format('Y-m-d\TH:i') !== ($_POST['ends_at'] ?? '') ||
                $end <= $start
            ) {
                throw new DomainException('Enter valid dates with the end after the start.');
            }
            if ($id && ($locked['cancelled'] || strtotime($locked['starts_at']) <= time())) {
                throw new DomainException(
                    'An activity can be edited only before it starts and while it is active.'
                );
            }
            if ($start->getTimestamp() <= time()) {
                throw new DomainException('Choose a future start date and time.');
            }
            if ($capacity === false || $capacity < 1 || $capacity > 10000) {
                throw new DomainException('Capacity must be a whole number between 1 and 10,000.');
            }
            $statement = $pdo->prepare('SELECT id FROM clubs WHERE id = ?');
            $statement->execute([$club_id]);
            if (!$statement->fetch()) {
                throw new DomainException('Choose a valid club.');
            }
            $parameters = [
                $club_id,
                $title,
                $description,
                $venue,
                $start->format('c'),
                $end->format('c'),
                $capacity
            ];
            if ($id) {
                $statement = $pdo->prepare(
                    'SELECT COUNT(*) FROM registrations WHERE activity_id = ?'
                );
                $statement->execute([$id]);
                if ($capacity < (int) $statement->fetchColumn()) {
                    throw new DomainException(
                        'Capacity cannot be lower than the number of confirmed registrations.'
                    );
                }
                $parameters[] = $id;
                $pdo->prepare(
                    'UPDATE activities SET club_id = ?, title = ?, description = ?, venue = ?, starts_at = ?, ends_at = ?, capacity = ? WHERE id = ?'
                )->execute($parameters);
                $message = 'Activity updated.';
            } else {
                $parameters[] = $student['id'];
                $statement = $pdo->prepare(
                    'INSERT INTO activities (club_id, title, description, venue, starts_at, ends_at, capacity, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING id'
                );
                $statement->execute($parameters);
                $id = (int) $statement->fetchColumn();
                $message = 'Your activity is published. Students can now register.';
            }
        } else {
            throw new DomainException('Choose a valid action.');
        }
        $pdo->commit();
        flash($message);
        redirect('/manage.php?id=' . $id);
    } catch (DomainException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}
$clubs = $pdo->query('SELECT id, name FROM clubs ORDER BY name')->fetchAll();
$statement = $pdo->prepare(activity_query() . ' WHERE a.created_by = ? ORDER BY a.starts_at DESC');
$statement->execute([$student['id']]);
$owned = $statement->fetchAll();
$defaults = [
    'title' => '',
    'description' => '',
    'venue' => '',
    'club_id' => '',
    'starts_at' => '',
    'ends_at' => '',
    'capacity' => 40
];
$values = $activity ?: $defaults;
if ($activity) {
    $values['starts_at'] = date('Y-m-d\TH:i', strtotime($activity['starts_at']));
    $values['ends_at'] = date('Y-m-d\TH:i', strtotime($activity['ends_at']));
}
if ($error && ($_POST['action'] ?? '') === 'save') {
    foreach ($defaults as $key => $value) {
        $values[$key] = (string) ($_POST[$key] ?? $value);
    }
}
page_start('Organizer workspace');
?>
<div class="page_intro">
    <p class="eyebrow">MAKE SOMETHING HAPPEN</p>
    <h1>Organizer workspace.</h1>
    <p>Publish an activity, keep an eye on registrations and record participation.</p>
</div>
<?php if ($error): ?>
    <div
        class="error"
        role="alert"
    ><?= escape($error) ?></div>
<?php endif; ?>
<div class="organizer_main">
    <div class="paper">
        <h2><?= $id ? 'Activity details' : 'Publish an activity' ?></h2>
        <p class="muted">All dates and times use IST. Students can view updates on their
            registration page.</p>
        <?php if (!$activity || (!$activity['cancelled'] && strtotime($activity['starts_at']) > time())): ?>
            <form
                method="post"
                class="validated"
            >
                <?= token() ?>
                <input
                    type="hidden"
                    name="action"
                    value="save"
                >
                <div class="field">
                    <label for="title">Activity title</label>
                    <input
                        id="title"
                        name="title"
                        minlength="3"
                        maxlength="120"
                        required
                        value="<?= escape($values['title']) ?>"
                    >
                </div>
                <div class="field">
                    <label for="club_id">Club</label>
                    <select
                        id="club_id"
                        name="club_id"
                        required
                    >
                        <option value="">Choose a club</option>
                        <?php foreach ($clubs as $club): ?>
                            <option
                                value="<?= (int) $club['id'] ?>"
                                <?= (int) $values['club_id'] === (int) $club['id'] ? 'selected' : '' ?>
                            ><?= escape($club['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="description">Description</label>
                    <textarea
                        id="description"
                        name="description"
                        minlength="20"
                        maxlength="5000"
                        required
                    ><?= escape($values['description']) ?></textarea>
                </div>
                <div class="field">
                    <label for="venue">Venue</label>
                    <input
                        id="venue"
                        name="venue"
                        minlength="3"
                        maxlength="150"
                        required
                        value="<?= escape($values['venue']) ?>"
                    >
                </div>
                <div class="field">
                    <label for="starts_at">Starts at (IST)</label>
                    <input
                        id="starts_at"
                        name="starts_at"
                        type="datetime-local"
                        required
                        value="<?= escape($values['starts_at']) ?>"
                    >
                </div>
                <div class="field">
                    <label for="ends_at">Ends at (IST)</label>
                    <input
                        id="ends_at"
                        name="ends_at"
                        type="datetime-local"
                        required
                        value="<?= escape($values['ends_at']) ?>"
                    >
                </div>
                <div class="field">
                    <label for="capacity">Student capacity</label>
                    <input
                        id="capacity"
                        name="capacity"
                        type="number"
                        min="1"
                        max="10000"
                        required
                        value="<?= escape($values['capacity']) ?>"
                    >
                </div>
                <button
                    class="primary"
                    type="submit"
                ><?= $id ? 'Save activity' : 'Publish activity ↗' ?></button>
            </form>
        <?php else: ?>
            <p class="notice">This activity is <?= escape(strtolower(activity_state($activity))) ?>. Its details are preserved.</p>
        <?php endif; ?>
        <?php if ($activity): ?>
            <p>
                <a href="/activity.php?id=<?= $id ?>">View student activity page ↗</a>
            </p>
            <?php if (!$activity['cancelled'] && strtotime($activity['ends_at']) > time()): ?>
                <form method="post">
                    <?= token() ?>
                    <input
                        type="hidden"
                        name="action"
                        value="cancel"
                    >
                    <button
                        class="text_button"
                        type="submit"
                    >Cancel this activity</button>
                    <p class="muted">This closes registration and marks the activity as cancelled for all
                        registered students.</p>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php if ($activity): ?>
        <?php
        $statement = $pdo->prepare(
            'SELECT r.*, s.name, s.admission, s.email FROM registrations r JOIN students s ON s.id = r.student_id WHERE r.activity_id = ? ORDER BY s.name'
        );
        $statement->execute([$id]);
        $participants = $statement->fetchAll();
        ?>
        <div class="paper">
            <h2>Participants · <?= count($participants) ?> / <?= (int) $activity['capacity'] ?></h2>
            <p class="muted">Attendance can be recorded after the start time.</p>
            <?php if (!$participants): ?>
                <p>No registrations yet.</p>
            <?php endif; ?>
            <div class="table_scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Admission</th>
                            <th>Attendance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participants as $participant): ?>
                            <tr>
                                <td><?= escape($participant['name']) ?>
                                    <br>
                                    <span class="muted"><?= escape($participant['email']) ?></span>
                                </td>
                                <td><?= escape($participant['admission']) ?></td>
                                <td>
                                    <?php if (!$activity['cancelled'] && strtotime($activity['starts_at']) <= time()): ?>
                                        <form method="post">
                                            <?= token() ?>
                                            <input
                                                type="hidden"
                                                name="action"
                                                value="attendance"
                                            >
                                            <input
                                                type="hidden"
                                                name="registration_id"
                                                value="<?= (int) $participant['id'] ?>"
                                            >
                                            <input
                                                type="hidden"
                                                name="attended"
                                                value="<?= $participant['attended'] ? '0' : '1' ?>"
                                            >
                                            <button
                                                class="button secondary small"
                                                type="submit"
                                            ><?= $participant['attended'] ? 'Attended · undo' : 'Mark attended' ?></button>
                                        </form>
                                    <?php else: ?>
                                        <?= $participant['attended'] ? 'Attended' : 'Not recorded' ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
<div class="organizer_side paper">
    <h2>Your activities</h2>
    <p>
        <a href="/manage.php">+ Publish a new activity</a>
    </p>
    <?php foreach ($owned as $item): ?>
        <div class="owned_activity">
            <a href="/manage.php?id=<?= (int) $item['id'] ?>"><?= escape($item['title']) ?></a>
            <p class="muted"><?= escape(date('d M Y', strtotime($item['starts_at']))) ?> · <?= (int) $item['registered'] ?> registered · <?= escape(activity_state($item)) ?></p>
        </div>
    <?php endforeach; ?>
    <?php if (!$owned): ?>
        <p class="muted">Your published activities will appear here.</p>
    <?php endif; ?>
</div>
<?php page_end(); ?>
