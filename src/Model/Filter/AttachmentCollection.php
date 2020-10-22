<?php
namespace Trois\Attachment\Model\Filter;

use Search\Model\Filter\FilterCollection;

class AttachmentCollection extends FilterCollection
{
  public function initialize():void
  {
    $this
    ->add('uuid', 'Attachment.SessionControl') // wrapps query with session settings throught uuid
    ->add('type', 'Attachment.Restriction', ['restrictions' => ['types_restricted']])
    ->add('atags', 'Attachment.Restriction', ['restrictions' => ['tag_restricted']])
    ->add('search', 'Attachment.Search')
    ->add('date', 'Attachment.Date')
    ->add('filters', 'Attachment.Filters');
  }
}
