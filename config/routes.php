<?php
declare(strict_types=1);

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes): void {
    // Thumbnail resize (on-demand, cached on disk)
    $routes->connect('/thumbnails/*', [
        'prefix' => null,
        'plugin' => 'Trois/Attachment',
        'controller' => 'Resize',
        'action' => 'proceed',
    ]);

    // Direct source access
    $routes->connect('/source/*', [
        'prefix' => null,
        'plugin' => 'Trois/Attachment',
        'controller' => 'Attachments',
        'action' => 'source',
    ]);

    // Plugin JSON routes — /attachment/*
    // Note (v6) : le scope /admin/attachment a été supprimé, tout passe par JSON.
    // Le zip multi-fichiers (Download::files) est externalisé vers le service Rust
    // zipper (cf. #11 WGRC-419).
    $routes->plugin(
        'Trois/Attachment',
        ['path' => '/attachment'],
        function (RouteBuilder $builder): void {
            $builder->setExtensions(['json']);

            $builder->connect('/download/file', ['controller' => 'Download', 'action' => 'file']);
            $builder->connect('/download/stream', ['controller' => 'Download', 'action' => 'stream']);
            $builder->connect('/download/get-file-token', ['controller' => 'Download', 'action' => 'getFileToken']);
            $builder->connect('/download/get-zip-token', ['controller' => 'Download', 'action' => 'getZipToken']);

            // Custom bulk routes — must come before resources() so they
            // don't collide with the /attachments/:id patterns.
            $builder->connect(
                '/attachments/atags',
                ['controller' => 'Attachments', 'action' => 'deleteAtags'],
                ['_method' => ['DELETE', 'POST']]
            );
            $builder->connect(
                '/attachments/bulk',
                ['controller' => 'Attachments', 'action' => 'bulkEdit'],
                ['_method' => ['PATCH', 'POST']]
            );
            $builder->connect(
                '/attachments/bulk-delete',
                ['controller' => 'Attachments', 'action' => 'bulkDelete'],
                ['_method' => ['DELETE', 'POST']]
            );
            $builder->connect(
                '/atags/counts',
                ['controller' => 'Atags', 'action' => 'counts'],
                ['_method' => ['GET']]
            );
            $builder->connect(
                '/favorites/me',
                ['controller' => 'Atags', 'action' => 'myFavorites'],
                ['_method' => ['GET']]
            );
            $builder->connect(
                '/favorites/toggle',
                ['controller' => 'Atags', 'action' => 'toggleFavorite'],
                ['_method' => ['POST']]
            );

            $builder->resources('Aarchives');
            $builder->resources('Atags');
            $builder->resources('AtagTypes');
            $builder->resources('Attachments');

            $builder->fallbacks(DashedRoute::class);
        }
    );
};
