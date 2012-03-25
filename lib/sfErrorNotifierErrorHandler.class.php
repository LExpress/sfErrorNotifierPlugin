<?php
require_once dirname(__FILE__).'/sfErrorNotifier.class.php';

/**
 * PHP Error handler
 *
 * @package    sfErrorNotifierPlugin
 * @subpackage lib
 * @author     Maksim Kotlyar <mkotlar@ukr.net>
 */
class sfErrorNotifierErrorHandler
{
  /**
   * @see handlePhpError
   */
  public static function start()
  {
    if (false !== sfErrorNotifier::getEmailConfig())
    {
      set_error_handler(array(__CLASS__, 'handlePhpError'), E_ERROR | E_PARSE | E_NOTICE | E_STRICT);
      set_exception_handler(array(__CLASS__, 'handleException'));
      register_shutdown_function(array(__CLASS__, 'handlePhpFatalError'));

      self::reserveMemory();
    }
  }

  /**
   * Handle a PHP error to send notfication
   *
   * @param integer $errno
   * @param string $errstr
   * @param string $errfile
   * @param integer $errline
   */
  public static function handlePhpError($errno, $errstr, $errfile, $errline)
  {
    sfErrorNotifier::send(new ErrorException($errstr, 0, $errno, $errfile, $errline), 'PHP_ERROR');
  }

  public static function handlePhpFatalError()
  {
    $lastError = error_get_last();
    if (is_null($lastError))
    {
      return;
    }

    self::freeMemory();

    $errors = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, E_STRICT);

    if (in_array($lastError['type'], $errors))
    {
       sfErrorNotifier::send(new ErrorException(
         @$lastError['message'], @$lastError['type'], @$lastError['type'],
         @$lastError['file'], @$lastError['line']
       ), 'PHP_FATAL_ERROR');
    }
  }

  /**
   * Handle exception to send notification
   *
   * @param Exception $e
   */
  public static function handleException(Exception $e)
  {
    sfErrorNotifier::send($e, 'EXCEPTION');
  }

  /**
   * This is allows to catch memory limit fatal errors.
   */
  protected static function reserveMemory()
  {
    $GLOBALS['tmp_buf'] = str_repeat('x', 1024 * 500);
  }

  /**
   * Free momory
   */
  protected static function freeMemory()
  {
    unset($GLOBALS['tmp_buf']);
  }
}
