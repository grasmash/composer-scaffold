<?php

namespace Grasmash\ComposerScaffold\Tests\Functional;

use Composer\Util\Filesystem;
use Grasmash\ComposerScaffold\Handler;
use Grasmash\ComposerScaffold\Interpolator;
use Grasmash\ComposerScaffold\Tests\AssertUtilsTrait;
use Grasmash\ComposerScaffold\Tests\ExecTrait;
use Grasmash\ComposerScaffold\Tests\Fixtures;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * Tests Composer Hooks that run scaffold operations.
 *
 * The purpose of this test file is to exercise all of the different Composer
 * commands that invoke scaffold operations, and ensure that files are scaffolded
 * when they should be.
 *
 * Note that this test file uses `exec` to run Composer for a pure functional
 * test. Other functional test files invoke Composer commands directly via the
 * Composer Application object, in order to get more accurate test coverage
 * information.
 */
class ComposerHookTest extends TestCase {
  use ExecTrait;
  use AssertUtilsTrait;
  /**
   * The root of this project.
   *
   * Used to substitute this project's base directory into composer.json files
   * so Composer can find it.
   *
   * @var string
   */
  protected $projectRoot;
  /**
   * Directory to perform the tests in.
   *
   * @var string
   */
  protected $fixturesDir;
  /**
   * The Symfony FileSystem component.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->fileSystem = new Filesystem();
    $this->fixtures = new Fixtures();
    $this->projectRoot = $this->fixtures->projectRoot();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    // Remove any temporary directories et. al. that were created.
    $this->fixtures->tearDown();
  }

  /**
   * Test to see if scaffold operation runs at the correct times.
   */
  public function testComposerHooks() {
    $this->fixturesDir = $this->fixtures->tmpDir($this->getName());
    $is_link = FALSE;
    $replacements = ['SYMLINK' => $is_link ? 'true' : 'false', 'PROJECT_ROOT' => $this->projectRoot];
    $this->fixtures->cloneFixtureProjects($this->fixturesDir, $replacements);
    $topLevelProjectDir = 'composer-hooks-fixture';
    $sut = $this->fixturesDir . '/' . $topLevelProjectDir;
    // First test: run composer install. This is the same as composer update
    // since there is no lock file. Ensure that scaffold operation ran.
    $this->execComposer("install --no-ansi", $sut);
    $this->assertScaffoldedFile($sut . '/sites/default/default.settings.php', $is_link, '#Test version of default.settings.php from drupal/core#');
    // Run composer required to add in the scaffold-override-fixture. This
    // project is "allowed" in our main fixture project, but not required.
    // We expect that requiring this library should re-scaffold, resulting
    // in a changed default.settings.php file.
    list($stdout, $stderr) = $this->execComposer("require --no-ansi --no-interaction fixtures/scaffold-override-fixture:dev-master", $sut);
    $this->assertScaffoldedFile($sut . '/sites/default/default.settings.php', $is_link, '#scaffolded from the scaffold-override-fixture#');
    // Make sure that the appropriate notice informing us that scaffolding
    // is allowed was printed.
    $this->assertContains('Package fixtures/scaffold-override-fixture has scaffold operations, and is already allowed in the root-level composer.json file.', $stdout);
    // Delete one scaffold file, just for test purposes, then run
    // 'composer update' and see if the scaffold file is replaced.
    @unlink($sut . '/sites/default/default.settings.php');
    $this->execComposer("update --no-ansi", $sut);
    $this->assertScaffoldedFile($sut . '/sites/default/default.settings.php', $is_link, '#scaffolded from the scaffold-override-fixture#');
    // Delete the same test scaffold file again, then run
    // 'composer composer:scaffold' and see if the scaffold file is replaced.
    @unlink($sut . '/sites/default/default.settings.php');
    $this->execComposer("composer:scaffold --no-ansi", $sut);
    $this->assertScaffoldedFile($sut . '/sites/default/default.settings.php', $is_link, '#scaffolded from the scaffold-override-fixture#');
    // Run 'composer create-project' to create a new test project called
    // 'create-project-test', which is a copy of 'fixtures/drupal-drupal'.
    $packages = $this->fixturesDir . '/packages.json';
    $sut = $this->fixturesDir . '/create-project-test';
    $filesystem = new Filesystem();
    $filesystem->remove($sut);
    list($stdout, $stderr) = $this->execComposer("create-project --repository=packages.json fixtures/drupal-drupal {$sut}", $this->fixturesDir, ['COMPOSER_MIRROR_PATH_REPOS' => 1]);
    $this->assertDirectoryExists($sut);
    $this->assertContains('Scaffolding files for fixtures/drupal-drupal', $stdout);
    $this->assertScaffoldedFile($sut . '/index.php', FALSE, '#Test version of index.php from drupal/core#');
    $topLevelProjectDir = 'composer-hooks-nothing-allowed-fixture';
    $sut = $this->fixturesDir . '/' . $topLevelProjectDir;
    // Run composer install on an empty project.
    $this->execComposer("install --no-ansi", $sut);
    // Require a project that is not allowed to scaffold and confirm that we
    // get a warning, and it does not scaffold.
    list($stdout, $stderr) = $this->execComposer("require --no-ansi --no-interaction fixtures/scaffold-override-fixture:dev-master", $sut);
    $this->assertFileNotExists($sut . '/sites/default/default.settings.php');
    $this->assertContains('Package fixtures/scaffold-override-fixture has scaffold operations, but it is not allowed in the root-level composer.json file.', $stdout);
  }

  /**
   * Runs a `composer` command.
   *
   * @param string $cmd
   *   The Composer command to execute (escaped as required)
   * @param string $cwd
   *   The current working directory to run the command from.
   * @param array $env
   *   Environment variables to define for the subprocess.
   *
   * @return array
   *   Standard output and standard error from the command
   */
  protected function execComposer($cmd, $cwd, array $env = []) {
    return $this->mustExec("composer {$cmd}", $cwd, $env);
  }

}
