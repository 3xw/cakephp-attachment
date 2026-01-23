<?php
namespace Trois\Attachment\Filesystem;

use Cake\Core\Configure;
use Trois\Attachment\Filesystem\FilesystemRegistry;
use Trois\Attachment\Filesystem\Protect\ProtectionRegistry;
use Cake\Core\InstanceConfigTrait;
use Cake\Routing\Router;
use Cake\Http\ServerRequest;

class Profile
{
  use InstanceConfigTrait;

  public $name;

  /**
   * Magic getter for profile configuration properties.
   * Maps property names to their corresponding config keys.
   *
   * @param string $name Property name
   * @return mixed|null Config value or null if not found
   */
  public function __get(string $name): mixed
  {
    $map = [
      'replaceExisting' => 'replace',
      'afterReplace' => 'afterReplace',
    ];

    if (isset($map[$name])) {
      return $this->getConfig($map[$name]);
    }

    return null;
  }

  protected array $_defaultConfig = [
    'adapter' => null,
    'client' => null,
    'baseUrl' =>  null,
    'delete' => true,
    'replace' => false,
    'afterReplace' => null, // null | callback fct($entity)
    'thumbProtection' => null,
    'protection' => null,
    'thumbnails' => 'thumbnails',
    'keep' => true
  ];

  function __construct(string $alias, array $config = [])
  {
    $this->name = $alias;
    $this->setConfig($config);
  }

  public function filesystem()
  {
    return FilesystemRegistry::retrieve($this->name);
  }

  public function thumbProfile()
  {
    return ProfileRegistry::retrieve($this->getConfig('thumbnails'));
  }

  public function hasProtection()
  {
    return ProtectionRegistry::exists($this->name);
  }

  public function protection()
  {
    return ProtectionRegistry::retrieve($this->name);
  }

  public function verify(ServerRequest $request): bool
  {
    if (!$this->hasProtection()) return true;
    return $this->protection()->verify($request);
  }

  public function getFullPath($path)
  {
    return $this->filesystem()->getAdapter()->applyPathPrefix($path);
  }

  public function getUrl($path)
  {
    $url = $this->getBaseUrl().$path;
    if ($this->hasProtection()) $url = $this->protection()->getSignedUrl($this->getFullPath($path), $this->getBaseUrl());
    return $url;
  }

  public function getBaseUrl()
  {
    if (!$this->getConfig('baseUrl')) return Router::url('/', true);

    return ( substr($this->getConfig('baseUrl'),0 , 4) == 'http' )? $this->getConfig('baseUrl') : Router::url($this->getConfig('baseUrl'), true);
  }

  public function store($tmp, $name, $dir = false, $visibility = 'public', $mimetype = 'text/plain')
  {
    // resolve dir...
    if(!empty($dir))
    {
      $this->filesystem()->createDir($dir);
      $name = $dir.DS.$name;
    }

    // delete if exists
    if($this->filesystem()->has($name)) $this->delete($name, true);

    // store file
    $stream = fopen($tmp, 'r+');
    $this->filesystem()->writeStream($name, $stream,[
      'visibility' => $visibility,
      'mimetype' => $mimetype
    ]);
    while(is_resource($stream)) fclose($stream);
  }

  public function write($path, $contents)
  {
    return $this->filesystem()->write($path, $contents);
  }

  public function writeStream($path, $resource)
  {
    return $this->filesystem()->writeStream($path, $resource);
  }

  public function update($path, $contents)
  {
    return $this->filesystem()->update($path, $contents);
  }

  public function updateStream($path, $resource)
  {
    return $this->filesystem()->updateStream($path, $resource);
  }

  public function put($path, $contents)
  {
    return $this->filesystem()->put($path, $contents);
  }

  public function putStream($path, $resource)
  {
    return $this->filesystem()->putStream($path, $resource);
  }

  public function read($path)
  {
    return $this->filesystem()->read($path);
  }

  public function readStream($path)
  {
    return $this->filesystem()->readStream($path);
  }

  public function has($path)
  {
    return $this->filesystem()->has($path);
  }

