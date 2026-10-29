<?php
require dirname(__DIR__) . '/src/bootstrap.php';
$student = require_student();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_token();
    if (($_POST['action'] ?? '') === 'name') {
        $name = trim((string) ($_POST['name'] ?? ''));
        if (strlen($name) < 2 || strlen($name) > 100) {
            $error = 'Your name must have 2–100 characters.';
        } else {
            database()->prepare('UPDATE students SET name = ? WHERE id = ?')->execute([$name, $student['id']]);
            flash('Your name has been updated.');
            redirect('/profile.php');
        }
    } else {
        $statement = database()->prepare('SELECT password FROM students WHERE id = ?');
        $statement->execute([$student['id']]);
        $password = (string) ($_POST['password'] ?? '');
        if (!password_verify((string) ($_POST['current'] ?? ''), $statement->fetchColumn())) {
            $error = 'Your current password is incorrect.';
        } elseif (strlen($password) < 10 || strlen($password) > 72 || $password !== ($_POST['confirm'] ?? '')) {
            $error = 'New passwords must match and have 10–72 characters.';
        } else {
            database()->prepare('UPDATE students SET password = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $student['id']]);
            session_regenerate_id(true);
            unset($_SESSION['token']);
            flash('Your password has been changed.');
            redirect('/profile.php');
        }
    }
}
page_start('Your account');
?>
<div class="prose"><p class="eyebrow">YOUR CLUBSPHERE</p><h1>Your account.</h1>
<?php if ($error): ?><div class="error" role="alert"><?= escape($error) ?></div><?php endif; ?>
<div class="paper"><h2>Student details</h2><p class="muted"><?= escape($student['admission']) ?> · <?= escape($student['email']) ?></p><form method="post" class="validated"><?= token() ?><input type="hidden" name="action" value="name"><div class="field"><label for="name">Full name</label><input id="name" name="name" minlength="2" maxlength="100" required value="<?= escape($student['name']) ?>"></div><button class="primary" type="submit">Save details</button></form></div>
<div class="paper"><h2>Change password</h2><form method="post" class="validated"><?= token() ?><input type="hidden" name="action" value="password"><div class="field"><label for="current">Current password</label><input id="current" name="current" type="password" autocomplete="current-password" maxlength="72" required></div><div class="field"><label for="password">New password</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="10" maxlength="72" required></div><div class="field"><label for="confirm">Confirm new password</label><input id="confirm" name="confirm" type="password" autocomplete="new-password" minlength="10" maxlength="72" required></div><p class="form_message error" role="alert" hidden></p><button class="primary" type="submit">Update password</button></form></div></div>
<?php page_end(); ?>
