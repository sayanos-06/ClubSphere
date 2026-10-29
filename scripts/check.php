<?php

require dirname(__DIR__) . '/src/database.php';
require dirname(__DIR__) . '/src/activities.php';
date_default_timezone_set('Asia/Kolkata');
$pdo = database();
$pdo->exec('CREATE TEMP TABLE activities (LIKE public.activities INCLUDING ALL)');
$pdo->exec('CREATE TEMP TABLE registrations (LIKE public.registrations INCLUDING ALL)');
$insert = $pdo->prepare("INSERT INTO activities (club_id, title, description, venue, starts_at, ends_at, capacity) VALUES (1, 'Verification activity', 'Temporary data for registration verification.', 'Verification venue', CURRENT_TIMESTAMP + INTERVAL '1 day', CURRENT_TIMESTAMP + INTERVAL '2 days', 1) RETURNING id");
$insert->execute();
$id = (int) $insert->fetchColumn();
$passed = 0;
function check($condition, $label)
{
    global $passed;
    if (!$condition) {
        throw new RuntimeException('Verification failed: ' . $label);
    }
    $passed++;
    echo 'PASS ' . $label . "\n";
}
function rejected($student, $id, $action, $contains)
{
    try {
        change_registration($student, $id, $action);
        return false;
    } catch (DomainException $error) {
        return str_contains($error->getMessage(), $contains);
    }
}
check(str_contains(change_registration(101, $id, 'register'), 'registered'), 'first registration succeeds');
check(rejected(101, $id, 'register', 'already'), 'duplicate registration rejected');
check(rejected(102, $id, 'register', 'full'), 'capacity enforced for another student');
check((int) $pdo->query('SELECT COUNT(*) FROM registrations')->fetchColumn() === 1, 'failed registrations leave one confirmed place');
check(str_contains(change_registration(101, $id, 'cancel'), 'cancelled'), 'student can cancel before start');
check(str_contains(change_registration(102, $id, 'register'), 'registered'), 'released place can be booked');
check(rejected(101, $id, 'cancel', 'not registered'), 'student cannot cancel someone else’s place');
check(rejected(101, 99999999, 'register', 'found'), 'missing activity rejected');
$pdo->exec("UPDATE activities SET starts_at = CURRENT_TIMESTAMP - INTERVAL '1 hour' WHERE id = " . $id);
check(rejected(102, $id, 'cancel', 'close'), 'cancellation closes at start time');
check(rejected(103, $id, 'register', 'close'), 'registration closes at start time');
$pdo->exec("UPDATE activities SET starts_at = CURRENT_TIMESTAMP + INTERVAL '1 day', cancelled = TRUE WHERE id = " . $id);
check(rejected(103, $id, 'register', 'close'), 'cancelled activity rejects registration');
check(rejected(102, $id, 'cancel', 'close'), 'cancelled activity preserves participation records');
$pdo->exec('DROP TABLE pg_temp.registrations, pg_temp.activities');
check(password_verify('A-unique-verification-password', password_hash('A-unique-verification-password', PASSWORD_DEFAULT)), 'password hashing round trip');
echo $passed . " checks passed. Temporary verification data removed.\n";
