<?php
namespace Trois\Attachment\Model\Filter;

use Search\Model\Filter\FilterCollection;

class AttachmentCollection extends FilterCollection
{
  public function initialize():void
  {
    $this
    ->add('uuid', 'Trois/Attachment.SessionControl') // wrapps query with session settings throught uuid
    ->add('type', 'Trois/Attachment.Restriction', ['restrictions' => ['types_restricted']])
    ->add('atags', 'Trois/Attachment.Restriction', ['restrictions' => ['tag_restricted']])
    ->add('search', 'Trois/Attachment.Search')
    ->add('date', 'Trois/Attachment.Date')
    ->add('filters', 'Trois/Attachment.Filters');
  }
}
