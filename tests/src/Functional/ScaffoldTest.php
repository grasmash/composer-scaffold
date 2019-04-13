<?php

namespace Grasmash\ComposerScaffold\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Composer\Util\Filesystem;
use Symfony\Component\Process\Process;
use Grasmash\ComposerScaffold\Interpolator;

/**
 * Tests Composer Scaffold.
 */
class ScaffoldTest extends TestCase {

  /**
   * The root of this project.
   *
   * @var string
   */
  protected $projectRoot;

  /**
   * Directory to perform the tests in.
   *
   * @var string
   */
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

    $this->projectRoot = realpath(__DIR__ . '/../../..');
    $this->fixtures = sys_get_temp_dir() . '/composer-scaffold-test-' . md5($this->getName() . microtime());
  }

  /**
   * Create the System-Under-Test.
   */
  protected function createSut($topLevelProjectDir, $replacements = []) {
/*    $replacements += [
      'SYMLINK' => 'true',
    ];*/
    $interpolator = new Interpolator('__', '__', TRUE);
    $interpolator->setData($replacements);
    $projectRoot = dirname(__DIR__);
    $this->sut = $this->fixtures . '/' . $topLevelProjectDir;

    $this->fileSystem->copy(realpath(__DIR__ . '/../../fixtures'), $this->fixtures);

    $composer_json_templates = glob($this->fixtures . "/*/composer.json.tmpl");
    foreach ($composer_json_templates as $composer_json_tmpl) {
      // Inject replacements into composer.json.
      if (file_exists($composer_json_tmpl)) {
        $composer_json_contents = file_get_contents($composer_json_tmpl);
        $composer_json_contents = $interpolator->interpolate($composer_json_contents, FALSE);
        file_put_contents(dirname($composer_json_tmpl) . "/composer.json", $composer_json_contents);
        @unlink($composer_json_tmpl);
      }
    }

    return $this->sut;
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    // Remove the fixture filesystem.
    $this->fileSystem->remove($this->fixtures);
  }

  /**
   * Data provider for testComposerInstallScaffold and testScaffoldCommand.
   */
  public function scaffoldFixturesThatThrowTestValues() {
    return [
      [
        'drupal-drupal-missing-scaffold-file',
        '_no_assertion_',
        TRUE,
      ],
    ];
  }

  /**
   * Tests that scaffold files throw when they have bad values.
   *
   * @dataProvider scaffoldFixturesThatThrowTestValues
   */
  public function testScaffoldFixturesThatThrow($topLevelProjectDir, $scaffoldAssertions, $is_link) {
    $sut = $this->createSut($topLevelProjectDir, [
      'SYMLINK' => $is_link ? 'true' : 'false',
      'PROJECT_ROOT' => $this->projectRoot,
    ]);

    // Test composer install. Expect an error.
    // @todo: assert output contains too.
    $this->runComposer("install", 1, 'Could not find source file');
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
   * Tests that scaffold files are correctly moved by the plugin.
   *
   * @dataProvider scaffoldTestValues
   */
  public function testScaffoldPlugin($topLevelProjectDir, $scaffoldAssertions, $is_link) {
    $sut = $this->createSut($topLevelProjectDir, [
      'SYMLINK' => $is_link ? 'true' : 'false',
      'PROJECT_ROOT' => $this->projectRoot,
    ]);

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
  protected function runComposer($cmd, $expectedExitCode = 0, $expectedContents = '') {
    $process = new Process("composer $cmd", $this->sut);
    $process->setTimeout(300)->setIdleTimeout(300)->run();
    if (!empty($expectedContents)) {
      $this->assertContains($expectedContents, $process->getOutput() . "\n" . $process->getErrorOutput());
    }
    $this->assertSame($expectedExitCode, $process->getExitCode(), $process->getErrorOutput());
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

    // Ensure that the .htaccess.txt file was not written, as our
    // top-level composer.json excludes it from the files to scaffold.
    $this->assertFileNotExists($docroot . '/.htaccess');
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
