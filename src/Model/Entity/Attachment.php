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
  protected array $_virtual = ['mime','url','thumb_params', 'filename'];
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
    $base = $this->_displayBasename();
    $nameFields = (array) Configure::read('Trois/Attachment.browse.download.filename');
    if (count($nameFields) === 0) return $base;

    $parts = [];
    foreach ($nameFields as $field) {
      $value = $this->_fields[$field] ?? null;
      if (empty($value)) continue;
      if ($value instanceof \DateTimeInterface) {
        $parts[] = $value->format('Y-m-d');
      } elseif (is_object($value) && method_exists($value, 'format')) {
        // Cake\I18n\Date / ChronosDate (not a DateTimeInterface under Chronos 3).
        $parts[] = $value->format('Y-m-d');
      } elseif (is_string($value)) {
        $parts[] = $value;
      }
    }

    // No usable metadata → the file name alone (WGRC-744 behavior).
    if (count($parts) === 0) return $base;

    $name = $this->_sanitizeFilename(implode('_', $parts));
    if ($name === '') return $base;
    // The file name keeps the extension. It is NOT unique (13 different
    // `pano0001.jpg` share one title + date): zips go through
    // ZipEntryNames::dedupe().
    return $name . '_' . $base;
  }

  /**
   * Storage basename without the upload timestamp FlyBehavior prepends
   * (`1790085788_pano_0024.jpg` → `pano_0024.jpg`): noise for a human, and
   * the meaningful date is already in the name. Kept when stripping would
   * leave a bare extension (`1790085788_.jpg`).
   */
  protected function _displayBasename(): string
  {
    $base = basename((string)($this->_fields['path'] ?? ''));
    return preg_replace('/^\d{10}_(?=[^.])/', '', $base) ?? $base;
  }

  /**
   * Make a metadata-derived string safe as a Content-Disposition filename:
   * strip path separators + characters illegal on common filesystems, collapse
   * whitespace, trim. Accents and single spaces are preserved on purpose.
   */
  protected function _sanitizeFilename(string $value): string
  {
    $value = preg_replace('#[/\\\\:*?"<>|\x00-\x1F]#', '', $value);
    $value = preg_replace('/\s+/u', ' ', $value);
    return trim($value, " .\t");
  }
}
