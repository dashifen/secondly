<?php

namespace Dashifen\Secondly\App;

use Dashifen\Validator\AbstractValidator;
use Dashifen\WPHandler\Traits\CaseChangingTrait;

class RecordValidator extends AbstractValidator
{
  use CaseChangingTrait;
  
  /**
   * isValid
   *
   * Passed the value through a validator based on the field name.
   *
   * @param string $field
   * @param mixed  $value
   *
   * @return bool
   */
  public function isValid(string $field, $value): bool
  {
    // our parent's version of this method forces us to validate the values of
    // an array rather than the array itself.  but, for our purposes, it's
    // important to take a look at the project and task properties in all their
    // array-ness.  so, we can simplify the way that we validate fields to the
    // following ternary statement.
  
    return $this->canValidate($field)
      ? $this->{$this->getValidationMethod($field)}($value)
      : true;
  }
  
  /**
   * getValidationMethod
   *
   * Returns the name of a method assumed to be defined within the concrete
   * extension of this abstract class that will validate data labeled by our
   * field parameter.
   *
   * @param string $field
   *
   * @return string
   */
  protected function getValidationMethod(string $field): string
  {
    // our validation methods are the world "validate" followed by the studly
    // case version of our $field.  the $field variable is in camel case, so we
    // can switch that with our CaseChangingTrait methods as follows.
    
    return "validate" . $this->camelToStudlyCase($field);
  }
  
  /**
   * validateDate
   *
   * Returns true if $value is a Y-m-d formatted date string.
   *
   * @param string $value
   *
   * @return bool
   */
  protected function validateDate(string $value): bool
  {
    return $this->isDate($value, 'Y-m-d');
  }
  
  /**
   * validateTime
   *
   * Returns true if $value is a H:i formatted time string.
   *
   * @param string $value
   *
   * @return bool
   */
  protected function validateTime(string $value): bool
  {
    return $this->isTime($value, 'H:i');
  }
  
  /**
   * validateActivity
   *
   * Returns true as long as our value is not empty.
   *
   * @param string $value
   *
   * @return bool
   */
  protected function validateActivity(string $value): bool
  {
    return $this->isNotEmpty($value);
  }
  
  /**
   * validateTermData
   *
   * Returns true if $value is valid term data.  That is, if it is either
   * an ID and no "other" value, or its ID is the word "other" and the other
   * value is not empty.
   *
   * @param array $value
   *
   * @return bool
   */
  protected function validateTermData(array $value): bool
  {
    $id = $value['id'] ?? null;
    $other = $value['other'] ?? null;
    return ($this->isPositiveInt($id) && $this->isEmpty($other))
      || ($id === 'other' && $this->isNotEmpty($other));
  }
  
  /**
   * isPositiveInt
   *
   * Returns true if our $posInt parameter is a positive integer.
   *
   * @param mixed $posInt
   *
   * @return bool
   */
  private function isPositiveInt($posInt): bool
  {
    return $this->isPositive($posInt) && $this->isInteger($posInt);
  }
}
