<?php
declare(strict_types=1);

namespace Trois\Attachment\Controller;

use Trois\Attachment\Controller\AppController;

class AtagTypesController extends AppController
{
    use \Crud\Controller\ControllerTrait;

    public array $paginate = [
        'limit' => 1000,
        'order' => [
            'AtagTypes.order' => 'ASC',
            'AtagTypes.name' => 'ASC',
        ],
    ];

    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Crud.Crud', [
            'actions' => [
                'index' => ['className' => 'Crud.Index'],
                'view' => ['className' => 'Crud.View'],
                'add' => ['className' => 'Crud.Add'],
                'edit' => ['className' => 'Crud.Edit'],
                'delete' => ['className' => 'Crud.Delete'],
            ],
            'listeners' => [
                'Crud.Api',
                'Crud.ApiPagination',
                'Crud.ApiQueryLog',
            ],
        ]);
    }

    public function index()
    {
        return $this->Crud->execute();
    }
}
