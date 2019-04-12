<?php

namespace Grasmash\ComposerScaffold;

/**
 * Inject config values from an associative array into a string.
 */
class Interpolator {
  protected $startToken;
  protected $endToken;

  /**
   * Interpolator constructor.
   *
   * @param string $startToken
   *   The start marker for a token, e.g. '['.
   * @param string $endToken
   *   The end marker for a token, e.g. ']'.
   */
  public function __construct($startToken = '\[', $endToken = '\]') {
    $this->startToken = $startToken;
    $this->endToken = $endToken;
  }

  /**
   * Interpolate replaces tokens in a string with values from an associative array.
   *
   * Tokens are surrounded by double curley braces, e.g. "[key]".
   *
   * Example:
   * If the message is 'Hello, [user.name]', then the value of the user.name
   * item is fetched from the array, and the token [user.name] is
   * replaced with the result.
   *
   * @param mixed|array $data
   *   Data to use for interpolation.
   * @param string $message
   *   Message containing tokens to be replaced.
   * @param string|bool $default
   *   The value to substitute for tokens that
   *   are not found in the data. If `false`, then missing
   *   tokens are not replaced.
   *
   * @return string
   *   The message after replacements have been made.
   */
  public function interpolate($data, $message, $default = '') {
    $replacements = $this->replacements($data, $message, $default);
    return strtr($message, $replacements);
  }

  /**
   * Throw if any tokens remain after interpolation.
   */
  public function mustInterpolate($data, $message) {
    $result = $this->interpolate($data, $message, FALSE);
    $tokens = $this->findTokens($result);
    if (!empty($tokens)) {
      throw new \Exception('The following required keys were not found in configuration: ' . implode(',', $tokens));
    }
    return $result;
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
  public function replacements($data, $message, $default = '') {
    $tokens = $this->findTokens($message);

    $replacements = [];
    foreach ($tokens as $sourceText => $key) {
      $replacementText = $this->get($data, $key, $default);
      if ($replacementText !== FALSE) {
        $replacements[$sourceText] = $replacementText;
      }
    }
    return $replacements;
  }

  /**
   * Get a value from an array. Throw if the type is wrong.
   */
  protected function get($data, $key, $default) {
    if (is_array($data)) {
      return array_key_exists($key, $data) ? $data[$key] : $default;
    }
    throw new \Exception('Bad data type provided to Interpolator');
  }

}
