<?php

namespace Drupal\dcc_email\Entity;

/**
 * Class PersonalInformation.
 *
 * @package Drupal\dcc_email\Entity
 */
class PersonalInformation {

  /**
   * First name of a person.
   *
   * @var string
   */
  private $firstName;

  /**
   * Last name of a person.
   *
   * @var string
   */
  private $lastName;

  /**
   * PersonalInformation constructor.
   *
   * @param string $firstName
   *   First name of a person.
   * @param string $lastName
   *   Last name of a person.
   */
  public function __construct($firstName, $lastName) {
    $this->firstName = $firstName;
    $this->lastName = $lastName;
  }

  /**
   * Returns the first name of a person.
   *
   * @return mixed
   *   First name of a person.
   */
  public function getFirstName() {
    return $this->firstName;
  }

  /**
   * Sets the first name.
   *
   * @param mixed $firstName
   *   First name of a person.
   */
  public function setFirstName($firstName) {
    $this->firstName = $firstName;
  }

  /**
   * Returns the last name.
   *
   * @return mixed
   *   Last name of a person.
   */
  public function getLastName() {
    return $this->lastName;
  }

  /**
   * Sets the last name.
   *
   * @param mixed $lastName
   *   Last name of a person.
   */
  public function setLastName($lastName) {
    $this->lastName = $lastName;
  }

}
