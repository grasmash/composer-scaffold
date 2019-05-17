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
 * Tests Composer Scaffold.
 *
 * The purpose of this test file is to exercise all of the different kinds of
 * scaffold operations: copy, symlinks, skips and so on.
 */
class ScaffoldTest extends TestCase {
  use AssertUtilsTrait;
  const FIXTURE_DIR = 'SCAFFOLD_FIXTURE_DIR';
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
    $this->fixtures = new Fixtures();
    $this->projectRoot = $this->fixtures->projectRoot();
    $this->fixturesDir = getenv(self::FIXTURE_DIR);
    if (!$this->fixturesDir) {
      $this->fixturesDir = $this->fixtures->tmpDir($this->getName());
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    // Remove any temporary directories et. al. that were created.
    $this->fixtures->tearDown();
  }

  /**
   * Create the System-Under-Test.
   */
  protected function createSut($topLevelProjectDir, $replacements = []) {
    $this->sut = $this->fixturesDir . '/' . $topLevelProjectDir;
    // Erase just our sut, to ensure it is clean. Recopy all of the fixtures.
    $this->fileSystem->remove($this->sut);
    $replacements += ['PROJECT_ROOT' => $this->projectRoot];
    $this->fixtures->cloneFixtureProjects($this->fixturesDir, $replacements);
    return $this->sut;
  }

  /**
   * Data provider for testComposerInstallScaffold and testScaffoldCommand.
   */
  public function scaffoldFixturesWithErrorConditionsTestValues() {
    return [
      [
        'drupal-drupal-missing-scaffold-file',
        'Scaffold file assets/missing-robots-default.txt not found in package fixtures/drupal-drupal-missing-scaffold-file.',
        TRUE
      ]
    ];
  }

  /**
   * Tests that scaffold files throw when they have bad values.
   *
   * @dataProvider scaffoldFixturesWithErrorConditionsTestValues
   */
  public function testScaffoldFixturesWithErrorConditions($topLevelProjectDir, $expectedExceptionMessage, $is_link) {
    $sut = $this->createSut($topLevelProjectDir, ['SYMLINK' => $is_link ? 'true' : 'false']);
    // Run composer install to get the dependencies we need to test.
    $this->fixtures->runComposer("install --no-ansi --no-scripts", $this->sut);
    // Test scaffold. Expect an error.
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage($expectedExceptionMessage);
    $this->fixtures->runScaffold($sut);
  }

  /**
   * Data provider for testComposerInstallScaffold and testScaffoldCommand.
   */
  public function scaffoldTestValues() {
    return [
      [
        'drupal-composer-drupal-project',
        'assertDrupalProjectSutWasScaffolded', TRUE
      ],
      [
        'drupal-drupal',
        'assertDrupalDrupalSutWasScaffolded',
        FALSE
      ],
      [
        'drupal-drupal-test-overwrite',
        'assertDrupalDrupalFileWasReplaced',
        FALSE
      ],
      [
        'drupal-drupal-test-append',
        'assertDrupalDrupalFileWasAppended',
        FALSE
      ],
      [
        'drupal-drupal-test-append',
        'assertDrupalDrupalFileWasAppended',
        TRUE
      ]
    ];
  }

  /**
   * Tests that scaffold files are correctly moved.
   *
   * @dataProvider scaffoldTestValues
   */
  public function testScaffold($topLevelProjectDir, $scaffoldAssertions, $is_link) {
    $sut = $this->createSut($topLevelProjectDir, ['SYMLINK' => $is_link ? 'true' : 'false']);
    // Run composer install to get the dependencies we need to test.
    $this->fixtures->runComposer("install --no-ansi --no-scripts", $this->sut);
    // Test composer:scaffold.
    $scaffoldOutput = $this->fixtures->runScaffold($sut);
    // @todo We could assert that $scaffoldOutput must contain some expected text
    call_user_func([$this, $scaffoldAssertions], $sut, $is_link, $topLevelProjectDir);
  }

  /**
   * Try to scaffold a project that does not scaffold anything.
   */
  public function testEmptyProject() {
    $topLevelProjectDir = 'empty-fixture';
    $sut = $this->createSut($topLevelProjectDir, ['SYMLINK' => 'false']);
    // Run composer install to get the dependencies we need to test.
    $this->fixtures->runComposer("install --no-ansi --no-scripts", $this->sut);
    // Test composer:scaffold.
    $scaffoldOutput = $this->fixtures->runScaffold($sut);
    $this->assertEquals('', $scaffoldOutput);
  }

  /**
   * Try to scaffold a project that allows a project with no scaffold files.
   */
  public function testProjectThatScaffoldsEmptyProject() {
    $topLevelProjectDir = 'project-allowing-empty-fixture';
    $sut = $this->createSut($topLevelProjectDir, ['SYMLINK' => 'false']);
    // Run composer install to get the dependencies we need to test.
    $this->fixtures->runComposer("install --no-ansi --no-scripts", $this->sut);
    // Test composer:scaffold.
    $scaffoldOutput = $this->fixtures->runScaffold($sut);
    $this->assertContains('The allowed package fixtures/empty-fixture does not provide a file mapping for Composer Scaffold', $scaffoldOutput);
    $docroot = $sut;
    $this->assertCommonDrupalAssetsWereScaffolded($docroot, FALSE, $topLevelProjectDir);
  }

