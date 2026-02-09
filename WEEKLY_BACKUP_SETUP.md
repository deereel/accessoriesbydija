# Weekly Database Backup Setup Guide

## Step 1: Generate a Secure Cron Key

Open a terminal on your server and run:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

This will output a random 64-character hex string like:
```
a1b2c3d4e5f6... (64 characters total)
```

Copy this key - you'll need it for the next step.

## Step 2: Add Cron Key to Your .env File

Edit your `.env` file (in the project root) and add:

```
CRON_KEY=your-generated-key-here
```

Example:
```
CRON_KEY=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6
```

## Step 3: Set Up Real Cron Job (Recommended)

For guaranteed weekly backups, set up a cron job on your server.

### Using crontab:

1. Open crontab editor:
```bash
crontab -e
```

2. Add this line to run backup every Sunday at 2 AM:
```
0 2 * * 0 curl "https://accessoriesbydija.uk/api/cron.php?key=YOUR_CRON_KEY&action=run-if-needed" >> /var/log/cron_backup.log 2>&1
```

Replace `YOUR_CRON_KEY` with the key you generated in Step 1.

### Alternative Cron Formats:

Every Sunday at 2 AM (recommended):
```
0 2 * * 0
```

Every Monday at 2 AM:
```
0 2 * * 1
```

Every Saturday at 2 AM:
```
0 2 * * 6
```

### Cron Time Format:
```
┌───────────── minute (0 - 59)
│ ┌───────────── hour (0 - 23)
│ │ ┌───────────── day of month (1 - 31)
│ │ │ ┌───────────── month (1 - 12)
│ │ │ │ ┌───────────── day of week (0 - 6) (Sunday = 0)
│ │ │ │ │
│ │ │ │ │
0 2 * * 0
```

### Hosting Control Panels (cPanel, Plesk, etc.):

1. Log into your hosting control panel
2. Look for "Cron Jobs" or "Scheduled Tasks"
3. Create a new cron job with:
   - **Frequency**: Weekly (or custom: 0 2 * * 0)
   - **Command**: 
     ```
     curl "https://accessoriesbydija.uk/api/cron.php?key=YOUR_CRON_KEY&action=run-if-needed"
     ```
   - **Output**: Optional - redirect to log file

## Step 4: Verify It's Working

1. After setting up the cron job, wait for the first execution
2. Check the admin backups directory: `admin/backups/`
3. You should see a new `.sql` backup file with date in filename
4. A `.last_weekly_backup` file will also be created tracking last run time

## How It Works

1. **Real Cron Job** (if set up): Your server calls the API every week at the scheduled time
2. **Pseudo-Cron** (fallback): If no cron job is set, the backup will run when:
   - An admin visits any admin page
   - It's been more than 7 days since the last backup
   - The backup runs silently in the background

## Backup Files Location

All backups are stored in: `admin/backups/`

Filename format: `dija_accessories_backup_YYYY-MM-DD_HH-MM-SS.sql`

## API Endpoints

| Endpoint | Description |
|----------|-------------|
| `?action=run` | Force run backup immediately |
| `?action=check` | Check if backup is needed, return next scheduled time |
| `?action=run-if-needed` | Run only if it's been 7+ days (recommended) |

## Troubleshooting

**Backup not running?**
1. Check if CRON_KEY is set in `.env`
2. Verify file permissions on `admin/backups/` directory (should be writable)
3. Check server error logs

**Cron job not executing?**
1. Verify cron syntax is correct
2. Check if curl is installed on server
3. Test manually: `curl "https://yourdomain.com/api/cron.php?key=YOUR_KEY&action=check"`

**Getting 403 error?**
- The cron key doesn't match. Generate a new key and update both `.env` and cron command
