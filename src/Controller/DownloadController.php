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
   * Payload : { files: string[], filename: string, exp: int }.
   * Signed with JWT_ZIPPER_SECRET (NOT the app SECURITY_SALT) so the zipper
   * can verify it without knowing anything else about the app.
   */
  public function getZipToken()
  {
    if (!$this->getRequest()->is('post')) throw new BadRequestException('Post Needed');
    $files = $this->getRequest()->getData('files');

    if (empty($files)) {
      $token = '';
    } else {
      $secret = (string)env('JWT_ZIPPER_SECRET', Configure::read('Zipper.secret', ''));
      if ($secret === '') throw new \RuntimeException('JWT_ZIPPER_SECRET not configured');
      $ttl = (int)env('ZIPPER_TOKEN_TTL', 3600);
      $filename = (string)($this->getRequest()->getData('filename') ?: 'download.zip');
      $token = JWT::encode([
        'files' => array_values($files),
        'filename' => $filename,
        'exp' => time() + $ttl,
      ], $secret, 'HS256');
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
    // serve
    $response = $this->response->withFile((new Downloader)->download($attachment));
    $response = $response->withHeader('Content-Type', $attachment->type.'/'.$attachment->subtype);
    $response = $response->withDownload($attachment->name);
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
