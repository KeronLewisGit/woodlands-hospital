# Woodlands Hospital

Website, secure patient results portal and staff dashboard for Woodlands Hospital Limited, 110–111 Carmichael Street, Georgetown, Guyana.

Laravel 13 · PHP 8.3 · Tailwind CSS v4 · Alpine.js. Runs on Hostinger shared hosting (no Node, no queue worker).

## What's in it

**For patients**
- 24/7 emergency and ambulance information on every page, with a one-tap ambulance call on phones
- Services and departments, specialist clinic timetable, doctor directory (both managed by staff)
- Appointment requests for clinics, specialists, radiology and the laboratory, with a reference number and email confirmation
- Pharmacy refill requests (online form or WhatsApp)
- **Secure results portal**: lab and radiology PDFs, visible only to the patient they belong to; accounts by staff invitation only
- Careers with online applications and CV upload

**For staff** (`/admin`, three roles)
- Front desk: confirm or reschedule appointment requests (the patient is emailed), refills, messages, clinic timetable, portal invitations
- Lab & radiology: publish PDF reports to a patient's portal, see which reports haven't been opened
- Admin: everything, plus doctors, jobs, applications, phone numbers/extensions/hours and staff accounts

## Local setup

```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seeder=DemoSeeder
npm install && npm run build
php artisan serve --port=8101
```

Demo sign-ins (password `password`): `admin@woodlands.test`, `frontdesk@woodlands.test`, `lab@woodlands.test` at `/login`; patient `patient@woodlands.test` at `/results/sign-in`.

## Checks

```bash
php artisan test
vendor/bin/phpstan analyse
vendor/bin/pint --test
```

## Deploying

See [INSTALL-HOSTINGER.md](INSTALL-HOSTINGER.md). `scripts/package-hostinger.sh` builds `dist/woodlands-hostinger.zip`.

## Content sources

Hospital facts come from the hospital's own published pages (woodlandshospital.info, woodlandshospital.com) and cited news reports; see `config/woodlands.php`. Photo licences are listed on `/photo-credits`.
