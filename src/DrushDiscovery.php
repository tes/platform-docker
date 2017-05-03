<?php

namespace mglaman\PlatformDocker;

/**
 * @package mglaman\PlatformDocker\Utils\DrushDiscovery
 */
class DrushDiscovery
{
    /**
     * Gets the best Drush installation to use.
     *
     * @return string
     */
    public static function getExecutable()
    {
        // Prefer local installations of Drush.
        $local = Platform::rootDir() . '/vendor/bin/drush';
        if (file_exists($local)) {
            return $local;
        }
        // Global install.
        return 'drush';
    }

}
