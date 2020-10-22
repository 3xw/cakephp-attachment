<?php
namespace Trois\Attachment\Filesystem;

use Cake\Core\Configure;

class ProfileRegistry
{
  const CONFIGURE_KEY_PREFIX = 'Attachment.profiles.';

  protected static $instances = [];

  public static function retrieve($alias)
  {
    if (!isset(static::$instances[$alias])) static::$instances[$alias] = static::create($alias);
    return static::$instances[$alias];
  }

  public static function exists($alias)
  {
    return static::existsAndInstanceOf(static::getAliasConfigKey($alias));
  }

  public static function reset()
  {
    static::$instances = [];
  }

  protected static function create($alias)
  {
    $aliasConfigKey = static::getAliasConfigKey($alias);
    if (!Configure::check($aliasConfigKey)) throw new \InvalidArgumentException('Profile "' . $alias . '" not configured');

    return new Profile($alias, Configure::read($aliasConfigKey));
  }

  public static function getAliasConfigKey($alias)
  {
    return static::CONFIGURE_KEY_PREFIX . $alias;
  }
}
