# Whatsapp-Bulk-Bot

A lightweight WhatsApp bulk messaging tool with a multi-page dashboard, weekly scheduling, and safe-send controls.

## Features
- Multi-page dashboard with sidebar navigation
- Settings, contacts, campaigns, schedules, queue, safety, templates, and reports
- Weekly automation via `scheduler.php`
- Safer sending via `worker.php` (randomized delays, send window, daily cap, rate-limit backoff)
- CSV upload + manual contact entry

## Requirements
- PHP 8+
- Composer (for Guzzle)

## Setup
1. Install dependencies
   ```bash
   composer install
   ```

2. Start the dashboard
   ```bash
   php -S localhost:8081 -t dashboard
   ```

3. Configure settings in the dashboard (API key, delays, send window, daily limit)

## Automation
To enable weekly automation, run the scheduler every minute (cron or supervisor):
```bash
php scheduler.php
```

Then run the worker (continuous or scheduled):
```bash
php worker.php
```

## Contact CSV Format
```
name,number
Ravi,+919xxxxxxxxx
Priya,+919xxxxxxxxx
```

## Notes
- Use opt-in contacts only and keep limits conservative.
- `send_msg_name.php` and `send_msg_number.php` read API settings from the dashboard or env vars.

## License
Licensed under the Apache License 2.0. See `LICENSE` and `NOTICE`.

