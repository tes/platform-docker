<?php

namespace mglaman\PlatformDocker\Tests\Utils;

use mglaman\PlatformDocker\DrushDiscovery;
use mglaman\PlatformDocker\Platform;

class DrushDiscoveryTest extends BaseUtilsTest
{
    public function testGetExecutable()
    {
        $this->assertEquals('drush', DrushDiscovery::getExecutable());
        mkdir(Platform::rootDir() . '/vendor/bin', 0777, true);
        $local_install = Platform::rootDir() . '/vendor/bin/drush';
        // Fake create.
        touch($local_install);
        $this->assertEquals($local_install, DrushDiscovery::getExecutable());
    }

}
