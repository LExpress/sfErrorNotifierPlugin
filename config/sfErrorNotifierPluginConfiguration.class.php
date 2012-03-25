<?php

class sfErrorNotifierPluginConfiguration extends sfPluginConfiguration
{
  const CONFIG_PATH = 'config/error_notifier.yml';

  /**
   * Initializes the plugin.
   *
   * This method is called after the plugin's classes have been added to sfAutoload.
   */
  public function initialize()
  {
    if ($this->configuration instanceof sfApplicationConfiguration)
    {
      $configCache = $this->configuration->getConfigCache();
      $configCache->registerConfigHandler(self::CONFIG_PATH, 'sfDefineEnvironmentConfigHandler', array(
        'prefix' => 'error_notifier_',
      ));

      require $configCache->checkConfig(self::CONFIG_PATH);

      $this->dispatcher->connect('application.throw_exception', array('sfErrorNotifier', 'notify'));
    }

    sfErrorNotifierErrorHandler::start();
  }
}
