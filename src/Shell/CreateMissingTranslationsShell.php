<?php
namespace Trois\Attachment\Shell;

use Cake\Core\Configure;
use Cake\Utility\Inflector;
use Cake\Console\Shell;

class CreateMissingTranslationsShell extends Shell
{
  public function main(...$locales)
  {
    //check config
    if(!Configure::read('Attachment.translate')) return $this->err('You did not Attachment.translate to "true" !');

    // check args
    if(empty($locales)) return $this->err('need to pass a least one locale ex: "$ bin/cake CreateMissingTranslations en_US de_DE"');

    // build query
    $this->loadModel('Attachments');
    $query = $this->Attachments->find()->select(['Attachments.id','Attachments.title','Attachments.description']);

    // ensure locales exist and add if exists...
    $this->loadModel('I18n');
    foreach ($locales as $locale)
    {
      $firstLocaleRow = $this->I18n->find()->where(['locale' => $locale])->first();
      if(empty($firstLocaleRow)) return $this->err('locale "'.$locale.'" is not present in table i18n !');

      $alias = Inflector::camelize($locale);
      $query->select([$alias.'.id'])
      ->leftJoin([$alias =>'i18n'],[$alias.'.model' => 'Attachments', $alias.'.foreign_key = Attachments.id',$alias.'.locale' => $locale,$alias.'.field' => 'title'])
      ->where([$alias.'.id IS NULL']);
    }

    // to array
    $lonelyChilds = $query->toArray();
    if(empty($lonelyChilds)) return $this->info('There is not an attachment without its realted transaltions :=)');

    // create related records
    $entities = [];
    $fields = ['title','description'];
    foreach($lonelyChilds as $lonelyChild)
    {
      foreach ($locales as $locale)
      {
        foreach($fields as $field)
        {
          $entities[] = [
            'locale' => $locale,
            'model' => 'Attachments',
            'foreign_key' => $lonelyChild->id,
            'field' => $field,
            'content' => ''
          ];
        }
      }
    }

    $entities = $this->I18n->newEntities($entities);
    $results = $this->I18n->saveMany($entities);
    if(empty($results))
    {
      return $this->err('No record was added, an error occured!');
    }else
    {
      $this->info(count($results).' records were added for '.count($lonelyChilds).' lonely attachments not transalted...');
    }
  }
}
