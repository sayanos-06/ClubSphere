<?php
if (PHP_SAPI !== 'cli') {
    exit;
}
require dirname(__DIR__) . '/src/database.php';
$email = strtolower(trim($argv[1] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php scripts/organizer.php institute_email\n");
    exit(1);
}
$statement = database()->prepare("UPDATE students SET role = 'organizer' WHERE email = ? RETURNING id");
$statement->execute([$email]);
if (!$statement->fetchColumn()) {
    fwrite(STDERR, "Create the student account first. No account was changed.\n");
    exit(1);
}
echo "Organizer access granted.\n";
