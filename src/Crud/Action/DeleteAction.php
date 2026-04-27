<?php
declare(strict_types=1);

namespace Trois\Attachment\Crud\Action;

use Cake\Http\Response;
use Crud\Event\Subject;

class DeleteAction extends \Crud\Action\DeleteAction
{
  protected function _post(string|int|null $id = null): ?Response
  {
    $subject = $this->_subject();
    $subject->set(['id' => $id]);

    $entity = $this->_findRecord($id, $subject);

    $event = $this->_trigger('beforeDelete', $subject);
    if ($event->isStopped()) {
      return $this->_stopped($subject);
    }

    try {
      if ($this->_model()->delete($entity)) {
        $this->_success($subject);
      } else {
        $this->_error($subject);
      }
    } catch (\PDOException $e) {
      $this->_controller()->set('success', false);
      $this->_controller()->set('data', [
        'id' => $id,
        'status' => false,
        'code' => 400,
        'exception' => $e,
        'message' => __d('Trois/Attachment', 'unable to delete this Attachment. This attachment looks beeing used by an other record. Please detatch the attachment to related record an then try to delete it again.'),
      ]);
      $this->_controller()->viewBuilder()->setOption('serialize', ['success', 'data']);
      $this->_error($subject);
    }

    return $this->_redirect($subject, ['action' => 'index']);
  }
}
