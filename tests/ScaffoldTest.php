<?php

namespace Consolidation\SiteProcess;

use PHPUnit\Framework\TestCase;
use Composer\Util\Filesystem;
use SebastianBergmann\CodeCoverage\Node\File;

class ScaffoldTest extends TestCase
{
  protected $fixtures;
  protected $sut;
  /** @var  \Symfony\Component\Filesystem\Filesystem */
  protected $fs;

  /**
   * Set up our System Under Test
   */
  public function setUp()
  {
    $this->fixtures = dirname($this->projectRoot()) . '/composer-scaffold-test';
    $this->sut = $this->fixtures . '/top-level-project';
    $this->createSut();
    $this->fs = new \Symfony\Component\Filesystem\Filesystem();
  }

  /**
   * Remove our System Under Test
   */
  public function tearDown()
  {
    // For now, leave the SUT in place so that we can inspect it.
    // $this->removeSut();
  }

  protected function projectRoot()
  {
    return dirname(__DIR__);
  }

  protected function sourceFixtures()
  {
    return $this->projectRoot() . '/tests/fixtures';
  }

  protected function createSut()
  {
    $this->removeSut();
    $fs = new Filesystem();
    $fs->copy($this->sourceFixtures(), $this->fixtures);
  }

  protected function removeSut()
  {
    $fs = new Filesystem();
    $fs->remove($this->fixtures);
  }

  protected function removeScaffoldFiles()
  {
    $fs = new Filesystem();
    $fs->remove($this->sut . '/docroot');
  }

  public function testScaffold()
  {
    $default_config = $this->getDefaultComposerScaffoldConfig();
    $this->setSutConfig($default_config);

    // Test composer install
    $this->passthru("composer --working-dir={$this->sut} install");
    $this->assertSutWasScaffolded();

    // Clean up our scaffold files so that we can try it again
    $this->removeScaffoldFiles();

    // Test composer:scaffold
    $this->passthru("composer --working-dir={$this->sut} composer:scaffold");
    $this->assertSutWasScaffolded();
  }

  public function testSymlink() {
    $config = $this->getDefaultComposerScaffoldConfig();
    $config['symlink'] = true;
    $this->setSutConfig($config);
    $this->passthru("composer --working-dir={$this->sut} install");
    $this->assertSutWasScaffolded();
    $file_to_test = $this->sut . '/docroot/robots.txt';
    $this->assertTrue(is_link($file_to_test), "$file_to_test is not a symlink");
  }

  public function testNoSymlink() {
    $config = $this->getDefaultComposerScaffoldConfig();
    $config['symlink'] = false;
    $this->setSutConfig($config);
    $this->passthru("composer --working-dir={$this->sut} install");
    $this->assertSutWasScaffolded();
    $file_to_test = $this->sut . '/docroot/robots.txt';
    $this->assertFalse(is_link($file_to_test), "$file_to_test is a symlink");
  }

  public function setSutConfig($config) {
    $composer_json = json_decode(file_get_contents($this->sut . '/composer.json'), TRUE);
    $composer_json['extra']['composer-scaffold'] = array_merge($composer_json['extra']['composer-scaffold'], $config);
    $bytes = file_put_contents($this->sut . '/composer.json', json_encode($composer_json, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return (bool) $bytes;
  }

  protected function passthru($cmd)
  {
    passthru($cmd, $status);
    $this->assertEquals(0, $status);
  }

  protected function assertSutWasScaffolded()
  {
    // TODO: Test to see if the contents of these files is as expected.
    $this->assertFileNotExists($this->sut . '/docroot/.htaccess.txt');
    // $this->assertFileExists($this->sut . '/docroot/autoload.php');
    $this->assertFileExists($this->sut . '/docroot/index.php');
    $this->assertFileExists($this->sut . '/docroot/robots.txt');
    $this->assertFileExists($this->sut . '/docroot/sites/default/default.services.yml');
    $this->assertFileExists($this->sut . '/docroot/sites/default/default.settings.php');
    $this->assertFileExists($this->sut . '/docroot/sites/default/settings.php');
  }

  protected function getDefaultComposerScaffoldConfig() {
    return [
      "allowed-packages" => [
        "fixtures/drupal-core-fixture",
        "fixtures/scaffold-override-fixture",
      ],
      "locations" => [
        "web-root" => "./docroot",
      ],
      "symlink" => true,
      "file-mapping" => [
        "self" => [
          "assets/.htaccess" => false,
          "assets/robots-default.txt" => "[web-root]/robots.txt",
        ]
      ]
    ];
  }
}
