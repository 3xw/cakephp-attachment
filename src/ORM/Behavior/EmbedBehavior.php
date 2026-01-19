<?php
namespace Trois\Attachment\ORM\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Exception;
use Cake\Utility\Inflector;
use Cake\Datasource\EntityInterface;
use Cake\Http\Client;

use Cake\ORM\Behavior;
use Cake\ORM\Table;

/**
* Storage behavior
*/
class EmbedBehavior extends Behavior
{

  /**
  * Default configuration.
  *
  * @var array
  */
  protected array $_defaultConfig = [
    'embed_field' => 'embed',
    'file_field' => 'path'
  ];

  /**
  * Build the behaviour
  *
  * @param array $config Passed configuration
  * @return void
  */
  public function initialize(array $config):void
  {
    parent::initialize($config);
    // check for a datafield field (there is no default)
    if (
      empty($this->getConfig('embed_field')) ||
      empty($this->getConfig('file_field'))
    ) throw new Exception('Must specify a embed_field and a file_field for EmbedBehavior');
  }

  public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
  {
    $settings = $this->getConfig();
    $embed_field = $settings['embed_field'];
    $file_field = $settings['file_field'];
    if (!empty($data[$embed_field]))
    {
      $type = 'embed';
      $subType = 'other';
      preg_match('~soundcloud~i', $data[$embed_field], $matches);
      if (!empty($matches))
      {
        $subType = 'soundcloud';
      }
      preg_match('~vimeo~i', $data[$embed_field], $matches);
      if (!empty($matches))
      {
        $subType = 'vimeo';
      }
      preg_match('~youtu~i', $data[$embed_field], $matches);
      if (!empty($matches))
      {
        $subType = 'youtube';
      }
      $data['type'] = $type;
      $data['subtype'] = $subType;
      $data['size'] = 0;

      // try to get image from vimeo or youtube
      if ($subType == 'youtube')
      {
        preg_match('~/embed/([0-9a-zA-Z_!\-]+)~i', $data[$embed_field], $matches);
        if (!empty($matches))
        {
          $data[$file_field] = 'http://img.youtube.com/vi/' . $matches[1] . '/0.jpg'; //$matches[1];
        }
      }
      if ($subType == 'vimeo')
      {
        preg_match('~/video/([0-9a-zA-Z_!\-]+)~i', $data[$embed_field], $matches);
        if (!empty($matches))
        {
          $imgid = $matches[1];
          $http = new Client();
          $hash = unserialize( $http->get('http://vimeo.com/api/v2/video/' . $imgid . '.php')->body );
          $data[$file_field] = $hash[0]['thumbnail_medium'];
        }
      }

      // extract only data we need!!
      $attributes = [
        'frameborder' => '"0"'
      ];

      $testAttributes = [
        'src' => '/src="([^"]+)"/',
        'height' => '/height="([^"]+)"/',
        'width' => '/width="([^"]+)"/',
        'scrolling' => '/scrolling="([^"]+)"/',
        'allowfullscreen' => '/allowfullscreen="([^"]+)"/'
      ];

      foreach($testAttributes as $key => $test){
        preg_match($test, $data[$embed_field], $match);
        if( !empty($match) && count($match) > 1) $attributes[$key] = "\"$match[1]\"";
      }

      // replace with cleaned iframe
      $data[$embed_field] = sprintf('<iframe %s></iframe>',http_build_query($attributes,'',' '));

      // md5
      $data['md5'] = md5($data[$embed_field]);

      $data['profile'] = 'external';
    }
  }
}
