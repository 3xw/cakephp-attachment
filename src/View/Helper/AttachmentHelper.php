<?php
namespace Attachment\View\Helper;
use Cake\View\Helper;
use Cake\View\View;
use Attachment\Filesystem\FilesystemRegistry;
use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\Utility\Text;
use Cake\Utility\Inflector;
use Attachment\Filesystem\ProfileRegistry;
class AttachmentHelper extends Helper
{
  const OPEN = 'open';
  const TAG_RESTRICTED = 'tag_restricted';
  const TAG_OR_RESTRICTED = 'tag_or_restricted';
  const TYPES_RESTRICTED = 'types_restricted';
  const USER_RESTRICTED = 'user_restricted';
  const USER_OR_NO_ONE_RESTRICTED = 'user_or_no_one_restricted';

  public $helpers = ['Url','Html'];
  private $_filesystems = [];
  private $_inputComponentCount = 0;
  private $_version = false;

  private function _initComponentAndSession()
  {
    // only once : )
    $this->_inputComponentCount++;
    if($this->_inputComponentCount > 1) return;

    // init
    $this->_View->getRequest()->getSession()->write('Attachment', 'oui');
    $this->Html->css(['attachment/main.min.css'],['block' => true]);
    $this->Html->script(['attachment/main.min.js'],['block' => true]);
  }

  public function setup($field,$settings)
  {
    // compo and Session
    $this->_initComponentAndSession();

    // merge global with settings
    $settings = array_merge(Configure::read('Attachment.upload'), $settings);

    // attahment(s) selection
    if(empty($settings['attachments'])) $settings['attachments'] = [];

    // keys & session
    $uuid = Text::uuid();
    $this->_View->getRequest()->getSession()->write('Attachment.'.$uuid, $settings);

    $uuidField = str_replace('.', '', $field);
    $this->_View->getRequest()->getSession()->write('Attachment.'.$uuidField, $settings);


    // front side settings
    $settings['options'] = Configure::read('Attachment.options');
    $settings['uuid'] = $uuid;
    $settings['url'] = $this->Url->build('/');
    $settings['relation'] = ($field == 'Attachments')? 'belongsToMany' : 'belongsTo';
    $settings['field'] = ($field == 'Attachments')? '' : $field;
    $settings['label'] = empty($settings['label'])? Inflector::humanize($field) : $settings['label'];
    $settings['translate'] = Configure::read('Attachment.translate');
    $settings['i18n'] = [
      'enable' => Configure::read('Attachment.translate'),
      'languages' => Configure::read('I18n.languages'),
      'defaultLocale' => Configure::read('App.defaultLocale')
    ];

    // browse settings
    $settings['browse'] = Configure::read('Attachment.browse');

    // urls
    $profiles = Configure::read('Attachment.profiles');
    $settings['baseUrls'] = [];
    foreach($profiles as $key => $value) $settings['baseUrls'][$key] = [
      'profile' => Router::url($profiles[$key]['baseUrl'], true),
      'thumbnails' => !empty($profiles[$key]['thumbnails'])? Router::url($profiles[$profiles[$key]['thumbnails']]['baseUrl'], true): ''
    ];

    return $settings;
  }

  public function component($name, $props = [])
  {
    return $this->_View->Html->tag('attachment-loader', '', ['name' => $name, 'props' => json_encode($props)]);
  }

