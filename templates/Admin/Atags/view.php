<?php
/**
* @var \App\View\AppView $this
* @var \Cake\Datasource\EntityInterface $atag
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
        <?= $this->Html->link('<i class="material-icons">mode_edit</i> '.__('Edit'),['action'=>'edit', $atag->id], ['class' => '','escape'=>false]) ?>
      </li>
      <li class="nav-item">
        <?= $this->Html->link('<i class="material-icons">delete</i> '.__('Delete'),['action'=>'delete',$atag->id], ['class' => '','escape'=>false, 'confirm' => __('Are you sure you want to delete # {0}?', $atag->id)]) ?>
      </li>
    </ul>
  </div>
</nav>
<div class="utils--spacer-semi"></div>
<div class="row no-gutters">
  <div class="col-8 mx-auto">
    <div class="card">
      <!-- pic -->
      <?php if ($atag->attachment): ?>
        <?php if ($atag->attachment->type == 'image'): ?>
          <?= $this->Attachment->image(['image' => $atag->attachment->path, 'profile' => $atag->attachment->profile, 'width' => '1200px',  'cropratio' => '16:8'], ['class' => 'card-img-top']) ?>
        <?php endif; ?>
      <?php endif; ?>
      <!-- CONTENT -->
      <div class="card-body">
        <h2><?= h($atag->name) ?></h2>
        <label><?= __('Slug') ?></label>
        <?= h($atag->slug) ?>
        <label><?= __('Atag Type Id') ?></label>
        <?= h($atag->atag_type_id) ?>
        <label><?= __('User Id') ?></label>
        <?= h($atag->user_id) ?>
      </div>
    </div>
    <?php if (!empty($atag->attachments)): ?>
      <div class="card  mt-4">
        <div class="card-header">
          <h4 class="card-title"><?= __('Related Attachments')?></h4>
        </div>
        <div class="card-body">
          <figure class="figure figure--table">
            <table id="datatables" class="table table-striped table-no-bordered table-hover dataTable dtr-inline" cellspacing="0" width="100%" style="width: 100%;" role="grid" aria-describedby="datatables_info">
              <thead>
                <tr>
                  <th scope="col"><?= __('Id') ?></th>
                  <th scope="col"><?= __('Profile') ?></th>
                  <th scope="col"><?= __('Type') ?></th>
                  <th scope="col"><?= __('Subtype') ?></th>
                  <th scope="col"><?= __('Created') ?></th>
                  <th scope="col"><?= __('Modified') ?></th>
                  <th scope="col"><?= __('Name') ?></th>
                  <th scope="col"><?= __('Size') ?></th>
                  <th scope="col"><?= __('Md5') ?></th>
                  <th scope="col"><?= __('Path') ?></th>
                  <th scope="col"><?= __('Embed') ?></th>
                  <th scope="col"><?= __('Title') ?></th>
                  <th scope="col"><?= __('Date') ?></th>
                  <th scope="col"><?= __('Description') ?></th>
                  <th scope="col"><?= __('Author') ?></th>
                  <th scope="col"><?= __('Copyright') ?></th>
                  <th scope="col"><?= __('Width') ?></th>
                  <th scope="col"><?= __('Height') ?></th>
                  <th scope="col"><?= __('Duration') ?></th>
                  <th scope="col"><?= __('Meta') ?></th>
                  <th scope="col"><?= __('User Id') ?></th>
                  <th class="actions"><?= __('Actions') ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($atag->attachments as $attachments): ?>
                  <tr>
                    <td><?= h($attachments->id) ?></td>
                    <td><?= h($attachments->profile) ?></td>
                    <td><?= h($attachments->type) ?></td>
                    <td><?= h($attachments->subtype) ?></td>
                    <td><?= h($attachments->created) ?></td>
                    <td><?= h($attachments->modified) ?></td>
                    <td><?= h($attachments->name) ?></td>
                    <td><?= h($attachments->size) ?></td>
                    <td><?= h($attachments->md5) ?></td>
                    <td><?= h($attachments->path) ?></td>
                    <td><?= h($attachments->embed) ?></td>
                    <td><?= h($attachments->title) ?></td>
                    <td><?= h($attachments->date) ?></td>
                    <td><?= h($attachments->description) ?></td>
                    <td><?= h($attachments->author) ?></td>
                    <td><?= h($attachments->copyright) ?></td>
                    <td><?= h($attachments->width) ?></td>
                    <td><?= h($attachments->height) ?></td>
                    <td><?= h($attachments->duration) ?></td>
                    <td><?= h($attachments->meta) ?></td>
                    <td><?= h($attachments->user_id) ?></td>
                    <td data-title="actions" class="actions" class="text-right">
                      <div class="btn-group">
                        <?= $this->Html->link(__('View'), ['controller' => 'Attachments', 'action' => 'view', $attachments->id]) ?>
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
                <th scope="row"><?= __('Atag Type') ?></th>
                <td><?= $atag->has('atag_type') ? $this->Html->link($atag->atag_type->name, ['controller' => 'AtagTypes', 'action' => 'view', $atag->atag_type->id]) : '' ?></td>
              </tr>
              <tr>
                <th scope="row"><?= __('User') ?></th>
                <td><?= $atag->has('user') ? $this->Html->link($atag->user->id, ['controller' => 'Users', 'action' => 'view', $atag->user->id]) : '' ?></td>
              </tr>
              <tr>
                <th scope="row"><?= __('Id') ?></th>
                <td><?= $this->Number->format($atag->id) ?></td>
              </tr>
            </table>
          </figure>
        </div>
      </div>
    </div>
  </div>
  <div class="utils--spacer-default"></div>
