<?php

namespace mglaman\PlatformDocker;

/**
 * Reads the .platform/services.yaml configuration file.
 */
class PlatformServiceConfig
{
    use YamlConfigReader;

    /**
     * {@inheritdoc}
     */
    protected function getConfigFilePath()
    {
        return '.platform/services.yaml';
    }

    /**
     * Determines if the site is using redis.
     *
     * @return bool
     */
    public static function hasRedis() {
        return (bool) self::get('redis');
    }

    /**
     * Determines if the site is using solr.
     *
     * @return bool
     */
    public static function hasSolr() {
        return (bool) self::get('solr');
    }

}
