<?php

namespace Grasmash\ComposerScaffold\Tests;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use Grasmash\ComposerScaffold\Handler;
use Grasmash\ComposerScaffold\Interpolator;
use Grasmash\ComposerScaffold\Tests\Fixtures;
use PHPUnit\Framework\TestCase;

/**
 * Convenience class for creating fixtures.
 */
class Fixtures {

  /**
   * Directories to delete when we are done.
   *
   * @var string[]
   */
  protected $tmpDirs = [];

  /**
   * Generate a path to a temporary location, but do not create the directory.
   *
   * @param string $extraSalt
   *   Extra characters to throw into the md5 to add to name.
   *
   * @return string
   *   Path to temporary directory
   */
  public function tmpDir($extraSalt = '') {
    $tmpDir = sys_get_temp_dir() . '/composer-scaffold-test-' . md5($extraSalt . microtime());
    $this->tmpDirs[] = $tmpDir;

    return $tmpDir;
  }

  /**
   * Create a temporary directory.
   *
   * @param string $extraSalt
   *   Extra characters to throw into the md5 to add to name.
   *
   * @return string
   *   Path to temporary directory
   */
  public function mkTmpDir($extraSalt = '') {
    $tmpDir = $this->tmpDir($extraSalt);
    $filesystem = new Filesystem();
    $filesystem->ensureDirectoryExists($tmpDir);

    return $tmpDir;
  }

  /**
   * Call 'tearDown' in any test that copies fixtures to transient locations.
   */
  public function tearDown() {
    // Remove any temporary directories that were created.
    $filesystem = new Filesystem();
    foreach ($this->tmpDirs as $dir) {
      $filesystem->remove($dir);
    }
    // Clear out variables from the previous pass.
    $this->tmpDirs = [];
    $this->fixturesDir = NULL;
  }

  /**
   * Create a temporary copy of all of the fixtures projects into a temp dir.
   *
   * The fixtures remain dirty if they already exist. Individual tests should
   * first delete any fixture directory that needs to remain pristine. Since
   * all temporary directories are removed in tearDown, this is only an issue
   * when a) the FIXTURE_DIR environment variable has been set, or b) tests
   * are calling cloneFixtureProjects more than once per test method.
   *
   * @param string $fixturesDir
   *   The directory to place fixtures in.
   * @param array $replacements
   *   Key : value mappings for placeholders to replace in composer.json templates.
   */
  public function cloneFixtureProjects(string $fixturesDir, array $replacements = []) {
    $filesystem = new Filesystem();
    $replacements += [
      'SYMLINK' => 'true',
    ];
    $interpolator = new Interpolator('__', '__', TRUE);
    $interpolator->setData($replacements);

    $filesystem->copy(realpath(__DIR__ . '/../fixtures'), $fixturesDir);

    $composer_json_templates = glob($fixturesDir . "/*/composer.json.tmpl");
    foreach ($composer_json_templates as $composer_json_tmpl) {
      // Inject replacements into composer.json.
      if (file_exists($composer_json_tmpl)) {
        $composer_json_contents = file_get_contents($composer_json_tmpl);
        $composer_json_contents = $interpolator->interpolate($composer_json_contents, [], FALSE);
        file_put_contents(dirname($composer_json_tmpl) . "/composer.json", $composer_json_contents);
        @unlink($composer_json_tmpl);
      }
    }
  }

}
