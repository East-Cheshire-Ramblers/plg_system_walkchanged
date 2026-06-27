<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.walkchanged
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Ramblerseastcheshire\Plugin\System\Walkchanged\Extension\Walkchanged;

return new class () implements ServiceProviderInterface {
	public function register(Container $container): void
	{
		$container->set(
			PluginInterface::class,
			static function (Container $container): PluginInterface {
				$plugin = new Walkchanged(
					$container->get(DispatcherInterface::class),
					(array) PluginHelper::getPlugin('system', 'walkchanged')
				);

				$plugin->setApplication(Factory::getApplication());

				return $plugin;
			}
		);
	}
};
