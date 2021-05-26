<?php
/**
* @var \App\View\AppView $this
* @var \Cake\Datasource\EntityInterface[]|\Cake\Collection\CollectionInterface $atags
*/
?>
<nav class="navbar navbar-expand-lg">
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="navbar-collapse" id="navbarSupportedContent">
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <?= $this->Html->link('<i class="material-icons">add</i> '.__('Add'),['action'=>'add'], ['class' => '','escape'=>false]) ?>
      </li>
    </ul>
  </div>
</nav>
<div class="utils--spacer-semi"></div>
<div class="row no-gutters g-0">
  <div class="col-11 mx-auto ">
    <!-- LIST ELEMENT -->
    <div class="card">

      <div class="card-header">
        <h2 class="card-title">
          <?= __('Atags')?> <small><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></small>
        </h2>
        <?= $this->Form->create(null, ['novalidate', 'class'=>'', 'role'=>'search']) ?>
        <?= $this->Form->input('q', ['class'=>'form-control', 'placeholder'=>__('Search...'), 'label'=>false]) ?>
        <?= $this->Form->end() ?>
        <?php if (isset($q)): ?>
            Search value : <?= $this->Html->link($q.'<i class="material-icons">cancel</i>',['action'=>'index'], ['escape'=>false])?>
            <div class="utils--spacer-semi"></div>
        <?php endif; ?>
      </div>
      <!-- START CONTEMT -->
      <div class="card-body">
        <figure class="figure figure--table">
          <table id="datatables" class="table table-no-bordered table-hover dataTable dtr-inline" cellspacing="0" width="100%" style="width: 100%;" role="grid" aria-describedby="datatables_info">
            <thead class="thead-default">
              <tr>
                <th scope="col"><?= $this->Paginator->sort('name') ?></th>
                <th scope="col"><?= $this->Paginator->sort('atag_type_id') ?></th>
                <th scope="col"><?= $this->Paginator->sort('user_id') ?></th>
                <th scope="col" class="actions"><?= __('Actions') ?></th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($atags as $atag): ?>
              <tr>
                <td><?= h($atag->name) ?></td>
                <td><?= $atag->has('atag_type') ? $this->Html->link($atag->atag_type->name, ['controller' => 'AtagTypes', 'action' => 'view', $atag->atag_type->id]) : '' ?></td>
                <td><?= $atag->has('user') ? $this->Html->link($atag->user->email, ['controller' => 'Users', 'action' => 'view', $atag->user->id]) : '' ?></td>
                <td data-title="actions" class="actions" class="text-right">
                  <div class="btn-group">
                    <?= $this->Html->link('<i class="material-icons">visibility</i>', ['action' => 'view', $atag->id],['class' => 'btn btn-xs btn-simple btn-info btn-icon edit','escape' => false]) ?>
                    <?= $this->Html->link('<i class="material-icons">mode_edit</i>', ['action' => 'edit', $atag->id], ['class' => 'btn btn-xs btn-simple btn-warning btn-icon edit','escape' => false]) ?>
                    <?= $this->Form->postLink('<i class="material-icons">delete</i>', ['action' => 'delete', $atag->id], ['class' => 'btn btn-xs btn-simple btn-danger btn-icon remove','escape' => false, 'confirm' => __('Are you sure you want to delete # {0}?',  $atag->id)]) ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </figure>
      </div>
      <!-- END CONTEMT -->
      <!-- START FOOTER -->
      <div class="card-footer">
        <div class="row no-gutters g-0">
          <div class="col-6">
            <?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?>
          </div>
          <div class="col-6">
          <nav aria-label="pagination">
            <ul class="pagination justify-content-end">
              <?= $this->Paginator->first('<< ' . __('first'),['class'=>'btn '])?>
              <?= $this->Paginator->prev('< ' . __('previous')) ?>
              <?= $this->Paginator->numbers() ?>
              <?= $this->Paginator->next(__('next') . ' >') ?>
              <?= $this->Paginator->last(__('last') . ' >>') ?>
            </ul>
          </nav>
        </div>
      </div>
    </div>
    <!-- END FOOTER -->
  </div><!-- end content-->
</div><!-- end card-->
</div><!-- end col-xs-12-->
