# ClubSphere

Student club activity and participation portal for IIT (ISM) Dhanbad.

Live website: https://clubsphere-4fkf.onrender.com
Repository branch: main

## Team

| Name | Admission number |
| --- | --- |
| Sayantan Sen | 24JE0851 |
| Nehal Agarwal | 24JE0826 |
| Harsh Kumar | 24JE0545 |
| Chaxu Patel | 24JE0306 |

## Scope

The application uses HTML, an external CSS stylesheet, browser JavaScript events and fetch/JSON, PHP forms and sessions, and PDO prepared statements. These follow Labs 2–10 and Extra Lab. The existing PostgreSQL database is retained in place of the MariaDB demonstration in Extra Lab.

Approved security additions include password hashing, request tokens, single-use recovery codes, session invalidation, and PostgreSQL transactions with row locks. The database unique constraint prevents duplicate student/activity registrations. A locked activity row serializes registration, cancellation, and capacity edits.

No frontend framework, package manager, JavaScript build process, or third-party authentication service is required. Application code contains no comments.

## Code files

Only `public/` is served to browsers. Shared logic and operational scripts remain outside the document root.

| File | Responsibility |
| --- | --- |
| `public/index.php` | Activity search, category/club filters, upcoming/past views and cards. |
| `public/clubs.php` | Club directory and links to each club's activities. |
| `public/activity.php` | Activity details, poster, status, capacity and booking action. |
| `public/register.php` | Authenticated, token-checked registration/cancellation through forms or JSON. |
| `public/signup.php` | Identity and email-format validation, password hashing, student account and recovery-code creation. |
| `public/login.php` | Password verification, failure counters, lockout and sign-in sessions. |
| `public/logout.php` | POST sign-out that clears the session and cookie. |
| `public/recovery.php` | One-time recovery-code display and password reset with code replacement and session revocation. |
| `public/registrations.php` | Current student's registrations, references and recorded attendance. |
| `public/profile.php` | Name changes, password changes and confirmed account deletion. |
| `public/manage.php` | Organizer publishing, ownership checks, editing, posters, cancellation, rosters and attendance. |
| `public/help.php`, `public/privacy.php` | Usage guidance and data-handling notice. |
| `public/health.php` | Database readiness JSON with HTTP 200 or 503. |
| `public/availability.php` | GET-only aggregate availability JSON without participant identities. |
| `public/poster.php` | Stored image delivery with MIME type, ETag and conditional 304 response. |
| `src/bootstrap.php` | Sessions, authorization, request tokens, escaping, redirects, flash messages and exception handling. |
| `src/database.php` | Environment configuration and a reusable PDO PostgreSQL connection per request. |
| `src/activities.php` | Shared activity query, status calculation, registration transactions and event-card rendering. |
| `src/layout.php` | Shared metadata, navigation, branding, footer and content-hashed asset URLs. |
| `src/posters.php` | Upload size/type/dimension validation and Base64/hash preparation. |
| `scripts/setup.php` | Repeatable additive schema setup, club seeding, optional samples and organizer provisioning. |
| `scripts/organizer.php` | CLI promotion of an existing account and assignment of unowned sample activities. |
| `scripts/check.php` | Fifteen targeted checks, including two processes competing for one final place. |
| `scripts/start.sh` | Container entrypoint: setup, checks, port configuration and Apache startup. |
| `public/assets/style.css` | Shared colours, typography, responsive navigation, cards, forms and poster viewer. |
| `public/assets/script.js` | Password visibility, password-match validation, fetch-based booking/availability and poster dialogs. |
| `public/assets/logo.png`, `public/assets/favicon.svg` | Shared ClubSphere branding and browser icon. |
| `public/assets/eye.svg`, `public/assets/eye_off.svg` | Password visibility and poster-preview icons. |
| `Dockerfile` | PHP 8.3/Apache image, required extensions, document root and production configuration. |
| `.env.example` | Placeholder local environment configuration without real credentials. |
| `.dockerignore`, `.gitignore` | Exclude secrets and working files from image builds/version control. |
| `.editorconfig`, `.gitattributes` | Source formatting and line-ending conventions. |
| `README.md` | Project overview, file descriptions, setup, execution, deployment and verification instructions. |

