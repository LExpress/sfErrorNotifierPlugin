<?php

/*
 * (c) 2008-2009 Daniele Occhipinti
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

  static protected function send(Exception $exception, $subjectPrefix = 'ERROR')
  {
    if (null === $to = sfConfig::get('app_sfErrorNotifier_emailTo'))
    {
      // this environment is not set to notify exceptions
      return;
    }

    $mail = new sfErrorNotifierMail($exception, sfContext::getInstance(), $subjectPrefix);
    $mail->notify(sfConfig::get('app_sfErrorNotifier_emailFormat', 'html'));
  }
}
