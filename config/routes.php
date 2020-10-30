<?php
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

// set thumbnails route
Router::connect('/thumbnails/*', ['prefix' => false, 'plugin' => 'Trois\Attachment', 'controller' => 'Resize', 'action' => 'proceed']);

Router::prefix('Admin', function (RouteBuilder $routes) {
	$routes->plugin('Trois/Attachment', function (RouteBuilder $routes) {
		$routes->fallbacks();
	});
});

// set plugin stuff : )
Router::plugin(
    'Trois/Attachment',
    ['path' => '/attachment'],
    function (RouteBuilder $routes)
    {
      // protect from direct access
      //$routes->redirect('/resize/*', '/');

			$routes->resources('Aarchives');

      $routes->setExtensions(['json']);
      $routes->fallbacks('DashedRoute');
    }
);
