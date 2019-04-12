<?php

namespace Grasmash\ComposerScaffold\tests;

use PHPUnit\Framework\TestCase;
use Composer\Util\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Tests Composer Scaffold.
 */
class ScaffoldTest extends TestCase {

  protected $fixtures;

  /**
   * The file path to the system under test.
   *
   * @var string
   */
  protected $sut;

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

    $projectRoot = dirname(__DIR__);
    $this->fixtures = dirname($projectRoot) . '/composer-scaffold-test';
  }

  /**
   * Create the System-Under-Test.
   */
  protected function createSut($topLevelProjectDir) {
    $projectRoot = dirname(__DIR__);
    $this->sut = $this->fixtures . '/' . $topLevelProjectDir;

    $this->removeSut();
    $this->fileSystem->copy($projectRoot . '/tests/fixtures', $this->fixtures);

    return $this->sut;
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    // For now, leave the SUT in place so that we can inspect it.
    // $this->removeSut();
  }

  /**
   * Removes the system under test.
   */
  protected function removeSut() {
    $this->fileSystem->remove($this->fixtures);
  }

  /**
   * Data provider for testComposerInstallScaffold and testScaffoldCommand.
   */
  public function scaffoldTestValues() {
    return [
      [
        'drupal-composer-drupal-project',
        'assertDrupalProjectSutWasScaffolded',
        TRUE,
      ],
      [
        'drupal-drupal',
        'assertDrupalDrupalSutWasScaffolded',
        FALSE,
      ],
    ];
  }

  /**
   * Tests that scaffold files are correctly moved.
   *
   * @dataProvider scaffoldTestValues
   */
  public function testScaffold($topLevelProjectDir, $scaffoldAssertions, $is_link) {
    $sut = $this->createSut($topLevelProjectDir);

    // Test composer install.
    $this->runComposer("install");
    call_user_func([$this, $scaffoldAssertions], $sut, $is_link, $topLevelProjectDir);

    // Test composer:scaffold.
    $this->runComposer("composer:scaffold");
    call_user_func([$this, $scaffoldAssertions], $sut, $is_link, $topLevelProjectDir);
  }

  /**
   * Runs a `composer` command.
   */
  protected function runComposer($cmd) {
    $process = new Process("composer $cmd", $this->sut);
    $process->setTimeout(300)->setIdleTimeout(300)->mustRun();
    $this->assertSame(0, $process->getExitCode());
  }

  /**
   * Asserts that scaffold files were correctly moved.
   */
  protected function assertDrupalProjectSutWasScaffolded($sut, $is_link, $project_name) {
    $this->assertDrupalRootWasScaffolded($sut . '/docroot', $is_link, $project_name);
  }

  /**
   * Asserts that scaffold files were correctly moved.
   */
  protected function assertDrupalDrupalSutWasScaffolded($sut, $is_link, $project_name) {
    $this->assertDrupalRootWasScaffolded($sut, $is_link, $project_name);
  }

  /**
   * Assert that the scaffold files are placed as we expect them to be.
   */
  protected function assertDrupalRootWasScaffolded($docroot, $is_link, $project_name) {
    $from_project = "scaffolded from \"file-mappings\" in $project_name composer.json fixture";
    $from_scaffold_override = 'scaffolded from the scaffold-override-fixture';
    $from_core = 'from drupal/core';

    // Ensure that the autoload.php file was written.
    $this->assertFileExists($docroot . '/autoload.php');

    // Ensure that the .htaccess.txt file was not written, as our
    // top-level composer.json excludes it from the files to scaffold.
    $this->assertFileNotExists($docroot . '/.htaccess');

    // Assert other scaffold files are written in the correct locations.
    $this->assertScaffoldedFile($docroot . '/.csslintrc', $is_link, $from_core);
    $this->assertScaffoldedFile($docroot . '/.editorconfig', $is_link, $from_core);
    $this->assertScaffoldedFile($docroot . '/.eslintignore', $is_link, $from_core);
    $this->assertScaffoldedFile($docroot . '/.eslintrc.json', $is_link, $from_core);
    $this->assertScaffoldedFile($docroot . '/.gitattributes', $is_link, $from_core);
    $this->assertScaffoldedFile($docroot . '/.ht.router.php', $is_link, $from_core);
    $this->assertScaffoldedFile($docroot . '/sites/default/default.services.yml', $is_link, $from_core);
    $this->assertScaffoldedFile($docroot . '/sites/default/default.settings.php', $is_link, $from_scaffold_override);
    $this->assertScaffoldedFile($docroot . '/sites/example.settings.local.php', $is_link, $from_core);
    $this->assertScaffoldedFile($docroot . '/sites/example.sites.php', $is_link, $from_core);
    $this->assertScaffoldedFile($docroot . '/index.php', $is_link, $from_core);
    $this->assertScaffoldedFile($docroot . '/robots.txt', $is_link, $from_project);
    $this->assertScaffoldedFile($docroot . '/update.php', $is_link, $from_core);
    $this->assertScaffoldedFile($docroot . '/web.config', $is_link, $from_core);
  }

  /**
   * Asserts that a given file exists and is/is not a symlink.
   */
  protected function assertScaffoldedFile($path, $is_link, $contents_contains) {
    $this->assertFileExists($path);
    $contents = file_get_contents($path);
    $this->assertContains($contents_contains, basename($path) . ': ' . $contents);
    $this->assertSame($is_link, is_link($path));
  }

}
