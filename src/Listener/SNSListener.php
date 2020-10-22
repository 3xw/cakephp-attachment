<?php
namespace Trois\Attachment\Listener;

use Cake\Event\Event;
use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;
use Cake\Http\Exception\BadRequestException;

class SNSListener extends BaseListener
{
  protected $_defaultConfig = [
    'clientSettings' => [],
    'TopicArn' => 'arn:aws:sns:eu-central-1::myTopic',
    'data' => []
  ];

  public function respond(Event $event)
  {
    if($event->getSubject()->success === false) return;

    $subject = $event->getSubject();
    $SnSclient = new SnsClient($this->getConfig('clientSettings'));
    $message = json_encode(array_merge(['subject' => $subject], $this->getConfig('data')));
    $topic = $this->getConfig('TopicArn');

    // send message
    try {
      $result = $SnSclient->publish([
        'Message' => $message,
        'TopicArn' => $topic,
      ]);
    } catch (AwsException $e)
    {
      // output error message if fails
      throw new BadRequestException($e->getMessage());
    }
  }
}
