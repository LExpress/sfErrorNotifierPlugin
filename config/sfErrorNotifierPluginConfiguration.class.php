<?php

class sfErrorNotifierPluginConfiguration extends sfPluginConfiguration
{
  static protected $configLoaded = false;

  public function initialize()
  {
    if (!self::$configLoaded && $this->configuration instanceof sfApplicationConfiguration)
    {
      if ($file = $this->configuration->getConfigCache()->checkConfig('config/error_notifier.yml', true))
      {
        include($file);

        $this->dispatcher->connect('application.throw_exception', array('sfErrorNotifier', 'notify'));
      }
    }

    sfErrorNotifierErrorHandler::start();
  }
}
