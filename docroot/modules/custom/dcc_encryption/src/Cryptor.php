<?php

namespace Drupal\dcc_encryption;

/**
 * Cryptor class responsible for encryption.
 *
 * Simple example of using the openssl encrypt/decrypt functions that
 * are inadequately documented in the PHP manual.
 *
 * Available under the MIT License
 *
 * The MIT License (MIT)
 * Copyright (c) 2016 ionCube Ltd.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
class Cryptor {

  /**
   * The cipher alogrithm.
   *
   * @var string
   */
  private $cipherAlgo;

  /**
   * The key hashing algorithm.
   *
   * @var string
   */
  private $hashAlgo;

  /**
   * The length of the cipher iv.
   *
   * @var int
   */
  private $ivNumBytes;

  /**
   * Format of the encryption.
   *
   * @var int
   */
  private $format;

  const FORMAT_RAW = 0;
  const FORMAT_B64 = 1;
  const FORMAT_HEX = 2;

  /**
   * Cryptor constructor.
   *
   * Construct a Cryptor using aes256 encryption, sha256 key hashing and base64
   * encoding.
   *
   * @param string $cipherAlgo
   *   The cipher algorithm.
   * @param string $hashAlgo
   *   Key hashing algorithm.
   * @param int $fmt
   *   Format of the encrypted data.
   *
   * @throws \Exception
   */
  public function __construct($cipherAlgo = 'aes-256-ctr', $hashAlgo = 'sha256', $fmt = Cryptor::FORMAT_B64) {
    $this->cipherAlgo = $cipherAlgo;
    $this->hashAlgo = $hashAlgo;
    $this->format = $fmt;
    if (!in_array($cipherAlgo, openssl_get_cipher_methods(TRUE))) {
      throw new \Exception("Cryptor:: - unknown cipher algo {$cipherAlgo}");
    }
    if (!in_array($hashAlgo, openssl_get_md_methods(TRUE))) {
      throw new \Exception("Cryptor:: - unknown hash algo {$hashAlgo}");
    }
    $this->ivNumBytes = openssl_cipher_iv_length($cipherAlgo);
  }

  /**
   * Encrypt a string.
   *
   * @param string $in
   *   String to encrypt.
   * @param string $key
   *   Encryption key.
   * @param int $fmt
   *   Optional override for the output encoding. One of FORMAT_RAW, FORMAT_B64
   *   or FORMAT_HEX.
   *
   * @return string
   *   The encrypted string.
   *
   * @throws \Exception
   */
  public function encryptString($in, $key, $fmt = NULL) {
    if ($fmt === NULL) {
      $fmt = $this->format;
    }
    // Build an initialisation vector.
    $iv = mcrypt_create_iv($this->ivNumBytes, MCRYPT_DEV_URANDOM);
    // Hash the key.
    $keyhash = openssl_digest($key, $this->hashAlgo, TRUE);
    // And encrypt.
    $opts = OPENSSL_RAW_DATA;
    $encrypted = openssl_encrypt($in, $this->cipherAlgo, $keyhash, $opts, $iv);
    if ($encrypted === FALSE) {
      throw new \Exception('Cryptor::encryptString() - Encryption failed: ' . openssl_error_string());
    }
    // The result comprises the IV and encrypted data.
    $res = $iv . $encrypted;
    // And format the result if required.
    if ($fmt == Cryptor::FORMAT_B64) {
      $res = base64_encode($res);
    }
    else {
      if ($fmt == Cryptor::FORMAT_HEX) {
        $res = unpack('H*', $res)[1];
      }
    }
    return $res;
  }

  /**
   * Decrypt a string.
   *
   * @param string $in
   *   String to decrypt.
   * @param string $key
   *   Decryption key.
   * @param int $fmt
   *   Optional override for the input encoding. One of FORMAT_RAW, FORMAT_B64
   *   or FORMAT_HEX.
   *
   * @return string
   *   The decrypted string.
   *
   * @throws \Exception
   */
  public function decryptString($in, $key, $fmt = NULL) {
    if ($fmt === NULL) {
      $fmt = $this->format;
    }
    $raw = $in;
    // Restore the encrypted data if encoded.
    if ($fmt == Cryptor::FORMAT_B64) {
      $raw = base64_decode($in);
    }
    else {
      if ($fmt == Cryptor::FORMAT_HEX) {
        $raw = pack('H*', $in);
      }
    }
    // And do an integrity check on the size.
    if (strlen($raw) < $this->ivNumBytes) {
      throw new \Exception('Cryptor::decryptString() - data length ' .
        strlen($raw) . " is less than iv length {$this->ivNumBytes}");
    }
    // Extract the initialisation vector and encrypted data.
    $iv = substr($raw, 0, $this->ivNumBytes);
    $raw = substr($raw, $this->ivNumBytes);
    // Hash the key.
    $keyhash = openssl_digest($key, $this->hashAlgo, TRUE);
    // And decrypt.
    $opts = OPENSSL_RAW_DATA;
    $res = openssl_decrypt($raw, $this->cipherAlgo, $keyhash, $opts, $iv);
    if ($res === FALSE) {
      throw new \Exception('Cryptor::decryptString - decryption failed: ' . openssl_error_string());
    }
    return $res;
  }

  /**
   * Static convenience method for encrypting.
   *
   * @param string $in
   *   String to encrypt.
   * @param string $key
   *   Encryption key.
   * @param int $fmt
   *   Optional override for the output encoding. One of FORMAT_RAW, FORMAT_B64
   *   or FORMAT_HEX.
   *
   * @return string
   *   The encrypted string.
   */
  public static function encrypt($in, $key, $fmt = NULL) {
    $c = new Cryptor();
    return $c->encryptString($in, $key, $fmt);
  }

  /**
   * Static convenience method for decrypting.
   *
   * @param string $in
   *   String to decrypt.
   * @param string $key
   *   Decryption key.
   * @param int $fmt
   *   Optional override for the input encoding. One of FORMAT_RAW, FORMAT_B64
   *   or FORMAT_HEX.
   *
   * @return string
   *   The decrypted string.
   */
  public static function decrypt($in, $key, $fmt = NULL) {
    $c = new Cryptor();
    return $c->decryptString($in, $key, $fmt);
  }

}
