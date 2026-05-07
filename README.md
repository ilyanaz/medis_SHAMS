# Medis SHAMS

Laravel starter project for a medical surveillance and occupational health system.

## Stack

- Backend: PHP 8.2 with Laravel 12
- Frontend: Blade, HTML, CSS, JavaScript
- Database: MySQL
- Database admin tool: MySQL Workbench
- Server target: DigitalOcean `157.230.36.174`

## Project Location

Current Laravel app path:

`C:\Users\DELL\projects\medisSHAMS\medisSHAMS-app`

## Existing Database

Your SQL dump has been copied into:

`database/schema/medis.sql`

The schema already includes modules such as:

- users
- company
- employee
- doctor
- declaration
- medical_history
- physical_examination
- audiometry_test
- annual_audiograph
- baseline_audiograph
- fitness_report
- summary_report

## Local Setup

1. Open the project folder:
   `C:\Users\DELL\projects\medisSHAMS\medisSHAMS-app`
2. Update `.env` with your MySQL credentials.
3. Create the MySQL database:
   `medis`
4. Import the SQL dump:
   `database/schema/medis.sql`
5. Start the Laravel server:
   `php artisan serve`

## Suggested `.env` Database Settings

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=medis
DB_USERNAME=root
DB_PASSWORD=
```

## Useful Commands

```bash
composer install
php artisan key:generate
php artisan serve
npm install
npm run dev
```

## DigitalOcean Deployment Outline

1. Provision PHP, Composer, MySQL client, Node.js, and Nginx on `157.230.36.174`.
2. Upload the Laravel project to the droplet.
3. Set the web root to the Laravel `public/` directory.
4. Configure production `.env` values.
5. Import `database/schema/medis.sql` into the production MySQL server.
6. Run:
   `composer install --optimize-autoloader --no-dev`
7. Run:
   `php artisan config:cache`
8. Run:
   `php artisan route:cache`

## Recommended Development Order

1. Authentication and roles
2. Company and employee management
3. Medical examination workflow
4. Audiometry workflow
5. Reporting and printable forms

## Next Build Targets

- Create Eloquent models for the core tables
- Build admin CRUD for companies, employees, and doctors
- Add login and role-based permissions
- Create Blade pages for medical forms and reports
- Prepare production deployment on DigitalOcean