  public function index($settings = [])
  {
    // actions
    $settings = $this->setup('Attachments',array_merge(
      [
        'mode' => 'browse',
        'overlay' => false,
        'actions' => ['view','download'],
        'groupActions' => ['archive','edit','add','delete']
      ],
      $settings
    ));
    return $this->component('attachment-browse',['aid' => $settings['uuid'],':settings' => $settings]);
  }
  public function input($field, $settings = [])
  {
    $settings = $this->setup($field, array_merge(
      [
        'mode' => 'input',
        'overlay' => true,
        'actions' => ['view','download'],
        'groupActions' => ['select','add']
      ],
      $settings
    ));
    return $this->component('attachment-browse',['aid' => $settings['uuid'],':settings' => $settings]);
  }
  public function filesystem($profile)
  {
    if(empty($this->_filesystems[$profile]))
    {
      $this->_filesystems[$profile] = FilesystemRegistry::retrieve($profile);
    }
    return $this->_filesystems[$profile];
  }
  public function fullPath($attachment)
  {
    $baseUrl = Configure::read('Attachment.profiles.'.$attachment->profile.'.baseUrl');
    $start = substr($baseUrl,0 , 4);
    $baseUrl = ( $start == 'http' )? $baseUrl : Router::url($baseUrl, true);
    return $baseUrl.$attachment->path;
  }
  public function image($params, $attributes = null )
  {
    // src
    $src = $this->thumbSrc( $params );
    $html = '<img src="'. $src .'" ';
    $attributes = ( $attributes )? $attributes : [];
    foreach(  $attributes as $attribute => $value ){
      $html.='  '.$attribute.'="'.$value.'"';
    }
    $html .= ' />';
    // srcset!!
    if(!empty($params['srcset']) && is_array($params['srcset']) && (!empty($params['width']) || !empty($params['height'])))
    {
      // collect data
      $srcsets = $params['srcset'];
      unset($params['srcset']);
      $dim = !empty($params['width'])? 'width': 'height';
      $breakpoints = Configure::read('Attachment.thumbnails.breakpoints');
      //webp
      preg_match_all('/([a-zA-Z0-9_:\/.èüéöàä\$£ç\&%#*+?=,;~-]*\.png$|[a-zA-Z0-9_:\/.èüéöàä\$£ç\&%#*+?=,;~-]*\.PNG$)/', $params['image'], $noWebp, PREG_SET_ORDER);
      foreach($srcsets as $breakpoint => $values)
      {
        // normal
        $srcset = '';
        $newParams = $params;
        foreach($values as $ratio => $value)
        {
          $r = $ratio + 1;
          $newParams[$dim] = $value;
          $srcset .= $this->thumbSrc( $newParams ).' '.$r.'x, ';
        }
        $srcset = substr($srcset,0, -2);
        $type = empty($noWebp)? 'image/jpeg': 'image/png';
        $html = $this->Html->tag('source','',['srcset' => $srcset, 'media' => $breakpoints[$breakpoint], 'type' => $type]).$html;
        // webp
        $srcset = '';
        $newParams = $params;
        if(empty($noWebp))
        {
          foreach($values as $ratio => $value)
          {
            $r = $ratio + 1;
            $newParams[$dim] = $value;
            preg_match_all('/([a-zA-Z0-9_:\/.èüéöàä\$£ç\&%#*+?=,;~-]*)\.([a-zA-Z]{3,4})$/', $params['image'], $img, PREG_SET_ORDER);
            $newParams['image'] = $img[0][1].'.webp';
            $srcset .= $this->thumbSrc( $newParams ).' '.$r.'x, ';
          }
          $srcset = substr($srcset,0, -2);
          $html = $this->Html->tag('source','',['srcset' => $srcset, 'media' => $breakpoints[$breakpoint], 'type' => 'image/webp']).$html;
        }
      }
      $html = $this->Html->tag('picture',$html);
    }
    // normal stuff
    return $html;
  }
  public function thumbSrc($params)
  {
    if (substr($params['image'],0 , 4) == 'http' ) $profile = 'external';
    else $profile = empty($params['profile'])? 'external' : $params['profile'];
    $url = $profile . '/';
    $dims = ['height' => 'h','width' => 'w','align' => 'a', 'quality' => 'q'];
    foreach($dims as $key => $value) if (!empty($params[$key])) $url .= $value.$params[$key];
    if (!empty($params['cropratio'])) $url .= 'c'.str_replace(':','-',$params['cropratio']);
    $url = $url.'/'.$params['image'];
    return ProfileRegistry::retrieve($profile)->thumbProfile()->getUrl($url);
  }
}
