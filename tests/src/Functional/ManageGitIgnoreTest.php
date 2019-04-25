<?php

namespace Grasmash\ComposerScaffold\Tests\Functional;

use Composer\Util\Filesystem;
use Grasmash\ComposerScaffold\Handler;
use Grasmash\ComposerScaffold\Interpolator;
use Grasmash\ComposerScaffold\Tests\Fixtures;
use Grasmash\ComposerScaffold\Tests\AssertUtilsTrait;
use Grasmash\ComposerScaffold\Tests\RunCommandTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * Tests to see whether .gitignore files are correctly managed.
 *
 * The purpose of this test file is to run a scaffold operation and
 * confirm that the files that were scaffolded are added to the
 * repository's .gitignore file.
 */
class ManageGitIgnoreTest extends TestCase {

  use RunCommandTrait;
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
    // $this->fixtures->tearDown();
  }

  /**
   * Test to see if scaffold operation runs at the correct times.
   */
  public function testManageGitIgnore() {
    $topLevelProjectDir = 'drupal-composer-drupal-project';
    $is_link = FALSE;
    $this->fixturesDir = $this->fixtures->tmpDir($this->getName());
    $sut = $this->fixturesDir . '/' . $topLevelProjectDir;

    $replacements = [
      'SYMLINK' => $is_link ? 'true' : 'false',
      'PROJECT_ROOT' => $this->projectRoot,
    ];
    $this->fixtures->cloneFixtureProjects($this->fixturesDir, $replacements);

    // .gitignore files will not be managed unless there is a git repository.
    $this->runCommand('git init', $sut);
    $this->runCommand('git add .', $sut);
    $this->runCommand('git commit -m "Initial commit."', $sut);

    // Run the scaffold command and ensure that scaffold operation ran.
    $this->runComposer("install --no-ansi", $sut);

    $expected = <<<EOT
.csslintrc
.editorconfig
.eslintignore
.eslintrc.json
.gitattributes
.ht.router.php
index.php
robots.txt
update.php
web.config
EOT;

    // At this point we should have a .gitignore file, because although we
    // did not explicitly ask for .gitignore tracking, the vendor directory
    // is not tracked, so the default in that instance is to manage .gitignore files.
    $this->assertScaffoldedFile($sut . '/docroot/.gitignore', FALSE, '#' . $expected . '#msi');
    $this->assertScaffoldedFile($sut . '/docroot/sites/.gitignore', FALSE, '#example.settings.local.php#');
    $this->assertScaffoldedFile($sut . '/docroot/sites/default/.gitignore', FALSE, '#default.services.yml#');

    $expected = <<<EOT
?? docroot/.gitignore
?? docroot/sites/.gitignore
?? docroot/sites/default/.gitignore
EOT;

    // Check to see whether there are any untracked files. We expect that
    // only the .gitignore files themselves should be untracked.
    list($stdout, $stderr) = $this->runCommand('git status --porcelain', $sut);
    $this->assertEquals(trim($expected), trim($stdout));
  }

}
