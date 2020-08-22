<?php

namespace Dashifen\Secondly\Repositories;

use Dashifen\Repository\Repository;
use Dashifen\Repository\RepositoryException;

/**
 * Class ValidatingRepository
 *
 * @property-read array $errors
 *
 * @package Dashifen\Secondly\Repositories
 */
class ValidatingRepository extends Repository
{
  protected array $errors = [];
  
  /**
   * setPropertyValues
   *
   * Called from the AbstractRepository constructor, this method uses the
   * $data parameter of the constructor to set property values.
   *
   * @param array $data
   */
  protected function setPropertyValues (array $data): void
  {
    // the try block of this method is the same as our parent's method.  for
    // the moment, we don't have a better way to do all of its work while also
    // catching exceptions thrown within the setting process so that we can add
    // them to the $errors property and move onto the next property.
    
    foreach ($data as $property => $value) {
      try {
        if (!property_exists($this, $property)) {
          $property = $this->convertFieldToProperty($property);
        }
  
        if (property_exists($this, $property)) {
          $setter = 'set' . ucfirst($property);
          if (method_exists($this, $setter)) {
            $this->{$setter}($value);
          } else {
            throw new RepositoryException('Setter missing: $setter.', RepositoryException::UNKNOWN_SETTER);
          }
        } else {
          throw new RepositoryException('Unknown property: $property.', RepositoryException::UNKNOWN_PROPERTY);
        }
      } catch (RepositoryException $e) {
        $errors[$property] = $e->getMessage();
      }
    }
  }
}