## Database

The main website uses the existing Render Postgres instance. It creates students, clubs, activities, registrations, posters and portal tables. The pre-existing records table is preserved. The repository's default branch is main.

Organizers can attach, replace or remove one JPEG, PNG or WebP poster per activity before it starts. PHP validates the actual image type, a 2 MB size limit and image dimensions. Poster content is stored in PostgreSQL, so it survives container restarts and deployments without a persistent disk. public/poster.php serves the validated image with its MIME type and an ETag. Student cards show the image and an eye control that opens a keyboard-accessible full-poster dialog; a direct image link remains available without JavaScript.

scripts/setup.php applies additive, repeatable setup at container startup. SAMPLE_ACTIVITIES=true inserts six clearly labelled sample activities once. These are project demonstration content, not scheduled institute events. Subsequent deploys do not recreate removed samples or reset their dates.

DATABASE_URL is stored in Render's environment configuration. Do not put real credentials in source control. .env is ignored and excluded from the Docker build.

## Local development

### 1. Install prerequisites and get the source

Install Git, PostgreSQL and PHP 8.2 or later. Enable `PDO`, `pdo_pgsql`, `mbstring` and `fileinfo` in the PHP CLI configuration. The Docker image uses PHP 8.3. Use `php --ini` to find the loaded configuration and `php -m` to check enabled modules. Enable PostgreSQL support in `php.ini`, rather than only through a one-off `-d` option, so the child processes in the concurrency check inherit it.

```powershell
git clone --branch main https://github.com/sayanos-06/ClubSphere.git "Clubsphere Main"
cd "Clubsphere Main"
php --version
php -m
```

For an existing checkout, open a terminal in its root instead of cloning again. If PHP is not on PATH, replace `php` with the PHP executable's full path; for this XAMPP installation, use `& E:\xampp\php\php.exe` in PowerShell.

### 2. Prepare the database and environment

Create a local database and an application user that owns it using PostgreSQL administration tools. For example, run the following as a PostgreSQL administrator, replacing the placeholder password:

```sql
CREATE USER clubsphere WITH PASSWORD 'replace_with_your_local_password';
CREATE DATABASE clubsphere OWNER clubsphere;
```

The application account needs permission to create/alter tables and create/drop the verification schema used by `scripts/check.php`.

```powershell
Copy-Item .env.example .env
```

Edit `.env` for the local database:

```dotenv
DATABASE_URL=postgresql://clubsphere:replace_with_your_local_password@127.0.0.1:5432/clubsphere?sslmode=disable
APP_ENV=local
SAMPLE_ACTIVITIES=true
```

The example disables SSL only for a local server without TLS. Use `sslmode=require` for the hosted connection. URL-encode special characters in database usernames/passwords. Existing process environment variables take precedence over `.env`. A Render internal hostname is reachable only inside Render's private network; use a separate local database unless external access has deliberately been configured.

### 3. Set up, verify and execute

```powershell
php scripts/setup.php
php scripts/check.php
php -S 127.0.0.1:8091 -t public
```

