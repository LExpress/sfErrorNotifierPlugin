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
 * @author     Daniele Occhipinti <>
 */
class sfErrorNotifierMail
{
  protected 
    $body       = null,
    $to         = null,
    $from       = null,
    $subject    = null,
    $data       = array(),
    $exception  = null,
    $context    = null,
    $env        = 'n/a',
    $host       = 'n/a';

  public function __construct($from, $to, Exception $exception, sfContext $context = null, $subjectPrefix = 'ERROR')
  {
    $this->exception = $exception;
    $this->context = $context;

    $this->from = $from;
  	$this->to = $to;

    if ($this->context && $conf = $this->context->getConfiguration())
    {
      $this->env = $conf->getEnvironment();
    }

    $this->data = array(
      'className'   => get_class($exception),
      'message'     => null !== $exception->getMessage() ? $exception->getMessage() : 'n/a',
    );

    if ($this->context)
    {
      $this->data += array(
        'moduleName'  => $this->context->getModuleName(),
        'actionName'  => $this->context->getActionName(),
        'uri'         => $this->context->getRequest()->getUri(),
      );
    }

    if (isset($_SERVER['HTTP_HOST']))
    {
      $this->host = $_SERVER['HTTP_HOST'];
    }

    $this->subject = sprintf('%s: %s Exception - %s', $subjectPrefix, $this->host, $this->data['message']);
  }

  public function notify($format = 'html')
  {
    if (empty($this->to) || empty($this->from))
    {
     	return false;
    }

    if ($format == 'html')
    {
    	return $this->notifyHtml();
    }

    return $this->notifyTxt();
  }

  private function notifyHtml()
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
      else
      {
        if (method_exists($user, 'getGroupNames'))
        {
          $groups = implode(', ' , $user->getGroupNames());
        }

        $credentials = implode(', ' , $user->listCredentials());
      }

      if ($groups)
      {
        $this->addRow('Groups', $groups);
      }
      $this->addRow('Credentials', $credentials);

      $this->body .= '</table>';
    }

    $this->body .= '</div>';

    // send mail
    $sent = ocariMail::send(array(
      'smtp'    => sfConfig::get('app_smtp'),
      'from'    => $this->from,
      'to'      => $this->to,
      'subject' => $this->subject,
      'message' => array('html' => $this->body),
    ));

    return $sent;
  }

  private function notifyTxt()
  {
    $this->body = "Resume:\n";

    if ($this->exception)
    {
      $this->body .= 'Message: '.$this->exception->getMessage()."\n";
    }
    else
    {
      $this->body .= 'Subject: '.$this->subject."\n";
    }
    $this->body .= 'Environment: '.$this->env . "\n";
    $this->body .= 'Generated at: '.date('H:i:s j F Y')."\n\n";

    if ($this->exception)
    {
      $this->body .= "Exception:\n";
      $this->body .= $this->exception."\n\n";
    }

    $this->body .= "Additional Data:\n";
    foreach($this->data as $key => $value)
    {
    	$this->body .= $key . ': ' . $value . "\n";
    }
    $this->body .= "\n";
    
    $this->body .= "Parameters:\n";
    foreach ($_POST as $key => $value)
    {
      $this->body .= $key.': '.$value."\n";
    }
    $this->body .= "\n\n";

    if ($this->context)
    {
      $user = $this->context->getUser();
      
      if (!$user->isAnonymous())
      {
        $this->body .= "User Name:\n";
        $this->body .= $user->getUserName();
      }

      $this->body .= "User Attributes:\n";
      foreach ($user->getAttributeHolder()->getAll() as $key => $value)
      {
        if (is_array($value))
        {
          $value = 'Array: ' . implode(', ',  $value);
        }
        $this->body .= $key . ': ' . $value . "\n";
      }
      $this->body .= "\n\n";

      if (!$user->isAnonymous() && !$user->isSuperAdmin() && method_exists($user, 'getGroupNames'))
      {
        $this->body .= "User Groups:\n";
        $this->body .= implode(', ' , $user->getGroupNames());
        $this->body .= "\n\n";
      }

      $this->body .= "User Credentials:\n";
      if ($user->isAnonymous())
      {
        $this->body .= 'Not connected';
      }
      else if ($user->isSuperAdmin())
      {
        $this->body .= 'Super admin';
      }
      else
      {
        $this->body .= implode(', ' , $user->listCredentials());
        $this->body .= "\n\n";
      }
    }

    // send mail
    $sent = ocariMail::send(array(
      'smtp'    => sfConfig::get('app_smtp'),
      'from'    => $this->from,
      'to'      => $this->to,
      'subject' => $this->subject,
      'message' => array('plain' => $this->body),
    ));

    return $sent;
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
}

