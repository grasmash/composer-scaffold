<?php

namespace Grasmash\ComposerScaffold\tests;

use Grasmash\ComposerScaffold\Handler;
use PHPUnit\Framework\TestCase;
use Composer\Util\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Tests Composer Scaffold.
 */
class ScaffoldTest extends TestCase {

  protected $fixtures;

  protected $sut;

  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->fileSystem = new Filesystem();

    $projectRoot = dirname(__DIR__);
    $this->fixtures = dirname($projectRoot) . '/composer-scaffold-test';
    $this->sut = $this->fixtures . '/top-level-project';

    $this->removeSut();
    $this->fileSystem->copy($projectRoot . '/tests/fixtures', $this->fixtures);
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
   * Removes scaffold files from the system under test.
   */
  protected function removeScaffoldFiles() {
    $this->fileSystem->remove($this->sut . '/docroot');
  }

  /**
   * Tests that scaffold files are correctly moved.
   */
  public function testScaffold() {
    $default_config = $this->getDefaultComposerScaffoldConfig();
    $this->setSutConfig($default_config);

    // Test composer install.
    $this->runComposer("install");
    $this->assertSutWasScaffolded();

    // Clean up our scaffold files so that we can try it again.
    $this->removeScaffoldFiles();

    // Test composer:scaffold.
    $this->runComposer("composer:scaffold");
    $this->assertSutWasScaffolded();
  }

  /**
   * Tests that scaffolded files are symlinked when symlink is true.
   */
  public function testSymlink() {
    $config = $this->getDefaultComposerScaffoldConfig();
    $config['symlink'] = TRUE;
    $this->setSutConfig($config);
    $this->runComposer('install');
    $this->assertSutWasScaffolded();
    $this->assertScaffoldedFile('docroot/robots.txt', TRUE);
  }

  /**
   * Tests that scaffolded files are not symlinked when symlink is false.
   */
  public function testNoSymlink() {
    $config = $this->getDefaultComposerScaffoldConfig();
    $config['symlink'] = FALSE;
    $this->setSutConfig($config);
    $this->runComposer('install');
    $this->assertSutWasScaffolded();
    $this->assertScaffoldedFile('docroot/robots.txt', FALSE);
  }

  /**
   * Sets extra.composer-scaffold config in the sut's composer.json.
   */
  public function setSutConfig($config) {
    $composer_json = json_decode(file_get_contents($this->sut . '/composer.json'), TRUE);
    $composer_json['extra']['composer-scaffold'] = array_merge($composer_json['extra']['composer-scaffold'], $config);
    $bytes = file_put_contents($this->sut . '/composer.json', json_encode($composer_json, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return (bool) $bytes;
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
  protected function assertSutWasScaffolded() {
    // TODO: Test to see if the contents of these files is as expected.
    $this->assertFileNotExists($this->sut . '/docroot/.htaccess.txt');
    // $this->assertFileExists('docroot/autoload.php');.
    $this->assertScaffoldedFile('docroot/.csslintrc');
    $this->assertScaffoldedFile('docroot/.editorconfig');
    $this->assertScaffoldedFile('docroot/.eslintignore');
    $this->assertScaffoldedFile('docroot/.eslintrc.json');
    $this->assertScaffoldedFile('docroot/.gitattributes');
    $this->assertScaffoldedFile('docroot/.ht.router.php');
    $this->assertScaffoldedFile('docroot/.htaccess');
    $this->assertScaffoldedFile('docroot/sites/default/default.services.yml');
    $this->assertScaffoldedFile('docroot/sites/default/default.settings.php');
    $this->assertScaffoldedFile('docroot/sites/example.settings.local.php');
    $this->assertScaffoldedFile('docroot/sites/example.sites.php');
    $this->assertScaffoldedFile('docroot/index.php');
    $this->assertScaffoldedFile('docroot/robots.txt');
    $this->assertScaffoldedFile('docroot/update.php');
    $this->assertScaffoldedFile('docroot/web.config');
  }

  /**
   * Asserts that a given file exists and is/is not a symlink.
   */
  protected function assertScaffoldedFile($file, $is_link = NULL) {
    $path = $this->sut . '/' . $file;
    $this->assertFileExists($path);

    if (is_bool($is_link)) {
      $this->assertSame($is_link, is_link($path));
    }
  }

  /**
   * Provide default Composer Scaffold configuration for testing.
   */
  protected function getDefaultComposerScaffoldConfig() {
    return [
      "allowed-packages" => [
        "fixtures/drupal-core-fixture",
        "fixtures/scaffold-override-fixture",
      ],
      "locations" => [
        "web-root" => "./docroot",
      ],
      "symlink" => TRUE,
      "file-mapping" => [
        "self" => [
          "assets/.htaccess" => FALSE,
          "assets/robots-default.txt" => "[web-root]/robots.txt",
        ],
      ],
    ];
  }

  /**
   * Tests ArrayManipulator::arrayMergeRecursiveExceptEmpty().
   *
   * @dataProvider providerTestArrayMergeRecursiveDistinct
   */
  public function testArrayMergeRecursiveDistinct(
    $array1,
    $array2,
    $expected_array
  ) {
    $this->assertEquals(Handler::arrayMergeRecursiveDistinct($array1,
      $array2), $expected_array);
  }

  /**
   * Provides values to testArrayMergeRecursiveDistinct().
   *
   * @return array
   *   An array of values to test.
   */
  public function providerTestArrayMergeRecursiveDistinct() {
    return [
      [
        [
          "self" => [
            "assets/.htaccess" => "[web-root]/.htaccess",
            "assets/robots-default.txt" => "[web-root]/robots.txt",
            "assets/index.php" => "[web-root]/index.php",
          ],
        ],
        [
          "self" => [
            "assets/.htaccess" => FALSE,
            "assets/robots-default.txt" => "[web-root]/robots.txt.bak",
          ],
        ],
        [
          "self" => [
            "assets/.htaccess" => FALSE,
            "assets/robots-default.txt" => "[web-root]/robots.txt.bak",
            "assets/index.php" => "[web-root]/index.php",
          ],
        ],
      ],
    ];
  }

}
