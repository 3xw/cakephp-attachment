<?php
declare(strict_types=1);

namespace Trois\Attachment\Model\Table;

use Trois\Attachment\Model\Entity\Attachment;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Search\Manager;
use Cake\Core\Configure;
use Trois\Attachment\Http\Exception\UploadException;
use Cake\Utility\Inflector;
use ArrayObject;
use Cake\Event\Event;
use Cake\Datasource\EntityInterface;

class AttachmentsTable extends Table
{
  public function initialize(array $config): void
  {
    parent::initialize($config);

    $this->setTable('attachments');
    $this->setDisplayField('name');
    $this->setPrimaryKey('id');

    // assoc
    $this->belongsTo('Users', [
      'type' => 'LEFT',
      'foreignKey' => 'user_id',
      'className' => 'Users',
    ]);
    $this->belongsToMany('Trois/Attachment.Atags', [
      'foreignKey' => 'attachment_id',
      'targetForeignKey' => 'atag_id',
      'joinTable' => 'attachments_atags',
      'className' => 'Trois/Attachment.Atags',
    ]);
    $this->hasOne('Aarchives', [
      'type' => 'LEFT',
      'foreignKey' => 'id',
      'className' => 'Trois/Attachment.Aarchives',
    ]);

    // native behaviors
    $this->addBehavior('Timestamp');

    // custom behaviors
    $this->addBehavior('Trois\Attachment\ORM\Behavior\UserIDBehavior');
    $this->addBehavior('Trois\Attachment\ORM\Behavior\ExternalBehavior');
    $this->addBehavior('Trois\Attachment\ORM\Behavior\EmbedBehavior');
    $this->addBehavior('Trois\Attachment\ORM\Behavior\AarchiveBehavior'); // must be before Fly for deletion if it crash the file remain...
    $this->addBehavior('Trois\Attachment\ORM\Behavior\FlyBehavior');
    $this->addBehavior('Trois\Attachment\ORM\Behavior\ATagBehavior');
    if(Configure::read('Trois/Attachment.translate')) $this->addBehavior('Trois\Utils\ORM\Behavior\TranslateBehavior', ['fields' => ['title','description']]);

    // third party behaviors
    $this->addBehavior('Search.Search',['collectionClass' => 'Trois/Attachment.Attachment']);
  }

  public function find(string $type = 'all', array $options = []): Query
  {
    if ($type == 'all' && Configure::read('Trois/Attachment.translate')) $type = 'translations';
    return parent::find($type, $options);
  }

  public function validationDefault(Validator $validator): Validator
  {
    $validator
    ->uuid('id')
    ->allowEmptyString('id', 'create');

    $validator
    ->scalar('profile')
    ->maxLength('profile', 45)
    ->notEmptyFile('profile');

    $validator
    ->scalar('type')
    ->maxLength('type', 45)
    ->requirePresence('type', 'create')
    ->notEmptyString('type');

    $validator
    ->scalar('subtype')
    ->maxLength('subtype', 45)
    ->requirePresence('subtype', 'create')
    ->notEmptyString('subtype');

    $validator
    ->scalar('name')
    ->maxLength('name', 255)
    ->requirePresence('name', 'create')
    ->notEmptyString('name');

    $validator
    ->requirePresence('size', 'create')
    ->notEmptyString('size');

    // MD5 Uique
    if(Configure::read('Trois/Attachment.md5Unique') )
    {
      $validator
      ->requirePresence('md5', 'create')
      ->notEmptyString('md5')
      ->add('md5', 'unique', ['rule' => 'validateUnique', 'provider' => 'table','message' => 'Attachment already exists']);
    }
    else
    {
      $validator
      ->requirePresence('md5', 'create')
      ->notEmptyString('md5');
    }

    // PATH
    $validator
    ->allowEmptyString('path')
    ->add('path', 'externalUrlValid', [
      'rule' => 'externalUrlIsValid',
      'message' => __('You need to provide a valid url'),
      'provider' => 'table',
    ])
    ->add('path', 'uploadValid', [
      'rule' => 'uploadIsValid',
      'provider' => 'table',
    ]);

    $validator
    ->scalar('embed')
    ->allowEmptyString('embed');

    $validator
    ->scalar('title')
    ->maxLength('title', 255)
    ->allowEmptyString('title');

    $validator
    ->dateTime('date',[ \Cake\Validation\Validation::DATETIME_ISO8601])
    ->allowEmptyDateTime('date');

    $validator
    ->scalar('description')
    ->allowEmptyString('description');

    $validator
    ->scalar('author')
    ->maxLength('author', 255)
    ->allowEmptyString('author');

    $validator
    ->scalar('copyright')
    ->maxLength('copyright', 255)
    ->allowEmptyString('copyright');

    $validator
    ->nonNegativeInteger('width')
    ->allowEmptyString('width');

    $validator
    ->nonNegativeInteger('height')
    ->allowEmptyString('height');

    $validator
    ->nonNegativeInteger('duration')
    ->allowEmptyString('duration');

    $validator
    ->scalar('meta')
    ->allowEmptyString('meta');

    return $validator;
  }

  public function externalUrlIsValid($value, array $context)
  {
    if(!empty($context['data']['type']) && $context['data']['type'] == 'embed') return true;
    if (!empty($value) && is_string($value) && substr($value, 0, 4) == 'http')
    {
      $headers = get_headers($value,1);
      if(substr($headers[0], 9, 3) != 200) return false;
    }
    return true;
  }

  public function uploadIsValid($value, array $context)
  {
    if (!empty($value) && is_a($value, '\Zend\Diactoros\UploadedFile') && $value->getError() !== UPLOAD_ERR_OK) throw new UploadException($value->getError());
    return true;
  }

}
