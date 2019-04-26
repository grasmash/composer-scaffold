<?php

namespace Grasmash\ComposerScaffold\Tests;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\BufferIO;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use Grasmash\ComposerScaffold\Handler;
use Grasmash\ComposerScaffold\Interpolator;
use Grasmash\ComposerScaffold\Operations\AppendOp;
use Grasmash\ComposerScaffold\Operations\ReplaceOp;
use Grasmash\ComposerScaffold\ScaffoldFilePath;
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

  protected $io;
  protected $composer;

  /**
   * Get an IO fixture.
   *
   * @return \Composer\IO\IOInterface
   *   A Composer IOInterface to write to; output may be retrieved via Fixtures::getOutput()
   */
  public function io() : IOInterface {
    if (!$this->io) {
      $this->io = new BufferIO();
    }
    return $this->io;
  }

  /**
   * Get the Composer object.
   *
   * @return \Composer\Composer
   *   The main Composer object, needed by the scaffold Handler, etc.
   */
  public function getComposer() : Composer {
    if (!$this->composer) {
      $this->composer = Factory::create($this->io(), NULL, TRUE);
    }
    return $this->composer;
  }

  /**
   * Get the output from our io() fixture.
   *
   * @return string
   *   Output captured from tests that write to Fixtures::io().
   */
  public function getOutput() {
    return $this->io()->getOutput();
  }

  /**
   * Return the path to this project so that it may be injected into composer.json files.
   *
   * @return string
   *   Path to the root of this project.
   */
  public function projectRoot() {
    return realpath(__DIR__ . '/../..');
  }

  /**
   * Return the path to the project fixtures.
   *
   * @return string
   *   Path to project fixtures
   */
  public function allFixturesDir() {
    return realpath(__DIR__ . '/../fixtures');
  }

  /**
   * Return the path to one particular project fixture.
   *
   * @return string
   *   Path to project fixture
   */
  public function projectFixtureDir($project_name) {
    $dir = $this->allFixturesDir() . '/' . $project_name;

    if (!is_dir($dir)) {
      throw new \Exception("Requested fixture project $project_name that does not exist.");
    }

    return $dir;
  }

  /**
   * Use in place of ScaffoldFilePath::sourcePath to get a path to a source scaffold fixture.
   *
   * @param string $project_name
   *   The name of the project to fetch; $package_name is "fixtures/$project_name".
   * @param string $source
   *   The name of the asset; path is "assets/$source".
   * @param string $destination
   *   The path to the destination; only used in error messages, not needed for most tests.
   *
   * @return \Grasmash\ComposerScaffold\ScaffoldFilePath
   *   The full and relative path to the desired asset
   */
  public function sourcePath(string $project_name, string $source, string $destination = 'unknown') : ScaffoldFilePath {
    $package_name = "fixtures/$project_name";
    $source_rel_path = "assets/$source";
    $package_path = $this->projectFixtureDir($project_name);
    $destination = 'unknown';

    return ScaffoldFilePath::sourcePath($package_name, $package_path, $destination, $source_rel_path);
  }

  /**
   * Use in place of Handler::getLocationReplacements() to obtain a 'web-root'.
   *
   * @return \Grasmash\ComposerScaffold\Interpolator
   *   An interpolator with location replacements, including 'web-root'.
   */
  public function getLocationReplacements() : Interpolator {
    $destinationTmpDir = $this->mkTmpDir();
    $interpolator = new Interpolator();
    $interpolator->setData([
      'web-root' => $destinationTmpDir,
      'package-name' => 'fixtures/tmp-destination',
    ]);

    return $interpolator;
  }

  /**
   * Use to create a ReplaceOp fixture.
   *
   * @param string $project_name
   *   The name of the project to fetch; $package_name is "fixtures/$project_name".
   * @param string $source
   *   The name of the asset; path is "assets/$source".
   *
   * @return \Grasmash\ComposerScaffold\Operations\ReplaceOp
   *   A replace operation object.
   */
  public function replaceOp(string $project_name, string $source) : ReplaceOp {
    $source_path = $this->sourcePath($project_name, $source);
    return (new ReplaceOp())->setSource($source_path);
  }

  /**
   * Use to create an AppendOp fixture.
   *
   * @param string $project_name
   *   The name of the project to fetch; $package_name is "fixtures/$project_name".
   * @param string $source
   *   The name of the asset; path is "assets/$source".
   *
   * @return \Grasmash\ComposerScaffold\Operations\AppendOp
   *   An append opperation object.
   */
  public function appendOp(string $project_name, string $source) : AppendOp {
    $source_path = $this->sourcePath($project_name, $source);
    return (new AppendOp())->setAppendFile($source_path);
  }

  /**
   * Use in place of ScaffoldFilePath::destinationPath to get a destination path in a tmp dir.
   *
   * @param string $destination
   *   Destination path; should be in the form '[web-root]/robots.txt', where
   *   '[web-root]' is always literally '[web-root]', with any arbitrarily
   *   desired filename following.
   * @param \Grasmash\ComposerScaffold\Interpolator $interpolator
   *   Location replacements. Obtain via Fixtures::getLocationReplacements()
   *   when creating multiple scaffold destinations.
   * @param string $package_name
   *   The name of the fixture package that this path came from. Optional;
   *   taken from interpolator if not provided.
   *
   * @return \Grasmash\ComposerScaffold\ScaffoldFilePath
   *   A destination scaffold file backed by temporary storage.
   */
  public function destinationPath(string $destination, Interpolator $interpolator = NULL, string $package_name = NULL) {
    $interpolator = $interpolator ?: $this->getLocationReplacements();
    $package_name = $package_name ?: $interpolator->interpolate('[package-name]');

    return ScaffoldFilePath::destinationPath($package_name, $destination, $interpolator);
  }

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
    $this->io = NULL;
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

    $filesystem->copy($this->allFixturesDir(), $fixturesDir);

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

  /**
   * Run the scaffold operation.
   *
   * This is equivalent to running `composer composer-scaffold`, but we
   * do the equivalent operation by instantiating a Handler object in order
   * to continue running in the same process, so that coverage may be
   * calculated for the code executed by these tests.
   */
  public function runScaffold($sut) {
    chdir($sut);
    $handler = new Handler($this->getComposer(), $this->io());
    $handler->scaffold();
    return $this->getOutput();
  }

}
