# Masjid App

Web and mobile application for a masjid:

- **Prayer times:** calculated astronomically (13 methods, Hanafi or standard Asr, correct across daylight-saving changes), with iqamah rules and Jumuah sessions
- **Ramadan calendar:** sehri, imsak and iftar times, estimated from Umm al-Qura and confirmed by the masjid after the moon sighting
- **Donations:** pledges from the website or app, cash recorded at the counter, receipts, refunds, and restricted funds such as Zakat
- **Ledger:** every income and expense, with immutable entries, voiding with a reason, attachments, reports and CSV export

| Folder | What |
| --- | --- |
| [backend/](backend) | Laravel 13 · PHP 8.3 · MySQL 8: public website, admin panel, REST API |
| [mobile/](mobile) | Flutter app (Android/iOS) using the API |
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | Architecture, data model, API reference, deployment |

## Run locally

Requires PHP 8.3 (extensions `intl`, `bcmath`, `pdo_mysql`), Composer and MySQL 8.

```bash
cd backend
composer install
cp .env.example .env          # set DB_DATABASE / DB_USERNAME / DB_PASSWORD
php artisan key:generate
php artisan migrate --seed    # demo masjid "Masjid-e-Fatima", Hyderabad
php artisan serve
```

- Public site: http://localhost:8000/m/masjid-e-fatima
- Admin panel: http://localhost:8000/admin/login. The demo accounts are `admin@masjid.test` and `treasurer@masjid.test` (password `password`).
- API: http://localhost:8000/api/v1/masjids/masjid-e-fatima/prayer-times

To create a real masjid instead of the demo, run `php artisan masjid:create`.

Run the tests with `php artisan test`.

For the mobile app, see [mobile/README.md](mobile/README.md).
