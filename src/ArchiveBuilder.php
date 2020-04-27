<?php

namespace DrupalS3Backup;

/**
 * Handles backup archive creation.
 */
class ArchiveBuilder {

  /**
   * The drupal site's root.
   *
   * @var string
   */
  protected $drupalRoot;

  /**
   * The list of site names.
   *
   * @var array
   */
  protected $sites;

  /**
   * Fetches site information.
   */
  public function __construct(string $drupalRoot, string $tmp) {
    $this->tmp = $tmp;
    $this->drupalRoot = $drupalRoot;
    $sites_file = "$drupalRoot/sites/sites.php";

    $sites = ['default'];
    if (file_exists($sites_file)) {
      include $sites_file;
    }
    $this->sites = array_unique($sites);
  }

  /**
   * Puts site files and database dump in an archive.
   */
  public function buildArchive(): string {
    foreach ($this->sites as $site) {
      $this->copySiteFiles($site, [
        "simpletest/", "files/css/", "files/js/", "files/php/", "files/styles"
      ]);
      $this->dumpDatabase($site);
    }

    $timestamp = date("Y-m-d_H:i:s");
    $archive = "{$this->tmp}/drupal_backup_$timestamp.tar.gz";
    exec("cd {$this->tmp} && tar -czf '$archive' 'drupal'");
    exec("rm -r {$this->tmp}/drupal");
    return $archive;
  }

  /**
   * Creates database dump.
   */
  protected function dumpDatabase($site) {
    // Get database settings.
    $settings_file = "{$this->drupalRoot}/sites/$site/settings.php";
    // Fake vars used in settings.php before including it.
    $app_root = "";
    $site_path = "";
    include $settings_file;
    $db = $databases['default']['default'];

    // Dump the database to file.
    $data_tmp_dir = $this->tmp . "drupal/$site/data";
    if (!is_dir($data_tmp_dir)) {
      mkdir($data_tmp_dir, 0755, TRUE);
    }
    putenv("MYSQL_PWD=${db['password']}");
    $command = "mysqldump --user='{$db['username']}' '{$db['database']}'";
    exec("$command > '$data_tmp_dir/drupal.sql'");
  }

  /**
   * Lists site files.
   */
  protected function copySiteFiles(string $site, array $exclude_patterns) {
    $dirname = "{$this->drupalRoot}/sites/$site";
    $dir = new \RecursiveDirectoryIterator($dirname);

    $filter = new \RecursiveCallbackFilterIterator($dir, function ($current, $key, $iterator) {
      // Skip hidden files and directories.
      $filename = $current->getFilename();
      if ($filename == '.' || $filename == '..') {
        return FALSE;
      }
      return TRUE;
    });

    $iterator = new \RecursiveIteratorIterator($filter);
    $files = [];
    foreach ($iterator as $fileinfo) {
      $pathname = $fileinfo->getPathname();
      if (!preg_match("/simpletest|files\/css|files\/js|files\/php|files\/styles/", $pathname)) {
        $files[] = $fileinfo;
      }
    }

    foreach ($files as $fileinfo) {
      $pathname = $fileinfo->getPathname();
      $src = $pathname;
      $dest = \str_replace($this->drupalRoot, $this->tmp . 'drupal/web', $pathname);
      $dest_path = \str_replace($this->drupalRoot, $this->tmp . 'drupal/web', $fileinfo->getPath());
      if (!is_dir($dest_path)) {
        mkdir($dest_path, 0755, TRUE);
      }
      copy($src, $dest);
    }
  }

}
