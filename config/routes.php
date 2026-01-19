<?php
declare(strict_types=1);

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes): void {
    // set thumbnails route
    $routes->connect('/thumbnails/*', [
        'prefix' => null,
        'plugin' => 'Trois/Attachment',
        'controller' => 'Resize',
        'action' => 'proceed',
    ]);
    $routes->connect('/source/*', [
        'prefix' => null,
        'plugin' => 'Trois/Attachment',
        'controller' => 'Attachments',
        'action' => 'source',
    ]);

    $routes->prefix('Admin', function (RouteBuilder $builder): void {
        $builder->plugin('Trois/Attachment', function (RouteBuilder $pluginRoutes): void {
            $pluginRoutes->fallbacks(DashedRoute::class);
        });
    });

    // set plugin stuff
    $routes->plugin(
        'Trois/Attachment',
        ['path' => '/attachment'],
        function (RouteBuilder $builder): void {
            $builder->resources('Aarchives');
            $builder->setExtensions(['json']);
            $builder->fallbacks(DashedRoute::class);
        }
    );
};
