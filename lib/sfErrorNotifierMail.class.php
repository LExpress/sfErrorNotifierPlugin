<?php

/*
 * (c) 2009 Gustavo Garcia
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package    symfony
 * @subpackage plugin
 * @author     Daniele Occhipinti
 */
class sfErrorNotifierMail
{
  protected
    $config     = array(),
    $body       = null,
    $subject    = null,
    $data       = array(),
    $exception  = null,
    $context    = null,
    $env        = 'n/a',
    $host       = 'n/a';

  public function __construct(Exception $exception, sfContext $context = null, $subjectPrefix = 'ERROR', $config)
  {
    $this->exception = $exception;
    $this->context   = $context;
    $this->config    = $config;

    if ($this->context && $conf = $this->context->getConfiguration())
    {
      $this->env = $conf->getEnvironment();
    }

    $this->data = array(
      'Exception class' => get_class($exception),
      'Message'         => null !== $exception->getMessage() ? $exception->getMessage() : 'n/a',
      'Host Server'     => gethostname(),
    );

    if ($this->context)
    {
      $this->data += array(
        'Module name' => $this->context->getModuleName(),
        'Action name' => $this->context->getActionName(),
        'URI'         => $this->context->getRequest()->getUri(),
      );
    }

    if (isset($_SERVER['HTTP_HOST']))
    {
      $this->host = $_SERVER['HTTP_HOST'];
    }

    $this->subject = sprintf('%s: %s Exception - %s', $subjectPrefix, $this->host, $this->data['Message']);
  }

  public function notify()
  {
    //Initialize the body message
    $this->body = '<div style="font-family: Verdana, Arial;">';

    //The exception resume
    $this->addTitle('Resume');

    $this->beginTable();
    if ($this->exception)
    {
      $this->addRow('Message', $this->exception->getMessage());
    }
    else
    {
      $this->addRow('Subject', $this->subject);
    }
    $this->addRow('Environment', $this->env);
    $this->addRow('Generated at' , date('H:i:s j F Y'));
    $this->body .= '</table>';

    //The exception itself
    if ($this->exception)
    {
      $this->addTitle('Exception');

      $this->beginTable();
      $this->addRow('Trace', $this->exception);

      $this->body .= '</table>';
    }

    //Aditional Data
    $this->addTitle('Additional Data');
    $this->beginTable();
    foreach ($this->data as $key=>$value)
    {
      $this->addRow($key, $value);
    }

    $subtable = array();
    foreach ($_POST as $key => $value)
    {
      $subtable[] = '<b>'.$key.'</b>: '.$value;
    }
    $subtable = implode('<br/>', $subtable);
    $this->addRow('parameters', $subtable);

    $this->body .= '</table>';

    // User attributes and credentials
    if ($this->context)
    {
      $this->addTitle('User');
      $this->beginTable();
      $user = $this->context->getUser();

      if (!$user->isAnonymous())
      {
        $this->addRow('Name', $user->getUserName());
      }

      $subtable = array();

      foreach ($user->getAttributeHolder()->getAll() as $key => $value)
      {
        if (is_array($value))
        {
          $value = 'Array: ' . implode(', ',  $value);
        }
        else if (is_object($value))
        {
          if (!method_exists($value, "__toString"))
          {
            $value = "Object: ".get_class($value);
          }
        }
        $subtable[] = '<b>'.$key.'</b>: '.$value;
      }
      $subtable = implode('<br/>', $subtable);
      $this->addRow('Attributes', $subtable);

      $groups = null;
      if ($user->isAnonymous())
      {
        $credentials = 'Not connected';
      }
      else if ($user->isSuperAdmin())
      {
        $credentials = 'Super admin';
      }
      else if (method_exists($user, 'listCredentials'))
      {
        $credentials = implode(', ' , $user->listCredentials());
      }
      else
      {
        $credentials = '';
      }

      if ($groups)
      {
        $this->addRow('Groups', $groups);
      }
      $this->addRow('Credentials', $credentials);

      $this->body .= '</table>';
    }

    $this->body .= '</div>';

    $mailer = $this->getMailer();

    $message = Swift_Message::newInstance()
      ->setFrom($this->config['from'])
      ->setTo($this->config['to'])
      ->setSubject($this->subject)
      ->setBody($this->body, 'text/html')
    ;

    // send mail
    return $mailer->send($message);
  }

  private function addRow($th, $td = '&nbsp;')
  {
    $this->body .= "<tr style=\"padding: 4px;spacing: 0;text-align: left;\">\n<th style=\"background:#cccccc\" width=\"100px\">$th:</th>\n<td style=\"padding: 4px;spacing: 0;text-align: left;background:#eeeeee\">".nl2br($td)."</td>\n</tr>";
  }

  private function addTitle($title)
  {
    $this->body .= '<h1 style="color:#000; padding:5px;">'.$title.'</h1>';
  }

  private function beginTable()
  {
    $this->body .= '<table cellspacing="1" width="100%">';
  }

  /**
   * @return sfMailer
   */
  private function getMailer()
  {
    if (!class_exists('Swift'))
    {
      $swift_dir = sfConfig::get('sf_symfony_lib_dir').'/vendor/swiftmailer/lib';
      require_once $swift_dir.'/classes/Swift.php';
      Swift::registerAutoload($swift_dir.'/swift_init.php');
    }

    return Swift_Mailer::newInstance(Swift_SmtpTransport::newInstance($this->config['smtp'], 25));
  }
}

