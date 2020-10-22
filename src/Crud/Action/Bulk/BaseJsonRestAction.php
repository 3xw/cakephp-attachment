<?php
declare(strict_types=1);

namespace Trois\Attachment\Crud\Action\Bulk;

use Cake\Controller\Controller;
use Cake\ORM\Query;
use Crud\Action\Bulk\BaseAction;
use Crud\Event\Subject;
use Cake\Http\Response;

abstract class BaseJsonRestAction extends BaseAction
{
  protected $subject = null;

  protected function _handle(): Response
  {
    // retrieve data
    $arrayOfData = $this->_controller()->getRequest()->input('json_decode', true);
    $ids = [];
    $data = [];
    $pk = $this->_table()->getPrimaryKey();
    foreach ((array) $arrayOfData as $jsonDecodedEntity)
    {
      $ids[] = $key = $jsonDecodedEntity[$pk];
      $data[$key] = $jsonDecodedEntity;
    }

    // get subject...
    $this->subject = $this->_constructSubject($ids);

    // modify subject Query AND add data
    $this->subject->set(['data' => $data]);
    $this->subject->query->mapReduce(
      function($entity, $key, $mp) use($pk)
      {
        $mp->emitIntermediate($entity, $entity->{$pk});
      },
      function($entities, $key, $mp)
      {
        $mp->emit($entities[0], $key);
      }
    );

    // store subject!!

    $event = $this->_trigger('beforeBulk', $this->subject);
    if ($event->isStopped()) return $this->_stopped($this->subject);

    if ($this->_bulk($this->subject->query)) $this->_success($this->subject);
    else $this->_error($this->subject);

    return $this->_redirect($this->subject, ['action' => 'index']);
  }
}
