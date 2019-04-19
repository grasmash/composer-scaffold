<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold\Operations;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Grasmash\ComposerScaffold\ScaffoldFileInfo;

/**
 * Manage the source path to a source file to scaffold.
 *
 * Both the relative and full path to the file is maintained so that the
 * shorter name may be used in progress and error messages, as needed.
 */
class ScaffoldSourcePath {

  protected $packageName;
  protected $sourceRelPath;
  protected $sourceFullPath;

  /**
   * ScaffoldSourcePath constructor.
   *
   * @param string $package_name
   *   The name of the package containing the source file.
   * @param string $source_rel_path
   *   The relative path to the source file.
   * @param string $source_full_path
   *   The full installed path to the source file.
   */
  public function __construct(string $package_name, string $source_rel_path, string $source_full_path) {
    $this->packageName = $package_name;
    $this->sourceRelPath = $source_rel_path;
    $this->sourceFullPath = $source_full_path;
  }

  /**
   * The name of the package this source file was pulled from.
   *
   * @return string
   *   Name of package.
   */
  public function packageName() : string {
    return $this->packageName;
  }

  /**
   * The relative path to the source file (best to use in messages).
   *
   * @return string
   *   Relative path to file.
   */
  public function relativePath() : string {
    return $this->sourceRelPath;
  }

  /**
   * The full path to the source file.
   *
   * @return string
   *   Full path to file.
   */
  public function fullPath() : string {
    return $this->sourceFullPath;
  }

  /**
   * ResolveSourceLocation converts the relative source path into an absolute path.
   *
   * The path returned will be relative to the package installation location.
   *
   * @param string $package_name
   *   The name of the package containing the source file. Only used for error messages.
   * @param string $package_path
   *   The installation path of the package containing the source file.
   * @param string $destination
   *   Destination location provided as a relative path. Only used for error messages.
   * @param string $source
   *   Source location provided as a relative path.
   *
   * @return self
   *   Object wrapping the relative and absolute path to the source file.
   */
  public static function create(string $package_name, string $package_path, string $destination, string $source) {
    // Complain if there is no source path.
    if (empty($source)) {
      throw new \Exception("No scaffold file path given for <info>$destination</info> in package <comment>$package_name</comment>.");
    }

    // Calculate the full path to the source scaffold file.
    $source_full_path = $package_path . '/' . $source;

    if (!file_exists($source_full_path)) {
      throw new \Exception("Scaffold file <info>$source</info> not found in package <comment>$package_name</comment>.");
    }
    if (is_dir($source_full_path)) {
      throw new \Exception("Scaffold file <info>$source</info> in package <comment>$package_name</comment> is a directory; only files may be scaffolded.");
    }

    return new self($package_name, $source, $source_full_path);
  }

}
