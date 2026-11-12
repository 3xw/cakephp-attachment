<?php
declare(strict_types=1);

namespace Trois\Attachment\Utility;

/**
 * Two entries with the same name in a zip overwrite each other (or prompt) on
 * extraction. Download names come from title + date + file name and do
 * collide, so later duplicates get `-2`, `-3`… before the extension.
 * Case-insensitive: macOS and Windows extract onto case-insensitive disks.
 */
final class ZipEntryNames
{
  /**
   * @param list<array{key: string, name: string}> $files
   * @return list<array{key: string, name: string}>
   */
  public static function dedupe(array $files): array
  {
    $taken = [];
    $out = [];
    foreach ($files as $file) {
      $name = $file['name'];
      $dot = strrpos($name, '.');
      $hasExt = $dot !== false && $dot > 0;
      $stem = $hasExt ? substr($name, 0, $dot) : $name;
      $ext = $hasExt ? substr($name, $dot) : '';

      $candidate = $name;
      for ($i = 2; isset($taken[mb_strtolower($candidate)]); $i++) {
        $candidate = $stem . '-' . $i . $ext;
      }
      $taken[mb_strtolower($candidate)] = true;
      $out[] = array_merge($file, ['name' => $candidate]);
    }

    return $out;
  }
}
