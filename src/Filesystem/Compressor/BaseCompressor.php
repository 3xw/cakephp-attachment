<?php
namespace Attachment\Filesystem\Compressor;

use Cake\Core\InstanceConfigTrait;
use Cake\Datasource\ModelAwareTrait;
use Attachment\Model\Entity\Aarchive;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\BadRequestException;

abstract class BaseCompressor
{
  use InstanceConfigTrait;
  use ModelAwareTrait;

  public $Attachments;
  public $attachments = [];
  public $allowedTypes = [];

  protected $_defaultConfig = [
    'profile' => 'default',
    'maxInputSize' => 1000, // IN MB
    'maxFiles' => 40,
    'allowedTypes' => '*/*' // OR array ['image/*','application/pdf']
  ];

  public function __construct(array $config = [])
  {
    $this->setConfig($config);
    $this->Attachments = $this->loadModel('Attachment.Attachments');
    $this->createAllowedTypes();
  }

  protected function createAllowedTypes()
  {
    $this->allowedTypes = is_array($this->getConfig('allowedTypes'))? $this->getConfig('allowedTypes'):  [$this->getConfig('allowedTypes')];
    if(empty($this->allowedTypes)) throw new BadRequestException('Allowed types check can not be empty!');
  }

  protected function checkType($type, $subtype)
  {
    $typeChecked = false;
    $subtypeChecked = false;
    foreach($this->allowedTypes as $mime)
    {
      $types = explode('/', $mime);
      if(count($types) != 2) throw new BadRequestException('Allowed types should be in MIME syntax xx/xx or xx/* or */*');


      if(!$typeChecked)
      {
        if($types[0] == '*') $typeChecked = true;
        if($type == $types[0]) $typeChecked = true;
      }
      if(!$subtypeChecked)
      {
        if($types[1] == '*') $subtypeChecked = true;
        if($subtype == $types[1]) $subtypeChecked = true;
      }

      // if allready good...
      if($typeChecked && $subtypeChecked) break;
    }

    return $typeChecked && $subtypeChecked;
  }

  protected function verify()
  {
    if(count($this->attachments) > $this->getConfig('maxFiles')) throw new BadRequestException('Maximum files reached! maximum allowed is: '.$this->getConfig('maxFiles'));
    $inputSize = 0;
    foreach($this->attachments as $a)
    {
      $inputSize += $a->size;
      $mime = $a->type.'/'.$a->subtype;
      if(!$this->checkType($a->type, $a->subtype)) throw new BadRequestException('The mime type: '.$mime.' is not allowed to be archived.');
    }

    //if($inputSize > $this->getConfig('maxInputSize') * 1000 ) throw new BadRequestException('Max input size reached! maximum allowed is: '.$this->getConfig('maxInputSize').'MB');
  }

  protected function gatherAttachments(Aarchive $entity):bool
  {
    // entity setup
    $entity->set('state', 'PROCESSING');

    // get Attachments
    $this->attachments = $this->Attachments->find()
    ->where([
      'Attachments.id IN' => json_decode($entity->get('aids'))
    ])
    ->toArray();

    return !empty($this->attachments) && !$entity->hasErrors();
  }

  public function compress(Aarchive $entity): bool
  {
    return true;
  }

  public function beforeSave(Event $event): void
  {
    $entity = $event->getSubject()->entity;

    // get attachments
    if(!$this->gatherAttachments($entity)) throw new NotFoundException("Unable to find attachment(s)");

    // verify
    $this->verify();

    // compress
    if(!$this->compress($entity)) throw new BadRequestException("Unable to create archive");
  }

  public function afterSave(Event $event): void
  {
    //if(!$event->getSubject()->success) throw new \Exception("Error Processing Request", 1);
  }
}
