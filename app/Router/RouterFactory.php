<?php

declare(strict_types=1);

namespace App\Router;

use Nette;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
	use Nette\StaticClass;

	public static function createRouter(): RouteList
	{
		$router = new RouteList;

		
		$router[] = $module = new RouteList('Adm');
		$module->addRoute('adm/<presenter>/<action>', 'Adm:default');		

		$router->addRoute('<presenter>/<action>[/<id>]', 'Svj:default');
		
		return $router;
	}
}
