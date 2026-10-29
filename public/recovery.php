<?php
require dirname(__DIR__) . '/src/bootstrap.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_token();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $code = strtolower(str_replace(['-', ' '], '', trim((string) ($_POST['code'] ?? ''))));
    $password = (string) ($_POST['password'] ?? '');
    if (
        strlen($password) < 10 ||
        strlen($password) > 72 ||
        $password !== ($_POST['confirm'] ?? '')
    ) {
        $error = 'Choose matching new passwords of 10–72 characters.';
    } else {
        $pdo = database();
        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare('SELECT * FROM students WHERE email = ? FOR UPDATE');
            $statement->execute([$email]);
            $student = $statement->fetch();
            $locked =
                $student &&
                !empty($student['locked_until']) &&
                strtotime($student['locked_until']) > time();
            if (
                $student &&
                !$locked &&
                !empty($student['recovery']) &&
                hash_equals($student['recovery'], hash('sha256', $code))
            ) {
                $recovery = bin2hex(random_bytes(16));
                $pdo->prepare(
                    'UPDATE students SET password = ?, recovery = ?, version = version + 1, failures = 0, locked_until = NULL WHERE id = ?'
                )->execute([
                    password_hash($password, PASSWORD_DEFAULT),
                    hash('sha256', $recovery),
                    $student['id']
                ]);
                $pdo->commit();
                session_regenerate_id(true);
                $_SESSION = ['recovery_code' => $recovery];
                flash(
                    'Password reset. Previous sessions have been signed out. Save your new recovery code before signing in.'
                );
                redirect('/recovery.php');
            }
            if ($student && !$locked) {
                $pdo->prepare(
                    "UPDATE students SET failures = CASE WHEN locked_until < CURRENT_TIMESTAMP THEN 1 ELSE failures + 1 END, locked_until = CASE WHEN failures >= 4 AND (locked_until IS NULL OR locked_until >= CURRENT_TIMESTAMP) THEN CURRENT_TIMESTAMP + INTERVAL '15 minutes' ELSE NULL END WHERE id = ?"
                )->execute([$student['id']]);
            }
            $pdo->commit();
            $error =
                'Email or recovery code is incorrect, or access is paused. After five attempts, wait 15 minutes before trying again.';
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}
$code = $_SESSION['recovery_code'] ?? '';
unset($_SESSION['recovery_code']);
page_start($code ? 'Save your recovery code' : 'Recover your account');
?>
<div class="auth">
    <p class="eyebrow">A WAY BACK IN</p>
    <?php if ($code): ?>
        <h1>Keep this somewhere safe.</h1>
        <p class="muted">Your recovery code lets you reset your password if you forget it. It is shown
            once. Save it in your password manager or write it down privately.</p>
        <p class="recovery_code"><?= escape(implode('-', str_split($code, 8))) ?></p>
        <p class="muted">Treat this code like a password. Do not share it. Using it replaces it with a
            new code.</p>
        <a
            class="button wide"
            href="<?= current_student() ? '/index.php' : '/login.php' ?>"
        ><?= current_student() ? 'I’ve saved it · discover activities →' : 'I’ve saved it · sign in →' ?></a>
    <?php else: ?>
        <h1>Let’s get you back in.</h1>
        <p class="muted">Enter your institute email and the recovery code you saved when you created
            your account.</p>
        <?php if ($error): ?>
            <div
                class="error"
                role="alert"
            ><?= escape($error) ?></div>
        <?php endif; ?>
        <form
            method="post"
            class="validated"
        >
            <?= token() ?>
            <div class="field">
                <label for="email">Institute email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    autocomplete="username"
                    maxlength="160"
                    required
                >
            </div>
            <div class="field">
                <label for="code">Recovery code</label>
                <input
                    id="code"
                    name="code"
                    autocomplete="off"
                    maxlength="40"
                    required
                >
            </div>
            <div class="field">
                <label for="password">New password</label>
                <div class="password_field">
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        minlength="10"
                        maxlength="72"
                        required
                    >
                    <button
                        type="button"
                        class="password_toggle"
                        data-password="password"
                        aria-label="Show password"
                        aria-controls="password"
                        aria-pressed="false"
                        title="Show password"
                    >
                        <img src="/assets/eye.svg" alt="" width="20" height="20">
                    </button>
                </div>
            </div>
            <div class="field">
                <label for="confirm">Confirm new password</label>
                <div class="password_field">
                    <input
                        id="confirm"
                        name="confirm"
                        type="password"
                        autocomplete="new-password"
                        minlength="10"
                        maxlength="72"
                        required
                    >
                    <button
                        type="button"
                        class="password_toggle"
                        data-password="confirm"
                        aria-label="Show password"
                        aria-controls="confirm"
                        aria-pressed="false"
                        title="Show password"
                    >
                        <img src="/assets/eye.svg" alt="" width="20" height="20">
                    </button>
                </div>
            </div>
            <p
                class="form_message error"
                role="alert"
                hidden
            ></p>
            <button
                class="primary wide"
                type="submit"
            >Reset password</button>
        </form>
        <p class="form_footer">
            <a href="/login.php">Back to sign in</a> · <a href="/help.php#access">Need help?</a>
        </p>
    <?php endif; ?>
</div>
<?php page_end(); ?>
