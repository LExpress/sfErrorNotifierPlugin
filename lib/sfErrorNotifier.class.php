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

    $context = sfContext::hasInstance() ? sfContext::getInstance() : null;

    if ($config = self::getEmailConfig())
    {
      $mail = new sfErrorNotifierMail($exception, $context, $subjectPrefix, $config);
      $mail->notify();
    }
  }

  static public function getEmailConfig()
  {
    $config = sfConfig::get('error_notifier_email_config');

    if (empty($config['smtp']) || empty($config['from']) || empty($config['to']))
    {
       return false;
    }

    return $config;
  }
}
