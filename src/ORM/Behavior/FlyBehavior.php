<?php
namespace Trois\Attachment\ORM\Behavior;

use ArrayObject;
use Exception;
use Psr\Http\Message\UploadedFileInterface;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Http\Session;
use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Trois\Attachment\Filesystem\Profile;
use Trois\Attachment\Filesystem\UploadedFile;
use League\Flysystem\FileNotFoundException;
use Cake\Log\Log;
use Trois\Attachment\Filesystem\ProfileRegistry;

class FlyBehavior extends Behavior
{

  protected array $_defaultConfig = [
    'file_field' => 'path',
    'sessionControl' => true
  ];

  protected ?object $_file = null;

  protected bool $_fileToRemove = false;

  protected string $_uuid = '';

  protected ?object $_session = null;

  public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
  {
    $settings = $this->getConfig();
    $field = $settings['file_field'];

    // rest for bulk;
    $this->_file = null;

    if (!empty($data[$field]) && ($data[$field] instanceof UploadedFileInterface))
    {
      if ($data[$field]->getError() !== UPLOAD_ERR_OK)
      {
        $event->stopPropagation();
        return false;
      }

      $this->_file = new UploadedFile($data[$field]);

      $data[$field] = $this->_file->getPath();

      // set uuid if exists
      $this->_uuid = empty($data['uuid'])? '' : $data['uuid'];

      // meta
      $data['meta'] = json_encode($this->_file->getMetadata());

      // md5
      $data['md5'] = md5_file($this->_file->getPath());

      $types = explode('/', $this->_file->getClientMediaType());
      $data['type'] = $types[0];
      $data['subtype'] = $types[1];
      $data['size'] = $this->_file->getSize();

      // date...
      if (!isset($data['date'])) $data['date'] = date('Y-m-d H:i:s');

      // name of file...
      if (!isset($data['name']) || $data['name'] == '') $data['name'] = $this->_file->getClientFilename();
    }
  }

  public function beforeSave(Event $event, EntityInterface $entity, ArrayObject $options)
  {
    $settings = $this->getConfig();
    $field = $settings['file_field'];
    $orginalValues = $entity->extractOriginalChanged([$field,'profile','md5']);

    if (!empty($this->_file))
    {
      // TYPE
      $type = explode('/', $this->_file->getClientMediaType());
      $subtype = $type[1];
      $type = $type[0];

      // GET CONFIG
      if($this->getConfig('sessionControl') && PHP_SAPI !== 'cli')
      {
        $this->_session = new Session();
        $sessionAttachment = $this->_session->read('Trois/Attachment.'.$this->_uuid);
        if(!$sessionAttachment)
        {
          $event->stopPropagation();
          $entity->setError($field,['Attachment keys not found in session! Please pass Attachment settings throught session!']);
          return false;
        }
        $conf = array_merge($sessionAttachment, $settings);
      }
      else $conf = array_merge(Configure::read('Trois/Attachment.upload'), $settings);

      // CHECK type
      if (!in_array($this->_file->getClientMediaType(), $conf['types']))
      {
        $event->stopPropagation();
        $entity->setError($field,['This file type is not suported!']);
        return false;
      }

      // CHECK Size
      if ($conf['maxsize'] * ( 1024 * 1024 ) < $this->_file->getSize())
      {
        $event->stopPropagation();
        $entity->setError($field,['This file is too large max size is : '  . ( $conf['maxsize']  ) .' MB']);
        return false;
      }

      // add image meta
      if(in_array($this->_file->getClientMediaType(), ['image/jpeg','image/png','image/gif']))
      {
        $image_info = getimagesize($this->_file->getPath());
        $image_width = $image_info[0];
        $image_height = $image_info[1];
        $entity->set('width', $image_width);
        $entity->set('height', $image_height);
        unset($img);
      }

      // manage existing file...
      $afterReplace = null;
      if(!empty($orginalValues[$field]))
      {
        $oldProfile = ProfileRegistry::retrieve(empty($orginalValues['profile'])? $conf['profile']: $orginalValues['profile']);

        // Meme filet que sur la suppression : un ancien fichier deja absent du
        // stockage ne doit pas empecher d'en televerser un nouveau. Sans cela
        // Flysystem levait ici, et le remplacement echouait entierement - le
        // document restait donc introuvable a la mise a jour.
        try {
          $oldProfile->deleteThumbnails($orginalValues[$field]);
        } catch (FileNotFoundException $e) {
          // Les vignettes sont regenerables : leur absence ne dit rien d'anormal.
        }

        try {
          $oldProfile->delete($orginalValues[$field]);
        } catch (FileNotFoundException $e) {
          Log::info(sprintf(
            'Remplacement de %s : l\'ancien fichier avait deja disparu du stockage.',
            (string)$orginalValues[$field]
          ));
        }

        $afterReplace = $oldProfile->afterReplace;
      }

      // store
      $profile = ProfileRegistry::retrieve($conf['profile']);
      $entity->set('profile', $profile->name);

      // name & dir
      $name = strtolower( time() . '_' . preg_replace('/[^a-z0-9_.]/i', '', $this->_file->getClientFileName()) );
      $dir = $this->_resolveDir($conf['dir'],$type,$subtype);

      // if replace on edit in profile
      if(!empty($orginalValues[$field]) && $profile->replaceExisting)
      {
        $name = $orginalValues[$field];
        $dir = false;
      }

      // write
      $profile->store($this->_file->getPath(), $name, $dir, $conf['visibility'], $this->_file->getClientMediaType());

      // set entity
      $entity->{$field} = $dir? $dir.DS.$name: $name;

      // excute callback fct if needed
      if(is_callable($afterReplace) && !empty($orginalValues[$field]) && $profile->replaceExisting) $afterReplace($entity);
    }
  }

  protected function _resolveDir($dir,$type,$subtype)
  {
    if($dir === false || $dir === true) return false;
    return str_replace(
      ['{DS}','{$role}','{$username}','{$year}','{$month}','{$type}','{$subtype}'],
      [DS,$this->_session->read('Auth.User.role'),$this->_session->read('Auth.User.username'),date("Y"),date("m"),$type,$subtype],
      $dir
    );
  }

  public function afterDelete(Event $event, EntityInterface $entity, ArrayObject $options)
  {
    $settings = $this->getConfig();
    $field = $settings['file_field'];
    if(!empty($entity->get($field))) {
      $profile = ProfileRegistry::retrieve($entity->get('profile'));

      // Un fichier deja absent du stockage n'est pas une erreur : l'etat
      // recherche est atteint. Sans ce filet, Flysystem levait une exception
      // apres la suppression de l'enregistrement, qui restait donc en base -
      // et la piece jointe devenait impossible a retirer de la bibliotheque.
      try {
        $profile->deleteThumbnails($entity->get($field));
      } catch (FileNotFoundException $e) {
        // Les vignettes sont regenerables : leur absence ne dit rien d'anormal.
      }

      try {
        $profile->delete($entity->get($field));
      } catch (FileNotFoundException $e) {
        Log::info(sprintf(
          'Piece jointe %s supprimee : son fichier avait deja disparu du stockage.',
          (string)$entity->get($field)
        ));
      }
    }
  }

}
