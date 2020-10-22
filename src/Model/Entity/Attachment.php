<?php
namespace Trois\Attachment\Model\Entity;
use Cake\ORM\Entity;
use Trois\Attachment\Filesystem\ProfileRegistry;
class Attachment extends Entity
{
  protected $_accessible = [
    '*' => true,
    'id' => false,
  ];
  protected $_virtual = ['mime','url','thumb_params'];
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
}
