<?php

namespace Grasmash\ComposerScaffold\Tests\Functional;

use Composer\Util\Filesystem;
use Grasmash\ComposerScaffold\Handler;
use Grasmash\ComposerScaffold\Interpolator;
use Grasmash\ComposerScaffold\Tests\Fixtures;
use Grasmash\ComposerScaffold\Tests\AssertUtilsTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * Tests Composer Hooks that run scaffold operations.
 *
 * The purpose of this test file is to exercise all of the different Composer
 * commands that invoke scaffold operations, and ensure that files are scaffolded
 * when they should be.
 */
class ComposerHookTest extends TestCase {

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
    $topLevelProjectDir = 'composer-hooks-fixture';
    $is_link = FALSE;
    $this->fixturesDir = $this->fixtures->tmpDir($this->getName());
    $sut = $this->fixturesDir . '/' . $topLevelProjectDir;

    $replacements = [
      'SYMLINK' => $is_link ? 'true' : 'false',
      'PROJECT_ROOT' => $this->projectRoot,
    ];
    $this->fixtures->cloneFixtureProjects($this->fixturesDir, $replacements);

    // First test: run composer install. This is the same as composer update
    // since there is no lock file. Ensure that scaffold operation ran.
    $this->fixtures->runComposer("install --no-ansi", $sut);
    $this->assertScaffoldedFile($sut . '/sites/default/default.settings.php', $is_link, '#Test version of default.settings.php from drupal/core#');

    // Run composer required to add in the scaffold-override-fixture. This
    // project is "allowed" in our main fixture project, but not required.
    // We expect that requiring this library should re-scaffold, resulting
    // in a changed default.settings.php file.
    $this->fixtures->runComposer("require --no-ansi fixtures/scaffold-override-fixture:dev-master", $sut);
    $this->assertScaffoldedFile($sut . '/sites/default/default.settings.php', $is_link, '#scaffolded from the scaffold-override-fixture#');

    // Delete one scaffold file, just for test purposes, then run
    // 'composer update' and see if the scaffold file is replaced.
    @unlink($sut . '/sites/default/default.settings.php');
    $this->fixtures->runComposer("update --no-ansi", $sut);
    $this->assertScaffoldedFile($sut . '/sites/default/default.settings.php', $is_link, '#scaffolded from the scaffold-override-fixture#');

    // Delete the same test scaffold file again, then run
    // 'composer composer:scaffold' and see if the scaffold file is replaced.
    @unlink($sut . '/sites/default/default.settings.php');
    $this->fixtures->runComposer("composer:scaffold --no-ansi", $sut);
    $this->assertScaffoldedFile($sut . '/sites/default/default.settings.php', $is_link, '#scaffolded from the scaffold-override-fixture#');
  }

}
