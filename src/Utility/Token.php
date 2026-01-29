<?php
namespace Trois\Attachment\Utility;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Cake\Utility\Security;

class Token
{
  public static function encode($obj)
  {
    // v6/v7: encode(payload, key, alg)
    return JWT::encode((array) $obj, self::key(), 'HS256');
  }

  public static function decode($cypher)
  {
    // v6/v7: decode(jwt, Key|Key[])
    return JWT::decode($cypher, new Key(self::key(), 'HS256'));
  }

  public static function key()
  {
    // IMPORTANT: en v7 il peut y avoir une validation de longueur de clé.
    // Security::getSalt() doit être suffisamment long et aléatoire.
    return (string) Security::getSalt();
  }
}