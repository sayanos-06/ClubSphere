<?php
require dirname(__DIR__) . '/src/bootstrap.php';
$clubs = database()
    ->query(
        'SELECT c.*, (SELECT COUNT(*) FROM activities a WHERE a.club_id = c.id AND a.cancelled = FALSE AND a.ends_at > CURRENT_TIMESTAMP) AS upcoming FROM clubs c ORDER BY c.name'
    )
    ->fetchAll();
page_start('The clubs', 'clubs');
?>
<div class="page_intro">
    <p class="eyebrow">DIFFERENT INTERESTS. SHARED ENERGY.</p>
    <h1>Find your circle.</h1>
    <p>Code something. Take the stage. Start a conversation. Discover the communities that make
        campus feel like yours.</p>
</div>
<div class="cards">
    <?php foreach ($clubs as $club): ?>
        <div class="club_card">
            <span class="club_monogram art_<?= escape(strtolower($club['category'])) ?>"><?= escape($club['initials']) ?></span>
            <p class="eyebrow"><?= escape($club['category']) ?></p>
            <h2><?= escape($club['name']) ?></h2>
            <p><?= escape($club['description']) ?></p>
            <a href="/index.php?club=<?= (int) $club['id'] ?>#activities"><?= (int) $club['upcoming'] ?> upcoming activities</a>
        </div>
    <?php endforeach; ?>
</div>
<p class="muted">Club descriptions are introductory project content. Activities are published by
    portal organizers.</p>
<?php page_end(); ?>
