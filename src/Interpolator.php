<?php

namespace Grasmash\ComposerScaffold;

/**
 * Inject config values from an associative array into a string.
 */
class Interpolator {
  protected $startToken;
  protected $endToken;
  protected $data;

  /**
   * Interpolator constructor.
   *
   * @param string $startToken
   *   The start marker for a token, e.g. '['.
   * @param string $endToken
   *   The end marker for a token, e.g. ']'.
   */
  public function __construct($startToken = '\\[', $endToken = '\\]') {
    $this->startToken = $startToken;
    $this->endToken = $endToken;
    $this->data = [];
  }

  /**
   * SetData allows the client to associate a standard data set to use when interpolating.
   *
   * @param array $data
   *   Interpolation data to use when interpolating.
   */
  public function setData(array $data) {
    $this->data = $data;
    return $this;
  }

  /**
   * Add data allows the client to add to the standard data set to use when interpolating.
   *
   * @param array $data
   *   Interpolation data to use when interpolating.
   */
  public function addData(array $data) {
    $this->data = array_merge($this->data, $data);
    return $this;
  }

  /**
   * Interpolate replaces tokens in a string with values from an associative array.
   *
   * Tokens are surrounded by double curley braces, e.g. "[key]". The characters
   * that surround the key may be defined when the Interpolator is constructed.
   *
   * Example:
   * If the message is 'Hello, [user.name]', then the value of the user.name
   * item is fetched from the array, and the token [user.name] is
   * replaced with the result.
   *
   * @param string $message
   *   Message containing tokens to be replaced.
   * @param mixed|array $extra
   *   Data to use for interpolation in addition to whatever was provided by self::setData().
   * @param string|bool $default
   *   The value to substitute for tokens that
   *   are not found in the data. If `false`, then missing
   *   tokens are not replaced.
   *
   * @return string
   *   The message after replacements have been made.
   */
  public function interpolate($message, $extra = [], $default = '') {
    $data = $extra + $this->data;
    $replacements = $this->replacements($message, $data, $default);
    return strtr($message, $replacements);
  }

  /**
   * FindTokens finds all of the tokens in the provided message.
   *
   * @param string $message
   *   String with tokens.
   *
   * @return string[]
   *   map of token to key, e.g. {{key}} => key
   */
  public function findTokens($message) {
    $regEx = '#' . $this->startToken . '([a-zA-Z0-9._-]+)' . $this->endToken . '#';
    if (!preg_match_all($regEx, $message, $matches, PREG_SET_ORDER)) {
      return [];
    }
    $tokens = [];
    foreach ($matches as $matchSet) {
      list($sourceText, $key) = $matchSet;
      $tokens[$sourceText] = $key;
    }
    return $tokens;
  }

  /**
   * Replacements finds the tokens that exist in a message and builds a replacement array.
   *
   * All of the replacements in the data array are looked up given the token
   * keys from the provided message. Keys that do not exist in the configuration
   * are replaced with the default value.
   */
  protected function replacements($message, $data, $default = '') {
    $tokens = $this->findTokens($message);
    $replacements = [];
    foreach ($tokens as $sourceText => $key) {
      $replacementText = $this->get($key, $data, $default);
      if ($replacementText !== FALSE) {
        $replacements[$sourceText] = $replacementText;
      }
    }
    return $replacements;
  }

  /**
   * Get a value from an array.
   */
  protected function get($key, $data, $default) {
    return array_key_exists($key, $data) ? $data[$key] : $default;
  }

}
