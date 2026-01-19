<?php
namespace Trois\Attachment\ORM\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Exception;
use Cake\Utility\Text;
use Cake\Datasource\EntityInterface;
use Cake\Http\Session;

use Cake\ORM\Behavior;
use Cake\ORM\Table;

use Cake\Log\Log;

use Cake\ORM\Locator\LocatorAwareTrait;

/**
* Storage behavior
*/
class ATagBehavior extends Behavior
{
  use LocatorAwareTrait;

  public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
  {

    // GET CONFIG
    $session = new Session();
    $uuid = empty($data['uuid'])? '' : $data['uuid'];
    $sessionAttachment = $session->read('Trois/Attachment.'.$uuid);
    if(!$sessionAttachment){
      $event->stopPropagation();
      //throw new Exception('Attachment keys not found in session! Please pass Attachment settings throught session!');
      return;
    }

    // see if tag restricted
    $isTagRestricted = false;
    if(!empty($sessionAttachment['restrictions']))
    {
      foreach($sessionAttachment['restrictions'] as $restriction )
      {
        if($restriction == 'tag_restricted' || $restriction == 'tag_or_restricted')
        {
          $isTagRestricted = true;
        }
      }
    }
    if($isTagRestricted)
    {
      if(($sessionAttachment['atagsDisplay'] != 'select') || empty($data['atags'])){
        $data['atags'] = [];
        $tags = empty($sessionAttachment['atags'])? []: $sessionAttachment['atags'];
        foreach($tags as $tag)
        {
          array_push($data['atags'],['name' => $tag]);
        }
      }
    }
    if(!empty($data['atags']))
    {


      $atags = $this->fetchTable('Trois/Attachment.Atags');
      $tags = [];
      foreach($data['atags'] as $tag)
      {
        $query = $atags->find()
        ->contain([])
        ->where([
          'OR' =>[
            'name' => $tag['name'],
            'slug' => Text::slug($tag['name'])
          ]
        ]);

        $atag = $query->first();

        if(!empty($atag))
        {
          array_push($tags, $atag->id);
        }else
        {
          $atag = $atags->newEntity([]);
          $atag = $atags->patchEntity($atag, $tag);
          $atag = $atags->save($atag);
          if($atag)
          {
            array_push($tags, $atag->id);
          }
        }
      }
      if(!empty($tags))
      {
        $data['atags'] = ['_ids' => $tags];
      }else
      {
        unset($data['atags']);
      }
    }
  }
}