  public function delete($file, $force = false)
  {
    if(($force || $this->getConfig('delete'))) $this->filesystem()->delete($file);
  }

  /**
   * Delete all thumbnails for a given file path.
   *
   * @param string $filePath The original file path (e.g., "images/photo.jpg")
   * @return array List of deleted thumbnail paths
   */
  public function deleteThumbnails(string $filePath): array
  {
    $thumbProfileName = $this->getConfig('thumbnails');
    if (empty($thumbProfileName) || $thumbProfileName === false) {
      return [];
    }

    $thumbProfile = $this->thumbProfile();
    $deletedPaths = [];
    $profileDir = $this->name;

    try {
      $contents = $thumbProfile->listContents($profileDir, true);

      foreach ($contents as $item) {
        if ($item['type'] !== 'file') {
          continue;
        }

        if ($this->matchesThumbnailPath($item['path'], $filePath, $profileDir)) {
          try {
            $thumbProfile->delete($item['path'], true);
            $deletedPaths[] = $item['path'];
          } catch (\Exception $e) {
            // Continue with other deletions
          }
        }
      }
    } catch (\Exception $e) {
      // listContents might fail on some adapters (e.g., External)
    }

    return $deletedPaths;
  }

  /**
   * Check if a thumbnail path corresponds to the given original file path.
   *
   * @param string $thumbnailPath Full thumbnail path
   * @param string $originalPath Original file path
   * @param string $profileName Profile name
   * @return bool
   */
  protected function matchesThumbnailPath(string $thumbnailPath, string $originalPath, string $profileName): bool
  {
    $thumbnailPath = str_replace(['/', '\\'], DS, $thumbnailPath);
    $originalPath = str_replace(['/', '\\'], DS, $originalPath);

    if (strpos($thumbnailPath, $profileName . DS) !== 0) {
      return false;
    }

    // Direct suffix match
    if (substr($thumbnailPath, -strlen($originalPath)) === $originalPath) {
      return true;
    }

    // WebP conversion match
    $thumbExt = strtolower(pathinfo($thumbnailPath, PATHINFO_EXTENSION));
    $origExt = strtolower(pathinfo($originalPath, PATHINFO_EXTENSION));

    if ($thumbExt === 'webp' && in_array($origExt, ['jpg', 'jpeg', 'png'])) {
      $originalWithoutExt = pathinfo($originalPath, PATHINFO_DIRNAME) . DS .
                            pathinfo($originalPath, PATHINFO_FILENAME);
      $thumbWithoutExt = pathinfo($thumbnailPath, PATHINFO_DIRNAME) . DS .
                         pathinfo($thumbnailPath, PATHINFO_FILENAME);

      if (substr($thumbWithoutExt, -strlen($originalWithoutExt)) === $originalWithoutExt) {
        return true;
      }
    }

    return false;
  }

  public function readAndDelete($path)
  {
    return $this->filesystem()->readAndDelete($path);
  }

  public function rename($from, $to)
  {
    return $this->filesystem()->rename($from, $to);
  }

  public function copy($from, $to)
  {
    return $this->filesystem()->copy($from, $to);
  }

  public function getMetadata($path)
  {
    return $this->filesystem()->getMetadata($path);
  }

  public function getMimetype($path)
  {
    return $this->filesystem()->getMimetype($path);
  }

  public function getTimestamp($path)
  {
    return $this->filesystem()->getTimestamp($path);
  }

  public function getSize($path)
  {
    return $this->filesystem()->getSize($path);
  }

  public function createDir($path)
  {
    return $this->filesystem()->createDir($path);
  }

  public function deleteDir($path)
  {
    return $this->filesystem()->deleteDir($path);
  }

  public function listContents($directory = '', $recursive = false)
  {
    return $this->filesystem()->listContents($directory, $recursive);
  }

  public function getVisibility($path)
  {
    return $this->filesystem()->getVisibility($path);
  }

  public function setVisibility($path, $visibility = 'public')
  {
    return $this->filesystem()->setVisibility($path, $visibility);
  }
}
