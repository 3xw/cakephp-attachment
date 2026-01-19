<?php
namespace Trois\Attachment\Model\Entity;
use Cake\ORM\Entity;
use Cake\Core\Configure;
use Trois\Attachment\Filesystem\ProfileRegistry;
class Attachment extends Entity
{
  protected array $_accessible = [
    '*' => true,
    'id' => false,
  ];
  protected $_virtual = ['mime','url','thumb_params', 'filename'];
  protected function _getUrl()
  {
    return ProfileRegistry::retrieve($this->profile)->getUrl($this->path);
  }
  protected function _getThumbParams()
  {
    $url = ProfileRegistry::retrieve($this->profile)->thumbProfile()->getUrl($this->path);
    return strrpos($url, '?') === false ? '': substr($url, strrpos($url, '?'));
  }
  protected function _getFullpath()
  {
    return ProfileRegistry::retrieve($this->profile)->getFullPath($this->path);
  }
  protected function _getMime()
  {
    return $this->_fields['type'].'/'. $this->_fields['subtype'];
  }

  protected function _getFilename()
  {
    $nameFields = Configure::read('Trois/Attachment.browse.download.filename');

    if(count($nameFields) === 0) return $this->_fields['path'];

    $filename = '';
    foreach ($nameFields as $field) {
      if(isset($this->_fields[$field]) && !empty($this->_fields[$field])){
        if(is_object($this->_fields[$field]) && $this->_fields[$field] instanceof \Cake\I18n\FrozenTime){
          $filename .= $this->_fields[$field]->format('Y-m-d') . '_';
        } elseif(is_string($this->_fields[$field])) {
          $filename .= $this->_fields[$field] . '_';
        }
      }
    }

    $filename .= $this->_fields['path'];

    return $filename;
  }
}
