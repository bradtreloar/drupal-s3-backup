Drupal S3 Backup
================

Simple backup utility that backs up a Drupal website to an AWS S3 bucket.

Requirements:
- Drupal 8+
- PHP 7+
- Drupal site must be using MySQL.

Usage
-----

Install this utility as a composer dependency of your Drupal site.
```
composer require bradtreloar/drupal-s3-backup
```

Set DRUPAL_S3_BACKUP_BUCKET in your site's `.env` file.
```
DRUPAL_S3_BACKUP_BUCKET="example-bucket"
```

To do a backup from the command line:
```
cd /path/to/drupal && php -q ./vendor/bin/drupal-s3-backup
```
