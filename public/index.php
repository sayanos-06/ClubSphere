<?php
require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/activities.php';
$search = substr(trim((string) ($_GET['search'] ?? '')), 0, 100);
$category = (string) ($_GET['category'] ?? '');
$club = (int) ($_GET['club'] ?? 0);
$period = (string) ($_GET['period'] ?? 'upcoming');
$categories = ['Technical', 'Cultural', 'Creative', 'Literary', 'Sports'];
$where = ['a.cancelled = FALSE'];
$parameters = [];
if ($period === 'past') {
    $where[] = 'a.ends_at <= CURRENT_TIMESTAMP';
} else {
    $where[] = 'a.ends_at > CURRENT_TIMESTAMP';
}
if ($search !== '') {
    $where[] = '(a.title ILIKE ? OR a.description ILIKE ? OR c.name ILIKE ? OR a.venue ILIKE ?)';
    $parameters = array_fill(0, 4, '%' . $search . '%');
}
if (in_array($category, $categories, true)) {
    $where[] = 'c.category = ?';
    $parameters[] = $category;
}
if ($club > 0) {
    $where[] = 'a.club_id = ?';
    $parameters[] = $club;
}
$statement = database()->prepare(
    activity_query() .
        ' WHERE ' .
        implode(' AND ', $where) .
        ' ORDER BY a.starts_at ' .
        ($period === 'past' ? 'DESC' : 'ASC')
);
$statement->execute($parameters);
$activities = $statement->fetchAll();
$clubs = database()->query('SELECT id, name FROM clubs ORDER BY name')->fetchAll();
$totals = database()
    ->query(
        'SELECT (SELECT COUNT(*) FROM clubs) AS clubs, (SELECT COUNT(*) FROM activities WHERE cancelled = FALSE AND ends_at > CURRENT_TIMESTAMP) AS activities'
    )
    ->fetch();
page_start('Discover your campus', 'activities');
?>
<div class="hero">
    <div class="hero_copy">
        <p class="eyebrow">
            <span class="tiny_dot">●</span> GOOD THINGS HAPPEN OUTSIDE THE CLASSROOM
        </p>
        <h1>Find your people.
            <br>Find your <em>thing.</em>
        </h1>
        <p class="hero_description">The workshop you didn’t know you needed. The stage you’ve been
            waiting for. Discover what’s happening at IIT (ISM) Dhanbad.</p>
        <a
            class="button"
            href="#activities"
        >Explore activities <span aria-hidden="true">↓</span>
        </a>
        <a
            class="hero_link"
            href="/clubs.php"
        >Meet the clubs ↗</a>
        <p class="hero_note">For the curious. The creative. The yet-to-figure-it-out.</p>
    </div>
    <div
        class="hero_visual"
        aria-label="Your campus, your community"
    >
        <div class="visual_top">THE CAMPUS COLLECTIVE <span>EST. 2026</span>
        </div>
        <div class="orbit">
            <span class="orbit_center">A place to
                <br>
                <em>belong.</em>
            </span>
        </div>
        <div class="visual_tags">
            <span>CREATE</span>
            <span>CONNECT</span>
            <span>EXPLORE</span>
        </div>
        <div class="visual_bottom">IIT (ISM) DHANBAD <span>↗</span>
        </div>
    </div>
</div>
<div class="campus_strip">
    <div>
        <strong><?= (int) $totals['clubs'] ?></strong> communities to discover
    </div>
    <div>
        <strong><?= (int) $totals['activities'] ?></strong> upcoming activities
    </div>
    <div>
        <strong>One</strong> campus. Endless possibilities.
    </div>
</div>
<div
    id="activities"
    class="section_heading"
>
    <p class="eyebrow">MAKE ROOM FOR SOMETHING NEW</p>
    <h2>What’s happening.</h2>
    <p class="muted">A good plan starts with something you’re curious about. All times are in IST.
    </p>
</div>
<form
    action="/index.php#activities"
    method="get"
    class="filters"
>
    <div class="search_field">
        <label for="search">Find an activity</label>
        <input
            id="search"
            name="search"
            placeholder="Search activities, clubs or venues…"
            maxlength="100"
            value="<?= escape($search) ?>"
        >
    </div>
    <div class="filter_field">
        <label for="category">Interest</label>
        <select
            id="category"
            name="category"
        >
            <option value="">All interests</option>
            <?php foreach ($categories as $item): ?>
                <option
                    <?= $category === $item ? 'selected' : '' ?>
                    value="<?= escape($item) ?>"
                ><?= escape($item) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter_field">
        <label for="club">Club</label>
        <select
            id="club"
            name="club"
        >
            <option value="0">All clubs</option>
            <?php foreach ($clubs as $item): ?>
                <option
                    value="<?= (int) $item['id'] ?>"
                    <?= $club === (int) $item['id'] ? 'selected' : '' ?>
                ><?= escape($item['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter_field">
        <label for="period">When</label>
        <select
            id="period"
            name="period"
        >
            <option value="upcoming">Upcoming</option>
            <option
                value="past"
                <?= $period === 'past' ? 'selected' : '' ?>
            >Past activities</option>
        </select>
    </div>
    <button
        class="primary"
        type="submit"
    >Find activities</button>
</form>
<div class="results_line">
    <span><?= count($activities) ?> <?= count($activities) === 1 ? 'activity' : 'activities' ?> to explore</span>
    <a href="/index.php#activities">Clear filters</a>
</div>
<div
    class="cards"
    id="activity_list"
>
    <?php foreach ($activities as $activity) { activity_card($activity); } ?>
</div>
<?php if (!$activities): ?>
    <div class="empty paper">
        <p class="eyebrow">SOMETHING GOOD IS ON ITS WAY</p>
        <h2><?= $search || $category || $club ? 'No activities match just yet.' : 'A little quiet. For now.' ?></h2>
        <p><?= $search || $category || $club ? 'Try a different interest, club or search term.' : 'Club organizers will publish upcoming activities here. In the meantime, meet the communities behind them.' ?></p>
        <a
            class="button secondary"
            href="<?= $search || $category || $club ? '/index.php#activities' : '/clubs.php' ?>"
        ><?= $search || $category || $club ? 'Reset your search' : 'Discover the clubs ↗' ?></a>
    </div>
<?php endif; ?>
<p
    id="live_message"
    class="muted"
    aria-live="polite"
>Places update when you refresh availability.</p>
<button
    id="refresh_places"
    type="button"
    class="text_button"
>Refresh availability ↻</button>
<div class="join_banner">
    <p class="eyebrow">YOUR NEXT CHAPTER</p>
    <h2>There’s more to campus
        <br>than your timetable.
    </h2>
    <p>Show up for something new. You might just find your people.</p>
    <a
        class="button"
        href="<?= current_student() ? '/clubs.php' : '/signup.php' ?>"
    ><?= current_student() ? 'Find a community ↗' : 'Make yourself at home ↗' ?></a>
</div>
<?php page_end(); ?>
