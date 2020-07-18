<?php

namespace DrupalS3Backup;

/**
 * Handles backup archive creation.
 */
class ArchiveBuilder
{

    /**
     * The Drupal site's root.
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
     * Fetches the path to the Drupal root and gets the list of sites.
     */
    public function __construct(string $drupalRoot, string $tmp)
    {
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
     *
     * @return string
     *   The path to the archive file.
     */
    public function buildArchive(): string
    {
        foreach ($this->sites as $site) {
            $this->copySiteFiles(
                $site,
                [
                "_default.settings.php_",
                "_simpletest/_",
                "_files/css/_",
                "_files/js/_",
                "_files/php/_",
                "_files/styles_"
                ]
            );
            $this->dumpDatabase($site);
        }

        $timestamp = date("Y-m-d_H:i:s");
        $archive = "{$this->tmp}/drupal_backup_$timestamp.tar.gz";
        exec("cd {$this->tmp} && tar -czf '$archive' 'drupal'");
        exec("rm -r {$this->tmp}/drupal");
        return $archive;
    }

    /**
     * Dumps database into temporary directory.
     *
     * @param string $site
     *   The name of the site's folder e.g. default.
     */
    protected function dumpDatabase(string $site)
    {
        // Get database settings.
        $settings_file = "{$this->drupalRoot}/sites/$site/settings.php";
        // Initialise some fake vars that are referenced in settings.php
        // before including that file.
        $app_root = '';
        $site_path = '';
        include $settings_file;
        $db = $databases['default']['default'];

        // Dump the database to file.
        $data_tmp_dir = $this->tmp . "drupal/$site/data";
        if (!is_dir($data_tmp_dir)) {
            mkdir($data_tmp_dir, 0755, true);
        }
        putenv("MYSQL_PWD=${db['password']}");
        $command = "mysqldump --user='{$db['username']}' '{$db['database']}'";
        exec("$command > '$data_tmp_dir/drupal.sql'");
    }

    /**
     * Copies files into temporary directory.
     *
     * @param string $site
     *   The name of the site's folder e.g. default.
     * @param array  $exclude_patterns
     *   A list of regexp patterns that match files to be excluded from the backup.
     */
    protected function copySiteFiles(string $site, array $exclude_patterns = [])
    {
        $dirname = "{$this->drupalRoot}/sites/$site";
        $dir = new \RecursiveDirectoryIterator($dirname);

        $filter = new \RecursiveCallbackFilterIterator(
            $dir,
            function ($current, $key, $iterator) {
                // Skip hidden files and directories.
                $filename = $current->getFilename();
                if ($filename == '.' || $filename == '..') {
                    return false;
                }
                return true;
            }
        );

        $iterator = new \RecursiveIteratorIterator($filter);
        $files = [];
        foreach ($iterator as $fileinfo) {
            $pathname = $fileinfo->getPathname();
            if ($this->includeFile($pathname, $exclude_patterns)) {
                $files[] = $fileinfo;
            }
        }

        foreach ($files as $fileinfo) {
            $pathname = $fileinfo->getPathname();
            $src = $pathname;
            $dest = \str_replace($this->drupalRoot, $this->tmp . 'drupal/web', $pathname);
            $dest_path = \str_replace($this->drupalRoot, $this->tmp . 'drupal/web', $fileinfo->getPath());
            if (!is_dir($dest_path)) {
                mkdir($dest_path, 0755, true);
            }
            copy($src, $dest);
        }
    }

    /**
     * Tests file path against a list of exclude patterns to determine if the
     * file should be included in the backup.
     *
     * @param string
     *   The file's path.
     * @param array
     *   The list of exclude patterns.
     */
    protected function includeFile(string $pathname, array $exclude_patterns)
    {
        foreach ($exclude_patterns as $pattern) {
            if (preg_match($pattern, $pathname)) {
                return false;
            }
        }
        return true;
    }
}
