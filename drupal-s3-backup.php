<?php

use DrupalS3Backup\ArchiveBuilder;
use Aws\S3\S3Client;
use Dotenv\Dotenv;
use DrupalFinder\DrupalFinder;
use Webmozart\PathUtil\Path;

$cwd = isset($_SERVER['PWD']) && is_dir($_SERVER['PWD'])
    ? $_SERVER['PWD']
    : getcwd();

// Set up autoloader.
$loader = false;
if (file_exists($autoloadFile = __DIR__ . '/vendor/autoload.php')
    || file_exists($autoloadFile = __DIR__ . '/../autoload.php')
    || file_exists($autoloadFile = __DIR__ . '/../../autoload.php')
) {
    $loader = include_once($autoloadFile);
} else {
    throw new \Exception("Could not locate autoload.php. cwd is $cwd; __DIR__ is " . __DIR__);
}

$home = Path::getHomeDirectory();
$tmp = "$home/tmp/";

$drupalFinder = new DrupalFinder();
if ($drupalFinder->locateRoot($cwd)) {
    $drupalRoot = $drupalFinder->getDrupalRoot();
} else {
    echo "Unable to locate Drupal root.";
    exit(1);
}

$dotenv = Dotenv::create("$drupalRoot/..");
$dotenv->load();

$archiver = new ArchiveBuilder($drupalRoot, $tmp);
$archive_filepath = $archiver->buildArchive();

$bucket = getenv("DRUPAL_S3_BACKUP_BUCKET");
$key = basename($archive_filepath);

$s3Client = new S3Client([
    'version' => '2006-03-01',
    'region' => getenv("AWS_DEFAULT_REGION"),
]);

$result = $s3Client->putObject([
    'Bucket' => $bucket,
    'Key' => $key,
    'SourceFile' => $archive_filepath,
]);

exec("rm $archive_filepath");
