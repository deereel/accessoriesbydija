# Maintenance Guide

This document provides instructions for maintaining the Accessories By Dija website.

## Table of Contents

- [Database Backups](#database-backups)
- [Clearing the Cache](#clearing-the-cache)

---

## Database Backups

To create a backup of the database, run the following command from the project root:

```bash
php admin/backup_db.php
```

This will create a new SQL file in the `admin/backups` directory.

---

## Clearing the Cache

To clear the cache, delete all the files in the `cache` directory.