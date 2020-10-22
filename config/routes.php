<?php
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

// set thumbnails route
Router::connect('/thumbnails/*', ['prefix' => false, 'plugin' => 'Attachment', 'controller' => 'Resize', 'action' => 'proceed']);

Router::prefix('Admin', function (RouteBuilder $routes) {
	$routes->plugin('Attachment', function (RouteBuilder $routes) {
		$routes->fallbacks();
	});
});

// set plugin stuff : )
Router::plugin(
    'Attachment',
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
