<?php

namespace Grasmash\ComposerScaffold;

use Composer\IO\IOInterface;
use Grasmash\ComposerScaffold\Operations\OperationInterface;
use Grasmash\ComposerScaffold\ScaffoldFilePath;

/**
 * Per-project options from the 'extras' section of the composer.json file.
 *
 * Projects that describe scaffold files do so via their scaffold options.
 * This data is pulled from the 'composer-scaffold' portion of the extras
 * section of the project data.
 */
class ScaffoldOptions {
  protected $options = [];

  /**
   * ScaffoldOptions constructor.
   *
   * @param array $options
   *   The scaffold options taken from the 'composer-scaffold' section.
   */
  protected function __construct(array $options) {
    $this->options = $options + ["allowed-packages" => [], "locations" => [], "symlink" => FALSE, "overwrite" => TRUE, "file-mapping" => []];
  }

  /**
   * Determine if the provided 'extras' section has scaffold options.
   *
   * @param array $extras
   *   The contents of the 'extras' section.
   *
   * @return bool
   *   True if scaffold options have been declared
   */
  public static function hasOptions(array $extras) {
    return array_key_exists('composer-scaffold', $extras);
  }

  /**
   * Create a scaffold options object.
   *
   * @param array $extras
   *   The contents of the 'extras' section.
   *
   * @return self
   *   The scaffold options object representing the provided scaffold options
   */
  public static function create(array $extras) {
    $options = static::hasOptions($extras) ? $extras['composer-scaffold'] : [];
    return new self($options);
  }

  /**
   * Create a scaffold option object with default values.
   *
   * @return self
   *   A scaffold options object with default values
   */
  public static function defaultOptions() {
    return new self([]);
  }

  /**
   * Create a new scaffold options object with some values overridden.
   *
   * @param array $options
   *   Override values.
   *
   * @return self
   *   The scaffold options object representing the provided scaffold options
   */
  protected function override(array $options) {
    return new self($options + $this->options);
  }

  /**
   * Create a new scaffold options object with a new value in the 'symlink' variable.
   *
   * @return self
   *   The scaffold options object representing the provided scaffold options
   */
  public function overrideSymlink($symlink) {
    return $this->override(['symlink' => $symlink]);
  }

  /**
   * Determine whether any allowed packages were defined.
   *
   * @return bool
   *   Whether there are allowed packages
   */
  public function hasAllowedPackages() {
    return !empty($this->allowedPackages());
  }

  /**
   * The allowed packages from these options.
   *
   * @return array
   *   The list of allowed packages
   */
  public function allowedPackages() {
    return $this->options['allowed-packages'];
  }

  /**
   * The location mapping table, e.g. 'webroot' => './'.
   *
   * @return array
   *   A map of name : location values
   */
  public function locations() {
    return $this->options['locations'];
  }

  /**
   * Determine whether a given named location is defined.
   *
   * @return bool
   *   True if the specified named location exist.
   */
  public function hasLocation($name) {
    return array_key_exists($name, $this->locations());
  }

  /**
   * Get a specific named location.
   *
   * @param string $name
   *   The name of the location to fetch.
   * @param string $default
   *   The value to return if the requested location is not defined.
   *
   * @return string
   *   The value of the provided named location
   */
  public function getLocation($name, $default = '') {
    return $this->hasLocation($name) ? $this->locations()[$name] : $default;
  }

  /**
   * Return the value of a specific named location, or throw.
   *
   * @param string $name
   *   The name of the location to fetch.
   * @param string $message
   *   The message to pass into the exception if the requested location
   *   does not exist.
   *
   * @return string
   *   The value of the provided named location
   */
  public function requiredLocation($name, $message) {
    if (!$this->hasLocation($name)) {
      throw new \Exception($message);
    }
    return $this->getLocation($name);
  }

  /**
   * Determine whether the options have defined symlink mode.
   *
   * @return bool
   *   Whether or not 'symlink' mode
   */
  public function symlink() {
    return $this->options['symlink'];
  }

  /**
   * Determine whether these options contain file mappings.
   *
   * @return bool
   *   Whether or not the scaffold options contain any file mappings
   */
  public function hasFileMapping() {
    return !empty($this->fileMapping());
  }

  /**
   * Return the actual file mappings.
   *
   * @return array
   *   File mappings for just this config type.
   */
  public function fileMapping() {
    return $this->options['file-mapping'];
  }

  /**
   * Whether the options have the global overwrite preference.
   *
   * @return bool
   *   The global the overwrite option
   */
  public function overwrite() {
    return $this->options['overwrite'];
  }

  /**
   * Whether the scaffold options have defined a value for the 'gitignore' option.
   *
   * @return bool
   *   Whether or not there is a 'gitignore' option setting
   */
  public function hasGitIgnore() {
    return isset($this->options['gitignore']);
  }

  /**
   * The value of the 'gitignore' option.
   *
   * @return bool
   *   The 'gitignore' option, or TRUE if undefined.
   */
  public function gitIgnore() {
    return $this->hasGitIgnore() ? $this->options['gitignore'] : TRUE;
  }

}
