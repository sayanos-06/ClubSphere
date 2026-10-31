<?php
function asset_url($file)
{
    $version = substr(hash_file('sha256', dirname(__DIR__) . '/public/' . $file), 0, 12);
    return '/' . $file . '?v=' . $version;
}

function page_start($title, $active = '')
{
    $student = current_student();
    $notice = $_SESSION['flash'] ?? '';
    unset($_SESSION['flash']);
    ?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >
        <meta
            name="description"
            content="Discover club activities at IIT (ISM) Dhanbad. Reserve your place and keep track of your participation with ClubSphere."
        >
        <title><?= escape($title) ?> | ClubSphere</title>
        <link
            rel="icon"
            href="/assets/favicon.svg"
            type="image/svg+xml"
        >
        <link
            rel="stylesheet"
            href="<?= escape(asset_url('assets/style.css')) ?>"
        >
    </head>
    <body>
        <a
            class="skip"
            href="#main"
        >Skip to content</a>
        <div class="institute">
            <div class="container">IIT (ISM) DHANBAD <span class="institute_right">STUDENT CLUB
                    ACTIVITY PORTAL</span>
            </div>
        </div>
        <header class="header">
            <div class="container">
                <a
                    class="brand"
                    href="/index.php"
                >
                    <span class="brand_mark">c<span>•</span>
                    </span> ClubSphere<span class="brand_dot">.</span>
                </a>
                <nav
                    class="nav"
                    aria-label="Main navigation"
                >
                    <a
                        class="<?= $active === 'activities' ? 'active' : '' ?>"
                        href="/index.php"
                    >Discover</a>
                    <a
                        class="<?= $active === 'clubs' ? 'active' : '' ?>"
                        href="/clubs.php"
                    >The clubs</a>
                    <?php if ($student): ?>
                        <a
                            class="<?= $active === 'registrations' ? 'active' : '' ?>"
                            href="/registrations.php"
                        >My registrations</a>
                        <?php if ($student['role'] === 'organizer'): ?>
                            <a href="/manage.php">Organize</a>
                        <?php endif; ?>
                        <a
                            class="account_link"
                            href="/profile.php"
                        ><?= escape(explode(' ', $student['name'])[0]) ?></a>
                        <form
                            class="inline"
                            action="/logout.php"
                            method="post"
                        >
                            <?= token() ?>
                            <button
                                class="text_button"
                                type="submit"
                            >Sign out</button>
                        </form>
                    <?php else: ?>
                        <a href="/login.php">Sign in</a>
                        <a
                            class="button small"
                            href="/signup.php"
                        >Join ClubSphere <span aria-hidden="true">↗</span>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </header>
        <main
            id="main"
            class="container"
        >
            <?php if ($notice): ?>
                <div
                    class="notice"
                    role="status"
                ><?= escape($notice) ?></div>
            <?php endif; ?>
<?php
}

function page_end()
{
    ?>
        </main>
        <footer class="footer">
            <div class="container">
                <div class="footer_brand">
                    <strong>ClubSphere.</strong>
                    <p>A little beyond the classroom.</p>
                </div>
                <div class="footer_links">
                    <a href="/help.php">Help & FAQs</a>
                    <a href="/privacy.php">Privacy</a>
                    <a
                        href="https://iitism.ac.in"
                        target="_blank"
                        rel="noopener"
                    >IIT (ISM) Dhanbad ↗</a>
                    <p>Student project · IIT (ISM) Dhanbad · <?= date('Y') ?></p>
                </div>
            </div>
        </footer>
        <script src="<?= escape(asset_url('assets/script.js')) ?>"></script>
    </body>
</html>
<?php
}
