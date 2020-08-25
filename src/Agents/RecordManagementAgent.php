<?php

namespace Dashifen\Secondly\Agents;

use Dashifen\WPDebugging\WPDebuggingTrait;
use Dashifen\Repository\RepositoryException;
use Dashifen\WPHandler\Agents\AbstractThemeAgent;
use Dashifen\WPHandler\Handlers\HandlerException;
use Dashifen\Secondly\Repositories\Records\Record;
use Dashifen\Secondly\Repositories\Records\RecordException;
use Dashifen\WPHandler\Handlers\Themes\ThemeHandlerInterface;

class RecordManagementAgent extends AbstractThemeAgent
{
  use WPDebuggingTrait;
  
  /**
   * AbstractPluginService constructor.
   *
   * @param ThemeHandlerInterface $handler
   */
  public function __construct(ThemeHandlerInterface $handler)
  {
    date_default_timezone_set('America/New_York');
    parent::__construct($handler);
  }
  
  /**
   * initialize
   *
   * Uses addAction and or addFilter to connect methods of this object to the
   * WordPress ecosystem.
   *
   * @return void
   * @throws HandlerException
   */
  public function initialize(): void
  {
    $this->addAction('wp_ajax_convert-date', 'ajaxConvertDate');
    $this->addAction('wp_ajax_convert-time', 'ajaxConvertTime');
    $this->addAction('admin_post_add-record', 'addRecord');
  }
  
  /**
   * ajaxConvertDate
   *
   * Converts a string-based date into our preferred format.
   *
   * @return void
   */
  protected function ajaxConvertDate(): void
  {
    $this->convertAndDie(
      $_GET['format'] ?? 'Y-m-d',
      (int) strtotime($_GET['date'] ?? 'today')
    );
  }
  
  /**
   * convert
   *
   * Receiving a format and a timestamp, uses the date function to convert the
   * latter into the former.  Then, because we call this from our ajax methods,
   * we die to halt the execution of this request.
   *
   * @param string $format
   * @param int    $timestamp
   *
   * @return void
   */
  private function convertAndDie(string $format, int $timestamp): void
  {
    $conversion = $timestamp !== 0 ? date($format, $timestamp) : '#error#';
    die(json_encode(['conversion' => $conversion]));
  }
  
  /**
   * ajaxConvertTime
   *
   * Uses the above convert function to convert a string-based time into our
   * preferred format.
   *
   * @return void
   */
  protected function ajaxConvertTime(): void
  {
    $this->convertAndDie(
      $GLOBALS['format'] ?? 'H:i',
      (int) strtotime($_GET['time'] ?? 'now')
    );
  }
  
  /**
   * addRecord
   *
   * Uses the posted data to add a record to the database.
   *
   * @return void
   * @throws RecordException
   * @throws RepositoryException
   * @throws HandlerException
   */
  protected function addRecord(): void
  {
    if (wp_verify_nonce($_REQUEST['add-action-nonce'], 'add-action')) {
      $record = new Record($_REQUEST);
      $recordId = $record->save();
    }
  }
}