No separate SQL import is required. Open [http://127.0.0.1:8091](http://127.0.0.1:8091) and keep the terminal open. Stop the development server with Ctrl+C. Serve only `public/`, never the repository root. XAMPP Apache can alternatively use `public/` as its document root after enabling the required extensions.

### 4. Use the website

Create a student account with an IIT (ISM) email, admission number and portal-specific password. Save the recovery code privately when shown. Open an activity and reserve a place; My registrations shows the reference and attendance status. Cancel before the event starts to release the place. To publish activities, grant organizer access using the next section.

## Organizer access

First create a normal student account and confirm that its owner can sign in. Then set ORGANIZER_EMAIL in the Render service environment to that confirmed account's institute email and deploy. That existing account receives organizer access at container startup. Public signup always creates a student and never accepts a role field. This also assigns any unowned sample activities to the organizer.

Alternatively, from a trusted environment connected to the database, run:

```text
php scripts/organizer.php student@iitism.ac.in
```

This promotes that existing account and assigns any unowned sample activities to it. No organizer password is published or seeded. Each organizer can manage only activities they own. To use the command with the existing private Render database, run it in a trusted Render environment on the same private network.

## Render deployment

Use a Docker Web Service, not a static site. For the existing deployment, update the existing ClubSphere service rather than creating a duplicate.

1. Push the intended application version to the repository's `main` branch.
2. For a new deployment, choose **New > Web Service** in the Render Dashboard, connect the GitHub provider and select this repository.
3. Select branch `main` and language/runtime **Docker**. Keep the build context at the repository root and use `./Dockerfile`. Leave Docker Command unset so the Dockerfile's `CMD` runs `scripts/start.sh`. No npm build or separate PHP start command is needed.
4. Use the existing PostgreSQL database, or provision one for a separate deployment. For an internal connection, use the same region and a permitted private network. The database account needs the setup and verification permissions described above.
5. Add `DATABASE_URL` with the database's internal URL and `sslmode=require`. Configure optional environment values below. Never commit real credentials.
6. Set the health check path to `/health.php`. Let Render provide `PORT`; the startup script configures Apache accordingly. Enable automatic deployment from the linked branch if pushes should deploy automatically.
7. Deploy and inspect the logs. Startup applies schema setup, runs the 15 checks and starts Apache in the foreground. A setup/check failure stops startup; resolve the logged error and redeploy.
8. Wait for a successful live deployment. Open the website and `/health.php`; expect HTTP 200 with `{"status":"ok","service":"ClubSphere"}`. Check discovery, signup/sign-in and the intended organizer workflow in a browser.

For later changes, commit and push to `main`, then verify the resulting deployment and health response. Existing database records and stored posters remain independent of the web container.

Environment keys:

- DATABASE_URL: existing database internal connection URL, with sslmode=require.
- APP_ENV: optional `production` label; application code does not use it to select behaviour.
- SAMPLE_ACTIVITIES: true for the initial demonstration content.
- ORGANIZER_EMAIL: the authorized organizer's institute email, when ready.
- PORT: provided by Render; container startup falls back to 8080 when absent.

Create the organizer account before setting `ORGANIZER_EMAIL`, then redeploy to apply the promotion. Check current hosting-plan limits and database retention/expiry in the Dashboard.

Render references: [Docker deployment](https://render.com/docs/docker) and [Web Service setup](https://render.com/docs/web-services).

## Verification

scripts/check.php verifies registration, duplicates, capacity, cancellation, closed/cancelled activities and password hashing against temporary tables. It also runs two independent PHP processes against a one-place activity in an isolated verification schema to check simultaneous registration. Verification tables and the schema are removed after checks; real student records are not used.

```text
php scripts/check.php
```

These are targeted checks, not a complete automated test suite for browser, account and upload workflows. Verify those workflows through the website after setup or deployment.

### Common setup issues

- **Could not find driver:** enable `pdo_pgsql` in the configuration shown by `php --ini` and restart the relevant PHP process.
- **Database configuration is missing:** set `DATABASE_URL` in `.env` or the process environment.
- **Database connection fails:** check credentials, database name, port, network access and SSL mode. A private Render hostname does not work locally.
- **Permission denied for schema/table creation:** use the database owner or grant the role the required setup and verification permissions.
- **Concurrency child process fails locally:** ensure the CLI's `php.ini` enables PostgreSQL support for child processes, not just the parent command.
- **Address already in use:** stop the other local server or choose an unused port in the server command.
- **Organizer account not found:** sign up first, then rerun CLI promotion or deployment setup with the matching email.

## Account notes

Use a password unique to this portal. Save the recovery code shown once during signup. A password reset replaces that code and revokes older sessions. Five failed sign-in or recovery attempts pause access for 15 minutes. Sessions expire after one hour of inactivity.

Institute email format is checked, but inbox ownership is not verified. There is no email-sending service or institute single sign-on integration. The portal is a student project, not an official institute login service.
