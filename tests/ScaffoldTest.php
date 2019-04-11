<?php

namespace Consolidation\SiteProcess;

use PHPUnit\Framework\TestCase;
use Composer\Util\Filesystem;

class ScaffoldTest extends TestCase
{
    protected $fixtures;
    protected $sut;

    /**
     * Set up our System Under Test
     */
    public function setUp()
    {
        $this->fixtures = dirname($this->projectRoot()) . '/composer-scaffold-test';
        $this->sut = $this->fixtures . '/top-level-project';
        $this->createSut();
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
        // Test composer install
        $this->passthru("composer --working-dir={$this->sut} install");
        $this->assertSutWasScaffolded();

        // Clean up our scaffold files so that we can try it again
        $this->removeScaffoldFiles();

        // Test composer:scaffold
        $this->passthru("composer --working-dir={$this->sut} composer:scaffold");
        $this->assertSutWasScaffolded();
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
//        $this->assertFileExists($this->sut . '/docroot/autoload.php');
        $this->assertFileExists($this->sut . '/docroot/index.php');
        $this->assertFileExists($this->sut . '/docroot/robots.txt');
        $this->assertFileExists($this->sut . '/docroot/sites/default/default.services.yml');
        $this->assertFileExists($this->sut . '/docroot/sites/default/default.settings.php');
        $this->assertFileExists($this->sut . '/docroot/sites/default/settings.php');
    }
}
