<?php

namespace Grasmash\ComposerScaffold\Tests\Integration;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Grasmash\ComposerScaffold\Handler;
use Grasmash\ComposerScaffold\Operations\ReplaceOp;
use Grasmash\ComposerScaffold\ScaffoldOptions;
use Grasmash\ComposerScaffold\Tests\Fixtures;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Grasmash\ComposerScaffold\Operations\ReplaceOp
 */
class ReplaceOpTest extends TestCase {

  /**
   * @covers ::process
   */
  public function testProcess() {
    $fixtures = new Fixtures();

    $destination = $fixtures->destinationPath('[web-root]/robots.txt');
    $source = $fixtures->sourcePath('drupal-assets-fixture', 'robots.txt');

    $options = ScaffoldOptions::defaultOptions();

    $sut = new ReplaceOp();
    $sut->setSource($source);
    $sut->setOverwrite(TRUE);

    // Assert that there is no target file before we run our test.
    $this->assertFileNotExists($destination->fullPath());

    // Test the system under test.
    $sut->process($destination, $fixtures->io(), $options);

    // Assert that the target file was created.
    $this->assertFileExists($destination->fullPath());

    // Assert the target contained the contents from the correct scaffold file.
    $contents = trim(file_get_contents($destination->fullPath()));
    $this->assertEquals('# Test version of robots.txt from drupal/core.', $contents);

    // Confirm that expected output was written to our io fixture.
    $output = $fixtures->getOutput();
    $this->assertContains('Copy [web-root]/robots.txt from assets/robots.txt', $output);
  }

}
