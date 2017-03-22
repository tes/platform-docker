<?php

namespace mglaman\PlatformDocker;

class PlatformRelationshipBuilder
{
  protected $relationships = [];

  public function addDefaultDatabase($driver, $database, $username, $password, $host, $port)
  {
      $relationships['database'] = [
          [
              'scheme' => $driver,
              'path' => $database,
              'username' => $username,
              'password' => $password,
              'host' => $host,
              'port' => $port,
          ]
      ];
      return $this;
  }

  public function encode() {
      return base64_encode(json_encode($this->relationships));
  }


  public function setEnv() {

  }

}
