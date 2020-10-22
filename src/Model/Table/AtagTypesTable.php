<?php
declare(strict_types=1);

namespace Trois\Attachment\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Core\Configure;

/**
* AtagTypes Model
*
* @property \Attachment\Model\Table\AtagsTable|\Cake\ORM\Association\HasMany $Atags
*
* @method \Attachment\Model\Entity\AtagType get($primaryKey, $options = [])
* @method \Attachment\Model\Entity\AtagType newEntity($data = null, array $options = [])
* @method \Attachment\Model\Entity\AtagType[] newEntities(array $data, array $options = [])
* @method \Attachment\Model\Entity\AtagType|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
* @method \Attachment\Model\Entity\AtagType saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
* @method \Attachment\Model\Entity\AtagType patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
* @method \Attachment\Model\Entity\AtagType[] patchEntities($entities, array $data, array $options = [])
* @method \Attachment\Model\Entity\AtagType findOrCreate($search, callable $callback = null, $options = [])
*/
class AtagTypesTable extends Table
{
  /**
  * Initialize method
  *
  * @param array $config The configuration for the Table.
  * @return void
  */
  public function initialize(array $config): void
  {
    parent::initialize($config);

    $this->setTable('atag_types');
    $this->setDisplayField('name');
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
      'field' => ['name']
    ]);
    $this->hasMany('Atags', [
      'foreignKey' => 'atag_type_id',
      'className' => 'Attachment.Atags',
    ]);

    // custom behaviors
    $this->addBehavior('Trois\Utils\ORM\Behavior\SluggableBehavior', ['field' => 'name']);
    if(Configure::read('Attachment.translate'))
    {
      $this->addBehavior('Trois\Utils\ORM\Behavior\TranslateBehavior', ['fields' => ['name']]);
    }
  }

  /**
  * Default validation rules.
  *
  * @param \Cake\Validation\Validator $validator Validator instance.
  * @return \Cake\Validation\Validator
  */
  public function validationDefault(Validator $validator): Validator
  {
    $validator
    ->nonNegativeInteger('id')
    ->allowEmptyString('id', 'create');

    $validator
    ->scalar('name')
    ->maxLength('name', 255)
    ->requirePresence('name', 'create')
    ->notEmptyString('name');

    $validator
    ->scalar('slug')
    ->maxLength('slug', 255)
    ->requirePresence('slug', 'create')
    ->notEmptyString('slug');

    $validator
    ->boolean('exclusive')
    ->notEmptyString('exclusive');

    $validator
    ->integer('order')
    ->allowEmptyString('order');

    return $validator;
  }
}
