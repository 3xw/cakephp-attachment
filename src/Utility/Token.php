<?php
namespace Trois\Attachment\Utility;

use Firebase\JWT\JWT;
use Cake\Utility\Security;

class Token
{

  public static function encode($obj)
  {
    return JWT::encode($obj, self::key());
  }

  public static function decode($cypher)
  {
    try {
      return JWT::decode($cypher, self::key(),['HS256']);
    } catch (\Exception $e) {
      throw $e;
    }
  }

  public static function key()
  {
    return Security::getSalt();
  }
}
