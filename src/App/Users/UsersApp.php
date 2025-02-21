<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users;

use App\Users\Renderer\AvatarsExtension;
use Joomla\DI\Container;
use Joomla\Router\Router;
use JTracker\AppInterface;

/**
 * Users app
 *
 * @since  1.0
 */
class UsersApp implements AppInterface
{
	/**
	 * Loads services for the component into the application's DI Container
	 *
	 * @param   Container  $container  DI Container to load services into
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function loadServices(Container $container)
	{
		$this->registerRoutes($container->get('router'));
		$this->registerServices($container);
	}

	/**
	 * Registers the routes for the app
	 *
	 * @param   Router  $router  The application router
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	private function registerRoutes(Router $router)
	{
		// Register the component routes
		$maps = json_decode(file_get_contents(__DIR__ . '/routes.json'), true);

		if (!$maps)
		{
			throw new \RuntimeException('Invalid router file for the Tracker app: ' . __DIR__ . '/routes.json', 500);
		}

		foreach ($maps as $patttern => $controller)
		{
			// TODO - Routes should be identified for proper methods
			$router->all($patttern, $controller);
		}
	}

	/**
	 * Registers the services for the app
	 *
	 * @param   Container  $container  DI Container to load services into
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	private function registerServices(Container $container)
	{
		$container->alias(AvatarsExtension::class, 'twig.extension.avatars')
			->share(
				'twig.extension.avatars',
				function (Container $container)
				{
					return new AvatarsExtension($container->get('app'));
				},
				true
			)
			->tag('twig.extension', ['twig.extension.avatars']);
	}
}
