<?php
require_once dirname(__FILE__).'/sfErrorNotifierMail.class.php';

/**
 *
 * @package    symfony
 * @subpackage plugin
 * @author     Daniele Occhipinti
 */
class sfErrorNotifier
{
  static public function sendMessage($subject, $message, $format = 'plain')
  {
    if (null === $from = self::getEmailFrom())
    {
      // this environment is not set to notify exceptions
      return;
    }

    if (null === $to = self::getEmailTo())
    {
      // this environment is not set to notify exceptions
      return;
    }

    $sent = ocariMail::send(array(
      'smtp'    => sfConfig::get('app_smtp'),
      'from'    => $from,
      'to'      => $to,
      'subject' => $subject,
      'message' => array($format => $message),
    ));

    return $sent;
  }

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
    if (null === $from = self::getEmailFrom())
    {
      // this environment is not set to notify exceptions
      return;
    }

    if (null === $to = self::getEmailTo())
    {
      // this environment is not set to notify exceptions
      return;
    }

    if ($exception instanceof sfStopException)
    {
      // it's not an error.
      return;
    }

    $context = null;
    if (sfContext::hasInstance())
    {
      $context = sfContext::getInstance();
    }

    $mail = new sfErrorNotifierMail($from, $to, $exception, $context, $subjectPrefix);
    $mail->notify(self::getEmailFormat());
  }

  static public function getEmailFrom()
  {
    return sfConfig::get('app_sfErrorNotifier_emailFrom');
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
