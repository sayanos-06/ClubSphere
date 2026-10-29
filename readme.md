# ClubSphere

Student club activity and participation portal for IIT (ISM) Dhanbad.

Live website: https://clubsphere-4fkf.onrender.com
Repository branch: main
Existing database API branch: database

## Scope

The application uses HTML, an external CSS stylesheet, browser JavaScript events and fetch/JSON, PHP forms and sessions, and PDO prepared statements. These follow Labs 2–10 and Extra Lab. The existing PostgreSQL database is retained in place of the MariaDB demonstration in Extra Lab.

Approved security additions include password hashing, request tokens, single-use recovery codes, session invalidation, and PostgreSQL transactions with row locks. The database unique constraint prevents duplicate student/activity registrations. A locked activity row serializes registration, cancellation, and capacity edits.

No frontend framework, package manager, JavaScript build process, or third-party authentication service is required. Application code contains no comments.

## Pages

- index.php: search and filter upcoming or past activities.
- clubs.php: club directory.
- activity.php: activity details, capacity and registration.
- signup.php and login.php: student accounts.
- recovery.php: single-use recovery-code password reset.
- registrations.php: personal registrations and recorded attendance.
- profile.php: name, password and account deletion.
- manage.php: organizer publishing, editing, cancellation and attendance.
- help.php and privacy.php: student guidance and data handling.
- health.php: database readiness response.
- availability.php: aggregate availability JSON, without participant details.

## Database

The main website uses the same existing Render Postgres instance as the database branch. It creates students, clubs, activities, registrations and portal tables. The pre-existing records table and database branch are preserved.

scripts/setup.php applies additive, repeatable setup at container startup. SAMPLE_ACTIVITIES=true inserts six clearly labelled sample activities once. These are project demonstration content, not scheduled institute events. Subsequent deploys do not recreate removed samples or reset their dates.

DATABASE_URL is stored in Render's environment configuration. Do not put real credentials in source control. .env is ignored and excluded from the Docker build.

## Local development

Use PHP 8.2 or later with PDO PostgreSQL and mbstring enabled. Copy .env.example to .env and supply a PostgreSQL connection URL accessible from your computer. A Render internal hostname works only from Render. The existing Render database currently blocks external access; local development requires an explicitly configured external connection or a separate local PostgreSQL database.

From this folder, run:

```powershell
E:\xampp\php\php.exe -d extension=pdo_pgsql scripts/setup.php
E:\xampp\php\php.exe -d extension=pdo_pgsql -S 127.0.0.1:8091 -t public
```

Open http://127.0.0.1:8091. Serve only public, never the repository root. XAMPP's Apache can also use public as its document root once PDO PostgreSQL is enabled.

## Organizer access

Create a normal student account with the intended institute email. From a trusted environment connected to the database, run:

```text
php scripts/organizer.php student@iitism.ac.in
```

This promotes that existing account and assigns any unowned sample activities to it. No organizer password is published or seeded. Each organizer can manage only activities they own. To use the command with the existing private Render database, run it in a trusted Render environment on the same private network.

## Render deployment

The clubsphere service deploys the main branch automatically using the Dockerfile. It runs in Singapore on the free plan alongside the existing database. The container applies setup, runs the database checks and starts Apache on Render's PORT. Configure an HTTP health check at /health.php in Render settings.

Environment keys:

- DATABASE_URL: existing database internal connection URL, with sslmode=require.
- APP_ENV: production.
- SAMPLE_ACTIVITIES: true for the initial demonstration content.

Render's free web service may need time to wake after inactivity. The existing free database is scheduled to expire on 15 November 2026; upgrade or migrate it before then to retain service availability. No paid resources were created.

## Verification

scripts/check.php verifies registration, duplicates, capacity, cancellation, closed/cancelled activities and password hashing against temporary tables. It also runs two independent PHP processes against a one-place activity in an isolated verification schema to check simultaneous registration. Verification tables and the schema are removed after checks; real student records are not used.

```text
php scripts/check.php
```

Live HTTP checks additionally covered public pages, protected access, invalid request tokens, duplicate accounts, signup, registration and cancellation, profile updates, password change, recovery-code replay rejection, session revocation and removal of the temporary test account.

## Account notes

Use a password unique to this portal. Save the recovery code shown once during signup. A password reset replaces that code and revokes older sessions. Five failed sign-in or recovery attempts pause access for 15 minutes. Sessions expire after one hour of inactivity.

Institute email format is checked, but inbox ownership is not verified. There is no email-sending service or institute single sign-on integration. The portal is a student project, not an official institute login service.
