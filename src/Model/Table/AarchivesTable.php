<?php
declare(strict_types=1);

namespace Trois\Attachment\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\Event\Event;
use Cake\Datasource\EntityInterface;

class AarchivesTable extends Table
{

  public function initialize(array $config): void
  {
    parent::initialize($config);

    $this->setTable('aarchives');
    $this->setDisplayField('id');
    $this->setPrimaryKey('id');
    $this->addBehavior('Search.Search');
    $this->searchManager()
    ->add('q', 'Search.Like', [
      'before' => true,
      'after' => true,
      'mode' => 'or',
      'comparison' => 'LIKE',
      'wildcardAny' => '*',
      'wildcardOne' => '?',
      'field' => ['id']
    ]);
    $this->addBehavior('Timestamp');

    $this->belongsTo('Attachments', [
      'type' => 'LEFT',
      'foreignKey' => 'id',
      'className' => 'Attachment.Attachments',
    ]);

    // custom behaviors
  }

  public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
  {
    if(!empty($data['aids']) && is_array($data['aids'])) $data['aids'] = json_encode($data['aids']);
  }

  public function beforeSave(Event $event, EntityInterface $entity, ArrayObject $options)
  {
    if(!$entity->isNew()) return;

    $attachment = $this->Attachments->newEntity([
        'name' => 'PROCESSING ARCHIVE...',
        'size' => 0,
        'profile' => 'default',
        'type' => 'application',
        'subtype' => 'zip',
        'md5' => md5(uniqid((string) rand(), true))
    ]);

    if($attachment->hasErrors())
    {
      $entity->setError('id',['Unable to create a new empty Attachment, validation error!']);
      $event->stopPropagation();
      return false;
    }

    if(!$attachment = $this->Attachments->save($attachment))
    {
      $entity->setError('id',['Unable to create a new empty Attachment, saving error!']);
      $event->stopPropagation();
      return false;
    }

    $entity->set('id', $attachment->id);
  }

  public function validationDefault(Validator $validator): Validator
  {
    $validator
    ->uuid('id')
    ->allowEmptyString('id', 'create');

    $validator
    ->scalar('state')
    ->maxLength('state', 45)
    ->requirePresence('state', 'create')
    ->notEmptyString('state');

    $validator
    ->scalar('aids')
    ->requirePresence('aids', 'create')
    ->notEmptyString('aids');

    $validator
    ->scalar('failure_message')
    ->allowEmptyString('failure_message');

    return $validator;
  }

  public function buildRules(RulesChecker $rules): RulesChecker
  {
    $rules->add($rules->existsIn(['id'], 'Attachments'));
    return $rules;
  }
}
