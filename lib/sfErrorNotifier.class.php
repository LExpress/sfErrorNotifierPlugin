<?php
require_once dirname(__FILE__).'/sfErrorNotifierMail.class.php';

/**
 *
 * @package    symfony
 * @subpackage plugin
 * @author     Daniele Occhipinti <>
 */
class sfErrorNotifier
{
  static public function notify(sfEvent $event)
  {
    self::send($event->getSubject(), 'NOTIFY');
  }

  static public function alert(Exception $exception)
  {
    self::send($exception, 'ALERT');
  }

  static public function send(Exception $exception, $subjectPrefix = 'ERROR')
  {
    if ($exception instanceof sfStopException)
    {
      // it's not an error.
	    return;
    }

    if (null === $to = self::getEmailTo())
    {
      // this environment is not set to notify exceptions
      return;
    }

    $context = null;
    if (sfContext::hasInstance())
    {
      $context = sfContext::getInstance();
    }

    $mail = new sfErrorNotifierMail($exception, $context, $subjectPrefix);
    $mail->notify(self::getEmailFormat());
  }

  static public function getEmailTo()
  {
    return sfConfig::get('app_sfErrorNotifier_emailTo');
  }

  static public function getEmailFormat()
  {
    return sfConfig::get('app_sfErrorNotifier_emailFormat', 'html');
  }
}
