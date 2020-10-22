<?php
namespace Attachment\ORM\Behavior;

use DateTime;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\Behavior;

/**
* External behavior
*/
class ExternalBehavior extends Behavior
{
  public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
  {
    if(!empty($data['path']) && is_a($data['path'], '\Zend\Diactoros\UploadedFile')) return;

    if (!empty($data['path']) && substr($data['path'], 0, 4) == 'http' && empty($data['md5']))
    {
      // urlencode if needed
      $pathPieces = explode('/',$data['path']);
      $fileName = array_pop($pathPieces);
      $data['path'] = implode('/',$pathPieces).'/';
      $data['path'] .= (strpos($fileName,'%') === false )? urlencode($fileName): $fileName;

      $headers = get_headers($data['path'],1);
      if(substr($headers[0], 9, 3) != 200) return;
      $data['meta'] = json_encode($headers);
      $pathPieces = explode('/',$data['path']);
      $data['name'] = empty($data['name'])? urldecode($fileName): $data['name'];
      $data['type'] = explode('/',$headers['Content-Type'])[0];
      $data['subtype'] = explode('/',$headers['Content-Type'])[1];
      $data['md5'] = md5($data['path']);
      $data['size'] = $headers['Content-Length'];
      $data['date']= new DateTime($headers['Date']);
      $data['profile'] = 'external';
    }
  }
}
