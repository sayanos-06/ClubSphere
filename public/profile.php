<?php
require dirname(__DIR__) . '/src/bootstrap.php';
$student = require_student();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_token();
    if (($_POST['action'] ?? '') === 'delete') {
        $statement = database()->prepare('SELECT password FROM students WHERE id = ?');
        $statement->execute([$student['id']]);
        $owned = database()->prepare('SELECT COUNT(*) FROM activities WHERE created_by = ?');
        $owned->execute([$student['id']]);
        if (!password_verify((string) ($_POST['current_delete'] ?? ''), $statement->fetchColumn()) || ($_POST['confirm_delete'] ?? '') !== 'DELETE') {
            $error = 'Enter your current password and type DELETE to remove your account.';
        } elseif ((int) $owned->fetchColumn() > 0) {
            $error = 'You own published activities. Ask the portal administrator to transfer them before deleting your account.';
        } else {
            $pdo = database();
            $pdo->beginTransaction();
            try {
                $pdo->prepare('DELETE FROM registrations WHERE student_id = ?')->execute([$student['id']]);
                $pdo->prepare('DELETE FROM students WHERE id = ?')->execute([$student['id']]);
                $pdo->commit();
            } catch (Throwable $exception) {
                $pdo->rollBack();
                throw $exception;
            }
            $_SESSION = [];
            session_regenerate_id(true);
            flash('Your account and registrations have been deleted.');
            redirect('/login.php');
        }
    } elseif (($_POST['action'] ?? '') === 'name') {
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
            database()->prepare('UPDATE students SET password = ?, version = version + 1 WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $student['id']]);
            $_SESSION['version'] = (int) $student['version'] + 1;
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
<div class="paper"><h2>Change password</h2><form method="post" class="validated"><?= token() ?><input type="hidden" name="action" value="password"><div class="field"><label for="current">Current password</label><input id="current" name="current" type="password" autocomplete="current-password" maxlength="72" required></div><div class="field"><label for="password">New password</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="10" maxlength="72" required></div><div class="field"><label for="confirm">Confirm new password</label><input id="confirm" name="confirm" type="password" autocomplete="new-password" minlength="10" maxlength="72" required></div><p class="form_message error" role="alert" hidden></p><button class="primary" type="submit">Update password</button></form></div>
<div class="paper"><h2>Delete your account</h2><p class="muted">This permanently removes your profile, registrations and attendance records. Places you hold will be released. This cannot be undone.</p><form method="post"><?= token() ?><input type="hidden" name="action" value="delete"><div class="field"><label for="current_delete">Current password</label><input id="current_delete" name="current_delete" type="password" autocomplete="current-password" maxlength="72" required></div><div class="field"><label for="confirm_delete">Type DELETE to confirm</label><input id="confirm_delete" name="confirm_delete" pattern="DELETE" required autocomplete="off"></div><button class="button secondary" type="submit">Permanently delete my account</button></form></div></div>
<?php page_end(); ?>
