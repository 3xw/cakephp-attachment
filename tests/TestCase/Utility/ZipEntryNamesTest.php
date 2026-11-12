<?php
declare(strict_types=1);

namespace Trois\Attachment\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use Trois\Attachment\Utility\ZipEntryNames;

class ZipEntryNamesTest extends TestCase
{
  public function testUniqueNamesAreUntouched(): void
  {
    $files = [['key' => 'a', 'name' => 'a.jpg'], ['key' => 'b', 'name' => 'b.jpg']];
    $this->assertSame($files, ZipEntryNames::dedupe($files));
  }

  public function testDuplicatesGetASuffixBeforeTheExtension(): void
  {
    $names = array_column(ZipEntryNames::dedupe([
      ['key' => '1', 'name' => 'Locle ouest_2024-11-29_pano0001.jpg'],
      ['key' => '2', 'name' => 'Locle ouest_2024-11-29_pano0001.jpg'],
      ['key' => '3', 'name' => 'Locle ouest_2024-11-29_pano0001.jpg'],
    ]), 'name');
    $this->assertSame([
      'Locle ouest_2024-11-29_pano0001.jpg',
      'Locle ouest_2024-11-29_pano0001-2.jpg',
      'Locle ouest_2024-11-29_pano0001-3.jpg',
    ], $names);
  }

  public function testComparisonIsCaseInsensitive(): void
  {
    $names = array_column(ZipEntryNames::dedupe([
      ['key' => '1', 'name' => 'Pano.JPG'],
      ['key' => '2', 'name' => 'pano.jpg'],
    ]), 'name');
    $this->assertSame(['Pano.JPG', 'pano-2.jpg'], $names);
  }

  public function testGeneratedSuffixNeverCollidesWithAnExistingName(): void
  {
    $names = array_column(ZipEntryNames::dedupe([
      ['key' => '1', 'name' => 'x-2.jpg'],
      ['key' => '2', 'name' => 'x.jpg'],
      ['key' => '3', 'name' => 'x.jpg'],
    ]), 'name');
    $this->assertSame(['x-2.jpg', 'x.jpg', 'x-3.jpg'], $names);
  }

  public function testNamesWithoutExtensionAndDotfiles(): void
  {
    $names = array_column(ZipEntryNames::dedupe([
      ['key' => '1', 'name' => 'README'],
      ['key' => '2', 'name' => 'README'],
      ['key' => '3', 'name' => '.env'],
      ['key' => '4', 'name' => '.env'],
    ]), 'name');
    $this->assertSame(['README', 'README-2', '.env', '.env-2'], $names);
  }

  public function testKeysAreKept(): void
  {
    $out = ZipEntryNames::dedupe([['key' => 'k1', 'name' => 'a.jpg'], ['key' => 'k2', 'name' => 'a.jpg']]);
    $this->assertSame(['k1', 'k2'], array_column($out, 'key'));
  }
}
