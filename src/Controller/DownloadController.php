<?php
namespace Trois\Attachment\Controller;

use Trois\Attachment\Controller\AppController;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\BadRequestException;
use Trois\Attachment\Utility\Token;
use Trois\Attachment\Filesystem\Downloader;
use Cake\Event\EventInterface;
class DownloadController extends AppController
{
  public function getZipToken()
  {
    if (!$this->getRequest()->is('post')) throw new BadRequestException('Post Needed');
    if(empty($this->getRequest()->getData('files'))) $this->set('token', '');
    else $this->set('token', (new Token)->encode(['files' => $this->getRequest()->getData('files')]));
    $this->viewBuilder()->setOption('serialize', ['token']);
  }
  // (new Token)->encode(['files' => [$attachment1->id, $attachment2->id]])
  public function files()
  {
    //check
    if (!$this->getRequest()->is('post')) throw new BadRequestException('Post Needed');
    if(!$token = $this->getRequest()->getData('token')) throw new BadRequestException('Url Form: pair token filed/value needed');
    // get Attachment
    $attachments = $this->loadModel('Trois/Attachment.Attachments')->find()
    ->where(['id IN' => (new Token)->decode($token)->files])
    ->toArray();
    // not found if empty
    if(empty($attachments)) throw new NotFoundException('Files not found');
    // serve
    $response = $this->response->withFile((new Downloader)->downloadZip($attachments));
    $response = $response->withHeader('Content-Type', 'application/zip');
    $response = $response->withDownload('archive.zip');
    return $response;
  }
  public function getFileToken()
  {
    if (!$this->getRequest()->is('post')) throw new BadRequestException('Post Needed');
    if(empty($this->getRequest()->getData('file'))) $this->set('token', '');
    else $this->set('token', (new Token)->encode(['file' => $this->getRequest()->getData('file')]));
    $this->viewBuilder()->setOption('serialize', ['token']);
  }
  // (new Token)->encode(['file' => $attachment->id])
  public function file($token)
  {
    // get Attachment
    $attachment = $this->loadModel('Trois/Attachment.Attachments')->find()
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
  public function stream($token)
  {
    // get Attachment
    $attachment = $this->loadModel('Trois/Attachment.Attachments')->find()
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
