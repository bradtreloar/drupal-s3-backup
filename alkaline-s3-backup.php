<?php

use Alkaline\ArchiveBuilder;
use Aws\S3\S3Client;
use Dotenv\Dotenv;
use DrupalFinder\DrupalFinder;
use Webmozart\PathUtil\Path;


require_once __DIR__ . '/vendor/autoload.php';

$cwd = isset($_SERVER['PWD']) && is_dir($_SERVER['PWD'])
  ? $_SERVER['PWD']
  : getcwd();

$home = Path::getHomeDirectory();
$tmp = "$home/tmp/";

$drupalFinder = new DrupalFinder();
if ($drupalFinder->locateRoot($cwd)) {
  $drupalRoot = $drupalFinder->getDrupalRoot();
}
else {
  echo "Unable to locate Drupal root.";
  exit(1);
}

$dotenv = Dotenv::create("$drupalRoot/..");
$dotenv->load();

$archiver = new ArchiveBuilder($drupalRoot, $tmp);
$archive_filepath = $archiver->buildArchive();

$bucket = getenv("ALKALINE_S3_BACKUP_BUCKET");
$key = basename($archive_filepath);

$s3Client = new S3Client([
  'profile' => 'default',
  'version' => 'latest',
  'region' => 'ap-southeast-2',
]);

$result = $s3Client->putObject([
  'Bucket' => $bucket,
  'Key' => $key,
  'SourceFile' => $archive_filepath,
]);
