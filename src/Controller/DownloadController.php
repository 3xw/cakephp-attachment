<?php
namespace Trois\Attachment\Controller;

use Trois\Attachment\Controller\AppController;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\BadRequestException;
use Cake\Core\Configure;
use Firebase\JWT\JWT;
use Trois\Attachment\Utility\Token;
use Trois\Attachment\Filesystem\Downloader;
use Cake\Event\EventInterface;
class DownloadController extends AppController
{
  /**
   * Issue a JWT for the Rust `zipper` microservice (cf. #11 / WGRC-419).
   *
   * Resolves the incoming attachment UUIDs to:
   *   - the full S3 key (prefix + stored path)
   *   - the user-facing original filename (`attachments.name`) which becomes
   *     the entry name inside the zip
   *
   * Payload : { files: [{key, name}], filename, exp }. Signed with
   * JWT_ZIPPER_SECRET (not the app SECURITY_SALT) so the zipper can verify
   * without knowing anything else about the app.
   */
  public function getZipToken()
  {
    if (!$this->getRequest()->is('post')) throw new BadRequestException('Post Needed');
    $ids = $this->getRequest()->getData('files');
    if (!is_array($ids)) $ids = [];
    $ids = array_values(array_filter($ids, 'is_string'));

    if (empty($ids)) {
      $token = '';
    } else {
      $Attachments = $this->fetchTable('Trois/Attachment.Attachments');
      $rows = $Attachments->find()
        ->where(['Attachments.id IN' => $ids])
        ->select(['id', 'path', 'name'])
        ->all()
        ->toList();

      $prefix = (string)env('ATTACHMENT_S3_PREFIX', '');
      $files = [];
      foreach ($rows as $r) {
        if (empty($r->path)) continue;
        $files[] = [
          'key' => $prefix . $r->path,
          'name' => (string)($r->name ?: basename((string)$r->path)),
        ];
      }

      if (empty($files)) {
        $token = '';
      } else {
        $secret = (string)env('JWT_ZIPPER_SECRET', Configure::read('Zipper.secret', ''));
        if ($secret === '') throw new \RuntimeException('JWT_ZIPPER_SECRET not configured');
        $ttl = (int)env('ZIPPER_TOKEN_TTL', 3600);
        $filename = (string)($this->getRequest()->getData('filename') ?: 'download.zip');
        $token = JWT::encode([
          'files' => $files,
          'filename' => $filename,
          'exp' => time() + $ttl,
        ], $secret, 'HS256');
      }
    }
    $this->set('token', $token);
    $this->viewBuilder()->setClassName('Json');
    $this->viewBuilder()->setOption('serialize', ['token']);
  }
  // files() (zip multi-fichiers in-PHP) supprimé en v6.
  // Le zip est désormais produit par le microservice Rust `zipper` (cf. #11 / WGRC-419).
  // Le front consume `getZipToken` puis redirige vers /download/zip (nginx → zipper).
  public function getFileToken()
  {
    if (!$this->getRequest()->is('post')) throw new BadRequestException('Post Needed');
    if(empty($this->getRequest()->getData('file'))) $this->set('token', '');
    else $this->set('token', (new Token)->encode(['file' => $this->getRequest()->getData('file')]));
    $this->viewBuilder()->setClassName('Json');
    $this->viewBuilder()->setOption('serialize', ['token']);
  }
  // (new Token)->encode(['file' => $attachment->id])
  public function file()
  {
    $token = $this->getRequest()->getQuery('token');
    if (empty($token)) throw new BadRequestException('Token required');
    // get Attachment
    $attachment = $this->fetchTable('Trois/Attachment.Attachments')->find()
    ->where(['id' => (new Token)->decode($token)->file])
    ->firstOrFail();
    // serve. Use basename(path) so the saved filename is the canonical
    // storage name (preserves original casing + extension as stored), not
    // the user-supplied `name` which may have been edited.
    $response = $this->response->withFile((new Downloader)->download($attachment));
    $response = $response->withHeader('Content-Type', $attachment->type.'/'.$attachment->subtype);
    $response = $response->withDownload(basename((string)$attachment->path));
    return $response;
  }

  /**
   * Stream file inline for preview (videos, PDFs)
   * Unlike file() which forces download, this renders inline in browser
   */
  public function stream()
  {
    $token = $this->getRequest()->getQuery('token');
    if (empty($token)) throw new BadRequestException('Token required');
    // get Attachment
    $attachment = $this->fetchTable('Trois/Attachment.Attachments')->find()
    ->where(['id' => (new Token)->decode($token)->file])
    ->firstOrFail();
    // serve file inline (not as download)
    $response = $this->response->withFile((new Downloader)->download($attachment));
    $response = $response->withHeader('Content-Type', $attachment->type.'/'.$attachment->subtype);
    // Inline disposition for browser preview
    $response = $response->withHeader('Content-Disposition', 'inline; filename="' . $attachment->name . '"');
    return $response;
  }
}
