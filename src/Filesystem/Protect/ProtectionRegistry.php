<?php
namespace Trois\Attachment\Filesystem\Protect;

use Cake\Core\Configure;

class ProtectionRegistry
{
  const CONFIGURE_KEY_PREFIX = 'Trois/Attachment.profiles.';

  const CONFIGURATION_KEY_SUFFIX = '.protection';

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
    if (!Configure::check($aliasConfigKey)) throw new \InvalidArgumentException('Protection for profile "' . $alias . '" not configured');

    if (static::existsAndInstanceOf($aliasConfigKey)) return Configure::read($aliasConfigKey);

    throw new \InvalidArgumentException('Protection "' . $alias . '" is not an instance of ');
  }

  protected static function existsAndInstanceOf($aliasConfigKey)
  {
    return Configure::check($aliasConfigKey) &&
      Configure::read($aliasConfigKey) instanceof ProtectionInterface;
  }

  public static function getAliasConfigKey($alias)
  {
    return static::CONFIGURE_KEY_PREFIX . $alias . static::CONFIGURATION_KEY_SUFFIX;
  }
}
