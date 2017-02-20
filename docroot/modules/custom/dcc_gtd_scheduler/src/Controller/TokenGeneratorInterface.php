<?php

namespace Drupal\dcc_gtd_scheduler\Controller;

/**
 * Interface TokenGeneratorInterface.
 *
 * @package Drupal\dcc_gtd_scheduler\Controller
 */
interface TokenGeneratorInterface {

  /**
   * Interface to be implemented by all services that provide custom tokens.
   *
   * @param string $type
   *    The machine-readable name of the type (group) of token being replaced.
   * @param array $tokens
   *    An array of tokens to be replaced. The keys are the machine-readable token names, and the values are the raw [type:token] strings that appeared in the original text.
   *
   * @return array
   *    An associative array of replacement values, keyed by the raw [type:token] strings from the original text.
   */
  public function generateToken($type, array $tokens);

  /**
   * Returns the token information to be inserted in token list.
   *
   * @return array
   *   An associative array of available tokens and token types. The outer array has two components: types and tokens
   */
  public function getTokenInfo();

}
