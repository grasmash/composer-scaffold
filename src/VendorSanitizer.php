<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold;

use Symfony\Component\Process\Process;
use Composer\Util\Filesystem;

/**
 * Manage the .gitignore file.
 */
class VendorSanitizer {

  protected $saniziations;
  protected $vendor;

  /**
   * VendorSanitizer constructor.
   *
   * @param string $vendor
   *   Path to vendor directory.
   * @param string[] $saniziations
   *   A set of directories to sanitize.
   */
  public function __construct(string $vendor, array $saniziations) {
    $this->vendor = $vendor;
    $this->saniziations = $saniziations;
  }

  /**
   * Remove directories to be sanitized.
   *
   * At the top-level, we have a list keyed by the project names that define
   * sanitizations, e.g. 'drupal/assets'.
   *
   * Each one of these entries contains a list keyed by the name of the projects
   * in the vendor directory to be sanitized. Each project to be sanitized
   * contains a list of directories to remove.
   */
  public function sanitize() {
    $fs = new FileSystem();
    foreach ($this->saniziations as $defining_project => $vendor_projects) {
      foreach ($vendor_projects as $project => $dirs) {
        $base = $this->baseDir($project);
        foreach ($dirs as $dir) {
          $fs->remove("$base/$dir");
        }
      }
    }
  }

  /**
   * Calculate the directory that the specified project is installed to.
   *
   * Assumes that the project was not relocated via composer/installers.
   *
   * @param string $project
   *   Name of project to find, stipulated as "org/project".
   */
  protected function baseDir(string $project) {
    return $this->vendor . '/' . $project;
  }

}
