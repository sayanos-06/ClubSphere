<?php
require dirname(__DIR__) . '/src/bootstrap.php';
if (current_student()) {
    redirect('/registrations.php');
}
$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_token();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $statement = database()->prepare('SELECT * FROM students WHERE email = ?');
    $statement->execute([$email]);
    $student = $statement->fetch();
    $locked = $student && !empty($student['locked_until']) && strtotime($student['locked_until']) > time();
    if (!$locked && $student && password_verify($password, $student['password'])) {
        database()->prepare('UPDATE students SET failures = 0, locked_until = NULL WHERE id = ?')->execute([$student['id']]);
        session_regenerate_id(true);
        $_SESSION = ['student_id' => $student['id'], 'active_at' => time()];
        flash('Welcome back, ' . explode(' ', $student['name'])[0] . '.');
        redirect('/registrations.php');
    }
    if ($student && !$locked) {
        database()->prepare("UPDATE students SET failures = CASE WHEN locked_until < CURRENT_TIMESTAMP THEN 1 ELSE failures + 1 END, locked_until = CASE WHEN failures >= 4 AND (locked_until IS NULL OR locked_until >= CURRENT_TIMESTAMP) THEN CURRENT_TIMESTAMP + INTERVAL '15 minutes' ELSE NULL END WHERE id = ?")->execute([$student['id']]);
    }
    $error = 'Email or password is incorrect. After five attempts, sign-in pauses for 15 minutes.';
}
page_start('Sign in');
?>
<div class="auth"><p class="eyebrow">YOUR CAMPUS. YOUR COMMUNITY.</p><h1>Welcome back.</h1><p class="muted">Sign in to find your next activity.</p>
<?php if ($error): ?><div class="error" role="alert"><?= escape($error) ?></div><?php endif; ?>
<form method="post" action="/login.php" class="validated">
<?= token() ?>
<div class="field"><label for="email">Institute email</label><input id="email" name="email" type="email" autocomplete="username" value="<?= escape($email) ?>" maxlength="160" required placeholder="you@iitism.ac.in"></div>
<div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" maxlength="72" required><button type="button" class="text_button password_toggle" data-password="password">Show password</button></div>
<p class="form_message error" hidden role="alert"></p><button class="primary wide" type="submit">Sign in →</button>
</form><p class="form_footer">New here? <a href="/signup.php">Create your account</a></p><p class="form_footer"><a href="/help.php#access">Trouble signing in?</a></p></div>
<?php page_end(); ?>
