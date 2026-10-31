<?php
require dirname(__DIR__) . '/src/bootstrap.php';
if (current_student()) {
    redirect('/registrations.php');
}
$error = '';
$values = ['name' => '', 'admission' => '', 'email' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_token();
    foreach ($values as $key => $value) {
        $values[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    $values['email'] = strtolower($values['email']);
    $values['admission'] = strtoupper($values['admission']);
    $password = (string) ($_POST['password'] ?? '');
    if (strlen($values['name']) < 2 || strlen($values['name']) > 100) {
        $error = 'Enter your full name, between 2 and 100 characters.';
    } elseif (!preg_match('/^[A-Z0-9-]{4,25}$/', $values['admission'])) {
        $error = 'Enter a valid admission number (4–25 letters, numbers or hyphens).';
    } elseif (
        !filter_var($values['email'], FILTER_VALIDATE_EMAIL) ||
        strlen($values['email']) > 160 ||
        !preg_match('/@(?:[a-z0-9-]+\.)*iitism\.ac\.in$/', $values['email'])
    ) {
        $error = 'Use your IIT (ISM) institute email ending in iitism.ac.in.';
    } elseif (strlen($password) < 10 || strlen($password) > 72) {
        $error = 'Choose a password between 10 and 72 characters.';
    } elseif ($password !== ($_POST['confirm'] ?? '')) {
        $error = 'The passwords do not match.';
    } else {
        try {
            $recovery = bin2hex(random_bytes(16));
            $statement = database()->prepare(
                'INSERT INTO students (name, admission, email, password, recovery) VALUES (?, ?, ?, ?, ?) RETURNING id'
            );
            $statement->execute([
                $values['name'],
                $values['admission'],
                $values['email'],
                password_hash($password, PASSWORD_DEFAULT),
                hash('sha256', $recovery)
            ]);
            $id = $statement->fetchColumn();
            session_regenerate_id(true);
            $_SESSION = [
                'student_id' => $id,
                'active_at' => time(),
                'version' => 1,
                'recovery_code' => $recovery
            ];
            flash('Your account is ready. Find an activity and make it yours.');
            redirect('/recovery.php');
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23505') {
                throw $exception;
            }
            $error = 'An account already uses that email or admission number. Please sign in.';
        }
    }
}
page_start('Create an account');
?>
<div class="auth">
    <p class="eyebrow">FIND YOUR PEOPLE</p>
    <h1>Campus starts here.</h1>
    <p class="muted">One account. A world beyond your timetable.</p>
    <?php if ($error): ?>
        <div
            class="error"
            role="alert"
        ><?= escape($error) ?></div>
    <?php endif; ?>
    <form
        method="post"
        action="/signup.php"
        class="validated"
    >
        <?= token() ?>
        <div class="field">
            <label for="name">Full name</label>
            <input
                id="name"
                name="name"
                autocomplete="name"
                minlength="2"
                maxlength="100"
                required
                value="<?= escape($values['name']) ?>"
            >
        </div>
        <div class="field">
            <label for="admission">Admission number</label>
            <input
                id="admission"
                name="admission"
                minlength="4"
                maxlength="25"
                pattern="[A-Za-z0-9-]{4,25}"
                required
                value="<?= escape($values['admission']) ?>"
                placeholder="Your institute admission number"
            >
        </div>
        <div class="field">
            <label for="email">Institute email</label>
            <input
                id="email"
                name="email"
                type="email"
                autocomplete="username"
                maxlength="160"
                required
                value="<?= escape($values['email']) ?>"
                placeholder="you@iitism.ac.in"
            >
            <small>Use your institute email. This student project does not verify inbox ownership.
            </small>
        </div>
        <div class="field">
            <label for="password">Password</label>
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
            <small>Use 10–72 characters. Choose a password unique to this portal.</small>
        </div>
        <div class="field">
            <label for="confirm">Confirm password</label>
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
        <p class="muted">Read how we handle your details in our <a href="/privacy.php">privacy
                notice</a>.</p>
        <p
            class="form_message error"
            hidden
            role="alert"
        ></p>
        <button
            class="primary wide"
            type="submit"
        >Create account</button>
    </form>
    <p class="form_footer">Already part of the community? <a href="/login.php">Sign in</a>
    </p>
</div>
<?php page_end(); ?>
