<?php

function activity_query()
{
    return "SELECT a.*, c.name AS club, c.category, c.initials,
        (SELECT COUNT(*) FROM registrations r WHERE r.activity_id = a.id) AS registered
        FROM activities a JOIN clubs c ON c.id = a.club_id";
}

function activity_state($activity)
{
    if ($activity['cancelled']) {
        return 'Cancelled';
    }
    if (strtotime($activity['ends_at']) <= time()) {
        return 'Completed';
    }
    if (strtotime($activity['starts_at']) <= time()) {
        return 'In progress';
    }
    return (int) $activity['registered'] >= (int) $activity['capacity'] ? 'Full' : 'Open';
}

function change_registration($student_id, $activity_id, $action)
{
    $pdo = database();
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare('SELECT * FROM activities WHERE id = ? FOR UPDATE');
        $statement->execute([$activity_id]);
        $activity = $statement->fetch();
        if (!$activity) {
            throw new DomainException('This activity could not be found.');
        }
        if ($activity['cancelled'] || strtotime($activity['starts_at']) <= time()) {
            throw new DomainException('Registration changes close when an activity starts or is cancelled.');
        }
        if ($action === 'cancel') {
            $statement = $pdo->prepare('DELETE FROM registrations WHERE student_id = ? AND activity_id = ?');
            $statement->execute([$student_id, $activity_id]);
            if (!$statement->rowCount()) {
                throw new DomainException('You are not registered for this activity.');
            }
            $message = 'Registration cancelled. Your place is now available to another student.';
        } elseif ($action === 'register') {
            $statement = $pdo->prepare('SELECT id FROM registrations WHERE student_id = ? AND activity_id = ?');
            $statement->execute([$student_id, $activity_id]);
            if ($statement->fetch()) {
                throw new DomainException('You already have a place in this activity.');
            }
            $statement = $pdo->prepare('SELECT COUNT(*) FROM registrations WHERE activity_id = ?');
            $statement->execute([$activity_id]);
            if ((int) $statement->fetchColumn() >= (int) $activity['capacity']) {
                throw new DomainException('This activity is full. Check back if a place becomes available.');
            }
            $pdo->prepare('INSERT INTO registrations (student_id, activity_id) VALUES (?, ?)')->execute([$student_id, $activity_id]);
            $message = 'You are registered. See you there!';
        } else {
            throw new DomainException('Choose a valid registration action.');
        }
        $pdo->commit();
        return $message;
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }
}

function activity_card($activity)
{
    $state = activity_state($activity);
    ?>
    <div class="activity_card">
        <div class="card_art art_<?= escape(strtolower($activity['category'])) ?>"><span class="art_label"><?= escape(strtoupper($activity['category'])) ?></span><span class="art_initials" aria-hidden="true"><?= escape($activity['initials']) ?></span><span class="art_corner" aria-hidden="true">↗</span></div>
        <div class="card_body"><p class="card_club"><?= escape($activity['club']) ?></p><h3><a href="/activity.php?id=<?= (int) $activity['id'] ?>"><?= escape($activity['title']) ?></a></h3><p class="card_description"><?= escape(mb_strimwidth($activity['description'], 0, 125, '…')) ?></p>
        <p class="card_meta"><span aria-hidden="true">◷</span> <?= escape(date('D, d M', strtotime($activity['starts_at']))) ?> <span class="meta_separator">·</span> <?= escape(date('g:i A', strtotime($activity['starts_at']))) ?></p>
        <p class="card_meta"><span aria-hidden="true">⌖</span> <?= escape($activity['venue']) ?></p>
        <div class="card_bottom"><span class="seat_count" data-count="<?= (int) $activity['id'] ?>"><?= max(0, (int) $activity['capacity'] - (int) $activity['registered']) ?> places left</span><a href="/activity.php?id=<?= (int) $activity['id'] ?>">View activity ↗</a><p class="status" data-state="<?= (int) $activity['id'] ?>"><?= escape($state) ?></p></div></div>
    </div>
    <?php
}
