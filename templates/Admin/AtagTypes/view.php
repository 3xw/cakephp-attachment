<?php
/**
* @var \App\View\AppView $this
* @var \Cake\Datasource\EntityInterface $atagType
*/
?>
<nav class="navbar navbar-expand-lg">
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <?= $this->Html->link('<i class="material-icons">list</i> '.__('List'),['action'=>'index'], ['class' => '','escape'=>false]) ?>
      </li>
      <li class="nav-item">
        <?= $this->Html->link('<i class="material-icons">mode_edit</i> '.__('Edit'),['action'=>'edit', $atagType->id], ['class' => '','escape'=>false]) ?>
      </li>
      <li class="nav-item">
        <?= $this->Html->link('<i class="material-icons">delete</i> '.__('Delete'),['action'=>'delete',$atagType->id], ['class' => '','escape'=>false, 'confirm' => __('Are you sure you want to delete # {0}?', $atagType->id)]) ?>
      </li>
    </ul>
  </div>
</nav>
<div class="utils--spacer-semi"></div>
<div class="row no-gutters g-0">
  <div class="col-8 mx-auto">
    <div class="card">
      <!-- pic -->
      <?php if ($atagType->attachment): ?>
        <?php if ($atagType->attachment->type == 'image'): ?>
          <?= $this->Attachment->image(['image' => $atagType->attachment->path, 'profile' => $atagType->attachment->profile, 'width' => '1200px',  'cropratio' => '16:8'], ['class' => 'card-img-top']) ?>
        <?php endif; ?>
      <?php endif; ?>
      <!-- CONTENT -->
      <div class="card-body">
        <h2><?= h($atagType->name) ?></h2>
        <label><?= __('Slug') ?></label>
        <?= h($atagType->slug) ?>
      </div>
    </div>
    <?php if (!empty($atagType->atags)): ?>
      <div class="card  mt-4">
        <div class="card-header">
          <h4 class="card-title"><?= __('Related Atags')?></h4>
        </div>
        <div class="card-body">
          <figure class="figure figure--table">
            <table id="datatables" class="table table-striped table-no-bordered table-hover dataTable dtr-inline" cellspacing="0" width="100%" style="width: 100%;" role="grid" aria-describedby="datatables_info">
              <thead>
                <tr>
                  <th scope="col"><?= __('Id') ?></th>
                  <th scope="col"><?= __('Name') ?></th>
                  <th scope="col"><?= __('Slug') ?></th>
                  <th scope="col"><?= __('Atag Type Id') ?></th>
                  <th scope="col"><?= __('User Id') ?></th>
                  <th class="actions"><?= __('Actions') ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($atagType->atags as $atags): ?>
                  <tr>
                    <td><?= h($atags->id) ?></td>
                    <td><?= h($atags->name) ?></td>
                    <td><?= h($atags->slug) ?></td>
                    <td><?= h($atags->atag_type_id) ?></td>
                    <td><?= h($atags->user_id) ?></td>
                    <td data-title="actions" class="actions" class="text-right">
                      <div class="btn-group">
                        <?= $this->Html->link(__('View'), ['controller' => 'Atags', 'action' => 'view', $atags->id]) ?>
                      </td>
                    </div>
                  </tr >
                <?php endforeach; ?>
              </tbody>
            </table>
          </figure>
        </div>
      </div>
    <?php endif; ?>
  </div>
  <div class="col-3 mx-auto">
    <div class="card">
      <div class="card-header">
        <h4><?= __('Informations')?></h4>
      </div>
      <!-- CONTENT -->
      <div class="card-body">
        <figure class="figure figure--table">
          <table class="table">
            <tbody>
              <tr>
                <th scope="row"><?= __('Exclusive') ?></th>
                <td><?= $atagType->exclusive ? __('Yes') : __('No'); ?></td>
              </tr>
              <tr>
                <th scope="row"><?= __('Id') ?></th>
                <td><?= $this->Number->format($atagType->id) ?></td>
              </tr>
              <tr>
                <th scope="row"><?= __('Order') ?></th>
                <td><?= $this->Number->format($atagType->order) ?></td>
              </tr>
            </table>
          </figure>
        </div>
      </div>
    </div>
  </div>
  <div class="utils--spacer-default"></div>
