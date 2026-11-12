<?php
declare(strict_types=1);

namespace Trois\Attachment\Test\TestCase\Model\Entity;

use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Trois\Attachment\Model\Entity\Attachment;

class AttachmentFilenameTest extends TestCase
{
  public function setUp(): void
  {
    parent::setUp();
    Configure::write('Trois/Attachment.browse.download.filename', ['title', 'date']);
  }

  private function filename(array $fields): string
  {
    return (new Attachment($fields))->filename;
  }

  public function testUploadTimestampIsDropped(): void
  {
    $this->assertSame('Locle ouest_2026-09-03_pano_0024.jpg', $this->filename([
      'path' => '1790088874_pano_0024.jpg',
      'title' => 'Locle ouest',
      'date' => new DateTime('2026-09-03 00:00:00'),
    ]));
  }

  public function testWithoutMetadataTheBareFileNameIsUsed(): void
  {
    $this->assertSame('pano_0024.jpg', $this->filename(['path' => '1790088874_pano_0024.jpg']));
  }

  public function testPathWithoutTimestampIsUnchanged(): void
  {
    $this->assertSame('Titre_legacy.pdf', $this->filename(['path' => 'legacy.pdf', 'title' => 'Titre']));
  }

  public function testOtherNumbersAreKept(): void
  {
    // Only a leading 10-digit stamp followed by `_` is the upload time.
    $this->assertSame('2024_06_rapport.pdf', $this->filename(['path' => '2024_06_rapport.pdf']));
    $this->assertSame('12345678901_x.jpg', $this->filename(['path' => '12345678901_x.jpg']));
  }

  public function testStampKeptWhenOnlyAnExtensionWouldRemain(): void
  {
    $this->assertSame('1790088874_.jpg', $this->filename(['path' => '1790088874_.jpg']));
  }

  public function testWithoutNamingConfigTheBareFileNameIsUsed(): void
  {
    Configure::write('Trois/Attachment.browse.download.filename', []);
    $this->assertSame('pano_0024.jpg', $this->filename(['path' => '1790088874_pano_0024.jpg', 'title' => 'X']));
  }
}
