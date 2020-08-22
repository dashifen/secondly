<?php

namespace Dashifen\Secondly\Repositories\Records;

interface RecordInterface
{
  /**
   * save
   *
   * Saves the record in the database and returns the post ID given to it.
   *
   * @return int
   * @throws RecordException
   */
  public function save(): int;
  
  /**
   * update
   *
   * Updates a record in the database returning true if it's successful and
   * false otherwise.
   *
   * @return bool
   */
  public function update(): bool;
  
  /**
   * delete
   *
   * Removes a record from the database returning true if it's successful and
   * false otherwise.
   *
   * @return bool
   */
  public function delete(): bool;
}
