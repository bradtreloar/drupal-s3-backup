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

Set your AWS credentials and bucket name in your site's `.env` file.
```
AWS_ACCESS_KEY_ID="AKIAIOSFODNN7EXAMPLE"
AWS_SECRET_ACCESS_KEY="wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY"
DRUPAL_S3_BACKUP_BUCKET="example-bucket"
```

To do a backup from the command line:
```
cd /path/to/drupal && php -q ./vendor/bin/drupal-s3-backup
```
