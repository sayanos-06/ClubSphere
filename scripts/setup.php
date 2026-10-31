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
        sample BOOLEAN NOT NULL DEFAULT FALSE,
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
    $pdo->exec("CREATE TABLE IF NOT EXISTS posters (
        activity_id BIGINT PRIMARY KEY REFERENCES activities(id) ON DELETE CASCADE,
        content TEXT NOT NULL,
        mime VARCHAR(30) NOT NULL,
        hash VARCHAR(64) NOT NULL
    )");
    $pdo->exec('CREATE INDEX IF NOT EXISTS registrations_activity ON registrations(activity_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS activities_date ON activities(starts_at)');
    $pdo->exec(
        'ALTER TABLE activities ADD COLUMN IF NOT EXISTS sample BOOLEAN NOT NULL DEFAULT FALSE'
    );
    $pdo->exec('ALTER TABLE students ADD COLUMN IF NOT EXISTS recovery VARCHAR(64)');
    $pdo->exec('ALTER TABLE students ADD COLUMN IF NOT EXISTS version INTEGER NOT NULL DEFAULT 1');
    $clubs = [
        [
            'Cyberlabs',
            'Technical',
            'A community for curious minds who code, create and solve problems together.',
            'CY'
        ],
        [
            'Robotics Club',
            'Technical',
            'From first circuits to working robots. Learn by making things move.',
            'RC'
        ],
        [
            'Manthan',
            'Cultural',
            'Find your rhythm with students who share a love of music and performance.',
            'MA'
        ],
        [
            'Lights Camera ISM',
            'Creative',
            'Explore storytelling, photography and the world through a different lens.',
            'LC'
        ],
        [
            'Literary Society',
            'Literary',
            'A space for conversations, compelling stories and ideas worth sharing.',
            'LS'
        ],
        [
            'Sports Society',
            'Sports',
            'Meet your next team and make time for movement, practice and friendly competition.',
            'SS'
        ]
    ];
    $insert = $pdo->prepare(
        'INSERT INTO clubs (name, category, description, initials) VALUES (?, ?, ?, ?) ON CONFLICT (name) DO NOTHING'
    );
    foreach ($clubs as $club) {
        $insert->execute($club);
    }
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS portal (name VARCHAR(40) PRIMARY KEY, value VARCHAR(100) NOT NULL)'
    );
    if (
        getenv('SAMPLE_ACTIVITIES') === 'true' &&
        !$pdo->query("SELECT value FROM portal WHERE name = 'samples'")->fetchColumn()
    ) {
        $samples = [
            [
                'Cyberlabs',
                'An evening of creative coding',
                'Try your hand at a small web project, exchange ideas and meet fellow students who enjoy building things.',
                'Computer Centre · Workshop Room',
                2,
                '17:30',
                40
            ],
            [
                'Manthan',
                'Open mic, open hearts',
                'Bring a song, a poem or just your curiosity. An informal evening to share something you love and discover new voices.',
                'Student Activity Centre',
                3,
                '18:00',
                60
            ],
            [
                'Lights Camera ISM',
                'Through the campus lens',
                'A relaxed photography walk exploring light, composition and the familiar corners of campus from a fresh perspective.',
                'Main Gate · Meeting Point',
                4,
                '16:00',
                25
            ],
            [
                'Robotics Club',
                'From circuits to first moves',
                'Explore basic robot components and work together on a simple hands-on challenge. Curiosity is the only prerequisite.',
                'Student Activity Centre · Lab',
                5,
                '15:00',
                30
            ],
            [
                'Literary Society',
                'The conversation corner',
                'Bring an interesting idea, a favourite passage or a question you cannot stop thinking about. Read, listen and exchange perspectives.',
                'Central Library · Discussion Room',
                6,
                '17:00',
                35
            ],
            [
                'Sports Society',
                'Make your next move',
                'A friendly chess meetup for new players and regulars alike. Find a partner, practise your opening and enjoy a few games.',
                'Student Activity Centre · Games Room',
                7,
                '17:00',
                32
            ]
        ];
        $insert = $pdo->prepare(
            'INSERT INTO activities (club_id, title, description, venue, starts_at, ends_at, capacity, sample) SELECT id, ?, ?, ?, ?, ?, ?, TRUE FROM clubs WHERE name = ?'
        );
        foreach ($samples as $sample) {
            $start = new DateTime(
                'today +' . $sample[4] . ' days ' . $sample[5],
                new DateTimeZone('Asia/Kolkata')
            );
            $end = (clone $start)->modify('+2 hours');
            $insert->execute([
                $sample[1],
                $sample[2] .
                "\n\nSample activity for project demonstration. This is not a scheduled institute event.",
                $sample[3],
                $start->format('c'),
                $end->format('c'),
                $sample[6],
                $sample[0]
            ]);
        }
        $pdo->exec("INSERT INTO portal (name, value) VALUES ('samples', 'created')");
    }
    $organizer = strtolower(trim(getenv('ORGANIZER_EMAIL') ?: ''));
    $organizer_ready = false;
    if ($organizer !== '') {
        $access = $pdo->prepare("UPDATE students SET role = 'organizer' WHERE email = ?");
        $access->execute([$organizer]);
        $organizer_ready = $access->rowCount() > 0;
        $pdo->prepare(
            'UPDATE activities SET created_by = (SELECT id FROM students WHERE email = ?) WHERE sample = TRUE AND created_by IS NULL'
        )->execute([$organizer]);
    }
    $pdo->commit();
    if ($organizer !== '') {
        echo $organizer_ready
            ? "Organizer account ready.\n"
            : "Organizer account not found. Create the account before provisioning access.\n";
    }
    echo "ClubSphere tables are ready. Existing records were preserved.\n";
} catch (Throwable $error) {
    $pdo->rollBack();
    throw $error;
}
