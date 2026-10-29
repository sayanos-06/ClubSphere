<?php

require dirname(__DIR__) . '/src/database.php';
require dirname(__DIR__) . '/src/activities.php';
date_default_timezone_set('Asia/Kolkata');
$pdo = database();
if (($argv[1] ?? '') === 'race') {
    $schema = $argv[2] ?? '';
    if (!preg_match('/^clubsphere_check_[a-f0-9]{12}$/', $schema)) {
        exit(3);
    }
    $pdo->exec('SET search_path TO ' . $schema . ', public');
    try {
        change_registration((int) $argv[3], (int) $argv[4], 'register');
        echo 'registered';
        exit(0);
    } catch (DomainException $error) {
        echo $error->getMessage();
        exit(2);
    }
}
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
$schema = 'clubsphere_check_' . bin2hex(random_bytes(6));
$pdo->exec('CREATE SCHEMA ' . $schema);
try {
    $pdo->exec('CREATE TABLE ' . $schema . '.activities (LIKE public.activities INCLUDING ALL)');
    $pdo->exec('CREATE TABLE ' . $schema . '.registrations (LIKE public.registrations INCLUDING ALL)');
    $race_id = $pdo->query("INSERT INTO " . $schema . ".activities (club_id, title, description, venue, starts_at, ends_at, capacity) VALUES (1, 'Concurrency verification', 'Isolated concurrent registration check.', 'Verification venue', CURRENT_TIMESTAMP + INTERVAL '1 day', CURRENT_TIMESTAMP + INTERVAL '2 days', 1) RETURNING id")->fetchColumn();
    $pdo->beginTransaction();
    $pdo->query('SELECT id FROM ' . $schema . '.activities WHERE id = ' . (int) $race_id . ' FOR UPDATE');
    $processes = [];
    foreach ([201, 202] as $student_id) {
        $pipes = [];
        $process = proc_open([PHP_BINARY, __FILE__, 'race', $schema, (string) $student_id, (string) $race_id], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('Could not start concurrent registration check.');
        }
        fclose($pipes[0]);
        $processes[] = [$process, $pipes];
    }
    usleep(200000);
    $pdo->commit();
    $results = [];
    foreach ($processes as [$process, $pipes]) {
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $results[] = proc_close($process);
        if ($errors !== '') {
            throw new RuntimeException('Concurrent registration process failed.');
        }
    }
    sort($results);
    check($results === [0, 2], 'two simultaneous students compete for one final place');
    check((int) $pdo->query('SELECT COUNT(*) FROM ' . $schema . '.registrations')->fetchColumn() === 1, 'concurrent requests cannot exceed activity capacity');
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $pdo->exec('DROP SCHEMA ' . $schema . ' CASCADE');
}
echo $passed . " checks passed. Temporary verification data removed.\n";
