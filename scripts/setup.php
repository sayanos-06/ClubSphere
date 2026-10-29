<?php

require dirname(__DIR__) . '/src/database.php';
$pdo = database();
$pdo->beginTransaction();
try {
    $pdo->exec('SELECT pg_advisory_xact_lock(634201)');
    $pdo->exec("CREATE TABLE IF NOT EXISTS students (
        id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        admission VARCHAR(25) NOT NULL UNIQUE,
        email VARCHAR(160) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(15) NOT NULL DEFAULT 'student' CHECK (role IN ('student', 'organizer')),
        failures INTEGER NOT NULL DEFAULT 0,
        locked_until TIMESTAMPTZ,
        created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS clubs (
        id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        category VARCHAR(30) NOT NULL,
        description VARCHAR(400) NOT NULL,
        initials VARCHAR(4) NOT NULL
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS activities (
        id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
        club_id BIGINT NOT NULL REFERENCES clubs(id),
        title VARCHAR(120) NOT NULL,
        description TEXT NOT NULL,
        venue VARCHAR(150) NOT NULL,
        starts_at TIMESTAMPTZ NOT NULL,
        ends_at TIMESTAMPTZ NOT NULL,
        capacity INTEGER NOT NULL CHECK (capacity BETWEEN 1 AND 10000),
        cancelled BOOLEAN NOT NULL DEFAULT FALSE,
        created_by BIGINT REFERENCES students(id),
        created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CHECK (ends_at > starts_at)
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS registrations (
        id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
        student_id BIGINT NOT NULL REFERENCES students(id),
        activity_id BIGINT NOT NULL REFERENCES activities(id),
        attended BOOLEAN NOT NULL DEFAULT FALSE,
        registered_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE (student_id, activity_id)
    )");
    $pdo->exec('CREATE INDEX IF NOT EXISTS registrations_activity ON registrations(activity_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS activities_date ON activities(starts_at)');
    $clubs = [
        ['Cyberlabs', 'Technical', 'A community for curious minds who code, create and solve problems together.', 'CY'],
        ['Robotics Club', 'Technical', 'From first circuits to working robots. Learn by making things move.', 'RC'],
        ['Manthan', 'Cultural', 'Find your rhythm with students who share a love of music and performance.', 'MA'],
        ['Lights Camera ISM', 'Creative', 'Explore storytelling, photography and the world through a different lens.', 'LC'],
        ['Literary Society', 'Literary', 'A space for conversations, compelling stories and ideas worth sharing.', 'LS'],
        ['Sports Society', 'Sports', 'Meet your next team and make time for movement, practice and friendly competition.', 'SS']
    ];
    $insert = $pdo->prepare('INSERT INTO clubs (name, category, description, initials) VALUES (?, ?, ?, ?) ON CONFLICT (name) DO NOTHING');
    foreach ($clubs as $club) {
        $insert->execute($club);
    }
    $pdo->commit();
    echo "ClubSphere tables are ready. Existing records were preserved.\n";
} catch (Throwable $error) {
    $pdo->rollBack();
    throw $error;
}
