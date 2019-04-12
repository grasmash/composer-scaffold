<?php

namespace Grasmash\ComposerScaffold\Tests;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Grasmash\ComposerScaffold\Handler;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Grasmash\ComposerScaffold\Handler
 */
class HandlerTest extends TestCase {

  /**
   * The Composer service.
   *
   * @var \Composer\IO\IOInterface
   */
  protected $composer;

  /**
   * The Composer IO service.
   *
   * @var \Composer\IO\IOInterface
   */
  protected $io;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->composer = $this->prophesize(Composer::class);
    $this->io = $this->prophesize(IOInterface::class);
  }

  /**
   * @covers ::getPackageFileMappings
   */
  public function testGetPackageFileMappingsErrors() {
    // Check missing parameters sets appropriate error.
    $package = $this->prophesize(PackageInterface::class);
    $package->getExtra()->willReturn([]);
    $package->getName()->willReturn('foo/bar');
    $this->io->writeError('The allowed package foo/bar does not provide a file mapping for Composer Scaffold.')->shouldBeCalled();
    $fixture = new Handler($this->composer->reveal(), $this->io->reveal());
    $this->assertEquals([], $fixture->getPackageFileMappings($package->reveal()));

    // With only one of the required parameters.
    $package->getExtra()->willReturn(['composer-scaffold' => []]);
    $this->assertEquals([], $fixture->getPackageFileMappings($package->reveal()));
  }

  /**
   * @covers ::getPackageFileMappings
   */
  public function testGetPackageFileMappings() {
    $expected = [
      'self' => [
        'assets/.htaccess' => FALSE,
        'assets/robots-default.txt' => '[web-root]/robots.txt',
      ],
    ];

    $package = $this->prophesize(PackageInterface::class);
    $package->getExtra()->willReturn(['composer-scaffold' => ['file-mapping' => $expected]]);
    $fixture = new Handler($this->composer->reveal(), $this->io->reveal());
    $this->assertEquals($expected, $fixture->getPackageFileMappings($package->reveal()));
  }

  /**
   * @covers ::getWebRoot
   */
  public function testGetWebRoot() {
    $expected = './build/docroot';
    $extra = [
      'composer-scaffold' => [
        'locations' => [
          'web-root' => $expected,
        ],
      ],
    ];

    $package = $this->prophesize(PackageInterface::class);
    $package->getExtra()->willReturn($extra);

    $this->composer->getPackage()->willReturn($package->reveal());

    $fixture = new Handler($this->composer->reveal(), $this->io->reveal());
    $this->assertEquals($expected, $fixture->getWebRoot());
  }

}