  /**
   * Try to scaffold a project that attempts to scaffold a file with no path.
   */
  public function testProjectWithEmptyScaffoldPath() {
    $topLevelProjectDir = 'project-with-empty-scaffold-path';
    $sut = $this->createSut($topLevelProjectDir, ['SYMLINK' => 'false']);
    // Run composer install to get the dependencies we need to test.
    $this->fixtures->runComposer("install --no-ansi --no-scripts", $this->sut);
    // Test composer:scaffold.
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('No scaffold file path given for [web-root]/my-error in package fixtures/project-with-empty-scaffold-path');
    $this->fixtures->runScaffold($sut);
  }

  /**
   * Try to scaffold a project that attempts to scaffold a directory.
   */
  public function testProjectWithIllegalDirScaffold() {
    $topLevelProjectDir = 'project-with-illegal-dir-scaffold';
    $sut = $this->createSut($topLevelProjectDir, ['SYMLINK' => 'false']);
    // Run composer install to get the dependencies we need to test.
    $this->fixtures->runComposer("install --no-ansi --no-scripts", $this->sut);
    // Test composer:scaffold.
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Scaffold file assets in package fixtures/project-with-illegal-dir-scaffold is a directory; only files may be scaffolded');
    $this->fixtures->runScaffold($sut);
  }

  /**
   * Asserts that the drupal/assets scaffold files correct for drupal/project layout.
   */
  protected function assertDrupalProjectSutWasScaffolded($sut, $is_link, $project_name) {
    $docroot = $sut . '/docroot';
    $this->assertCommonDrupalAssetsWereScaffolded($docroot, $is_link, $project_name);
    $this->assertDefaultSettingsFromScaffoldOverride($docroot, $is_link);
    $this->assertHtaccessExcluded($docroot);
  }

  /**
   * Asserts that the drupal/assets scaffold files correct for drupal/drupal layout.
   */
  protected function assertDrupalDrupalSutWasScaffolded($sut, $is_link, $project_name) {
    $docroot = $sut;
    $this->assertCommonDrupalAssetsWereScaffolded($docroot, $is_link, $project_name);
    $this->assertDefaultSettingsFromScaffoldOverride($docroot, $is_link);
    $this->assertHtaccessExcluded($docroot);
  }

  /**
   * Ensure that the default settings file was overridden by the test.
   */
  protected function assertDefaultSettingsFromScaffoldOverride($docroot, $is_link) {
    $this->assertScaffoldedFile($docroot . '/sites/default/default.settings.php', $is_link, '#scaffolded from the scaffold-override-fixture#');
  }

  /**
   * Ensure that the .htaccess file was excluded by the test.
   */
  protected function assertHtaccessExcluded($docroot) {
    // Ensure that the .htaccess.txt file was not written, as our
    // top-level composer.json excludes it from the files to scaffold.
    $this->assertFileNotExists($docroot . '/.htaccess');
  }

  /**
   * Assert that the appropriate file was replaced.
   *
   * Check the drupal/drupal-based project to confirm that the expected file was
   * replaced, and that files that were not supposed to be replaced remain
   * unchanged.
   */
  protected function assertDrupalDrupalFileWasReplaced($sut, $is_link, $project_name) {
    $docroot = $sut;
    $this->assertScaffoldedFile($docroot . '/replace-me.txt', $is_link, '#from assets that replaces file#');
    $this->assertScaffoldedFile($docroot . '/keep-me.txt', $is_link, '#File in drupal-drupal-test-overwrite that is not replaced#');
    $this->assertScaffoldedFile($docroot . '/make-me.txt', $is_link, '#from assets that replaces file#');
    $this->assertCommonDrupalAssetsWereScaffolded($docroot, $is_link, $project_name);
    $this->assertScaffoldedFile($docroot . '/robots.txt', $is_link, "#{$project_name}#");
  }

  /**
   * Confirm that the robots.txt file was prepended / appended as stipulated in the test.
   */
  protected function assertDrupalDrupalFileWasAppended($sut, $is_link, $project_name) {
    $docroot = $sut;
    $this->assertScaffoldedFile($docroot . '/robots.txt', FALSE, '#in drupal-drupal-test-append composer.json fixture.*This content is prepended to the top of the existing robots.txt fixture.*Test version of robots.txt from drupal/core.*This content is appended to the bottom of the existing robots.txt fixture.*in drupal-drupal-test-append composer.json fixture#ms');
    $this->assertCommonDrupalAssetsWereScaffolded($docroot, $is_link, $project_name);
  }

  /**
   * Assert that the scaffold files from drupal/assets are placed as we expect them to be.
   *
   * This tests that all assets from drupal/assets were scaffolded, save
   * for .htaccess, robots.txt and default.settings.php, which are scaffolded
   * in different ways in different tests.
   */
  protected function assertCommonDrupalAssetsWereScaffolded($docroot, $is_link, $project_name) {
    $from_project = "#scaffolded from \"file-mappings\" in {$project_name} composer.json fixture#";
    $from_core = '#from drupal/core#';
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
    $this->assertScaffoldedFile($docroot . '/sites/example.settings.local.php', $is_link, $from_core);
    $this->assertScaffoldedFile($docroot . '/sites/example.sites.php', $is_link, $from_core);
    $this->assertScaffoldedFile($docroot . '/index.php', $is_link, $from_core);
    $this->assertScaffoldedFile($docroot . '/update.php', $is_link, $from_core);
    $this->assertScaffoldedFile($docroot . '/web.config', $is_link, $from_core);
  }

}
