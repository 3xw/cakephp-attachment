<?php
namespace Trois\Attachment\Model\Filter;

use Search\Model\Filter\Base;

class Search extends Base
{
  public function process():bool
  {
    // retrive data
    $value = $this->getArgs()[$this->getConfig('name')];
    if(empty($value)) return true;

    $needle = '%'.trim($value).'%';
    $this->getQuery()
    ->distinct('Attachments.id')
    ->leftJoin(['AAtags' => 'attachments_atags'],['AAtags.attachment_id = Attachments.id'])
    ->leftJoin(['Atags' => 'atags'],['Atags.id = AAtags.atag_id'])
    ->where([
      'OR' => [
        'Atags.name LIKE' => $needle,
        'Atags.slug LIKE' => $needle,
        'Attachments.title LIKE' => $needle,
        'Attachments.description LIKE' => $needle,
        'Attachments.author LIKE' => $needle,
        'Attachments.copyright LIKE' => $needle,
        'Attachments.name LIKE' => $needle
      ]
    ]);

    return true;
  }
}
