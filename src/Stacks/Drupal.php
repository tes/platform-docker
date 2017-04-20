<?php

namespace mglaman\PlatformDocker\Stacks;

use mglaman\Docker\Compose;
use mglaman\PlatformDocker\Mysql\Mysql;
use mglaman\Toolstack\Toolstack;
use mglaman\Toolstack\Stacks\Drupal as DrupalStackHelper;
use mglaman\PlatformDocker\Platform;

/**
 * Class Settings
 * @package mglaman\PlatformDocker\Stacks\Drupal
 */
class Drupal extends StacksBase
{
    /**
     * Builds the settings.local.php file.
     */
    public function __construct()
    {
        parent::__construct();

        /** @var DrupalStackHelper $drupalStack */
        $drupalStack = Toolstack::getStackByType('drupal');
        $this->version = $drupalStack->version(Platform::webDir());
    }

    /**
     *
     */
    public function configure() {
        $this->ensureSettings();
        $this->ensureLocalSettings();
        $this->drushrc();
    }

    /**
     * Ensures that the Drupal settings.php is available.
     * @throws \Exception
     */
    protected function ensureSettings() {
        if (!$this->fs->exists(Platform::webDir() . '/sites/default')) {
            $this->fs->mkdir(Platform::webDir() . '/sites/default', 0775);
        }
        else {
            // If building from an existing project, Drupal may have fiddled with
            // the permissions preventing us from writing.
            $this->fs->chmod(Platform::webDir() . '/sites/default', 0775);
        }
        $has_settings = $this->fs->exists(Platform::webDir() . '/sites/default/settings.php');
        if ($has_settings) {
            $this->fs->chmod(Platform::webDir() . '/sites/default/settings.php', 0664);
        }

        switch ($this->version) {
            case DrupalStackHelper::DRUPAL7:
                if (!$has_settings) {
                    $this->fs->copy(CLI_ROOT . '/resources/stacks/drupal7/settings.php', Platform::webDir() . '/sites/default/settings.php', true);
                }
                break;
            case DrupalStackHelper::DRUPAL8:
                if (!$has_settings) {
                    $this->fs->copy(CLI_ROOT . '/resources/stacks/drupal8/settings.php', Platform::webDir() . '/sites/default/settings.php', true);
                }
                $this->fs->mkdir([
                  Platform::sharedDir() . '/config',
                  Platform::sharedDir() . '/config/active',
                  Platform::sharedDir() . '/config/staging',
                ]);
                break;
            default:
                throw new \Exception('Unsupported version of Drupal. Write a pull reuqest!');
        }
    }

    protected function ensureLocalSettings() {
        // @todo: Check if settings.local.php exists, load in any $conf changes.
        $target_local_settings = Platform::sharedDir() . '/settings.local.php';
        if ($this->fs->exists($target_local_settings)) {
            // Try and make it deletable.
            $this->fs->chmod($target_local_settings, 0664);
        }
        switch ($this->version) {
            case DrupalStackHelper::DRUPAL7:
                $this->fs->copy(CLI_ROOT . '/resources/stacks/drupal7/settings.local.php', $target_local_settings, true);
                break;
            case DrupalStackHelper::DRUPAL8:
                $this->fs->copy(CLI_ROOT . '/resources/stacks/drupal8/settings.local.php', $target_local_settings, true);
                break;
            default:
                throw new \Exception('Unsupported version of Drupal. Write a pull reuqest!');
        }

        $web_container_name = Compose::getContainerName(Platform::projectName(), 'nginx');
        $detect_port_command = "docker inspect --format='{{(index (index .NetworkSettings.Ports \"80/tcp\") 0).HostPort}}' $web_container_name";
        $placeholders = [
          '{{ salt }}' => hash('sha256', serialize($_SERVER)),
          '{{ container_name }}' => $this->containerName,
          '{{ redis_container_name }}' => $this->redisContainerName,
          '{{ solr_container_name }}' => $this->solrContainerName,
          '{{ project_domain }}' => $this->projectName . '.' . $this->projectTld,
          '{{ external_project_domain }}' => 'http://localhost/' . trim(shell_exec($detect_port_command)),
          '{{ mysql_user }}' => Mysql::getMysqlUser(),
          '{{ mysql_password }}' => Mysql::getMysqlPassword(),
          '{{ project_root }}' => Platform::rootDir(),
        ];
        
        // Replace template variables.
        $localSettings = file_get_contents($target_local_settings);
        if (file_exists(Platform::rootDir() . '/.platform-project.local.settings.php')) {
            $additional_settings = file_get_contents(Platform::rootDir() . '/.platform-project.local.settings.php');
            $additional_settings = str_replace("<?php\n", '', $additional_settings);
            $localSettings .= $additional_settings;
        }
        $localSettings = str_replace(array_keys($placeholders), array_values($placeholders), $localSettings);
        file_put_contents($target_local_settings, $localSettings);

        // Relink if missing.
        if (!$this->fs->exists(Platform::webDir() . '/sites/default/settings.local.php')) {
            $this->fs->symlink($this->getRelativeLinkToShared() . '/settings.local.php', Platform::webDir() . '/sites/default/settings.local.php');
        }

      if (file_exists(Platform::rootDir() . '/tests/behat.local.yml.dist')) {
          $behat_local_yml = file_get_contents(Platform::rootDir() . '/tests/behat.local.yml.dist');
          $behat_local_yml = str_replace(array_keys($placeholders), array_values($placeholders), $behat_local_yml);
          file_put_contents(Platform::rootDir() . '/tests/behat.local.yml', $behat_local_yml);
      }
    }

    /**
     * Write a drushrc
     */
    public function drushrc() {
        // @todo: Check if drushrc.php exists, load in any $conf changes.
        switch ($this->version) {
            case DrupalStackHelper::DRUPAL7:
                $this->fs->copy(CLI_ROOT . '/resources/stacks/drupal7/drushrc.php', Platform::sharedDir() . '/drushrc.php', true);
                break;
            case DrupalStackHelper::DRUPAL8:
                $this->fs->copy(CLI_ROOT . '/resources/stacks/drupal8/drushrc.php', Platform::sharedDir() . '/drushrc.php', true);
                break;
            default:
                throw new \Exception('Unsupported version of Drupal. Write a pull reuqest!');
        }

        // Replace template variables.
        $localSettings = file_get_contents(Platform::sharedDir() . '/drushrc.php');
        // @todo this expects proxy to be running.
        $localSettings = str_replace('{{ project_domain }}', $this->projectName . '.' . $this->projectTld, $localSettings);
        file_put_contents(Platform::sharedDir() . '/drushrc.php', $localSettings);

        // Only do this if this is a link. New Platform.sh build ship with a drushrc.php file.
        if (is_link(Platform::webDir() . '/sites/default/drushrc.php')) {
            $this->fs->symlink($this->getRelativeLinkToShared() . 'drushrc.php', Platform::webDir() . '/sites/default/drushrc.php');
        }
    }

    /**
     * Gets the relative path from the sites/default directory to the shared directory.
     *
     * @return string
     */
    protected function getRelativeLinkToShared() {
        return $this->fs->makePathRelative(realpath(Platform::sharedDir()), realpath(Platform::webDir() . '/sites/default/'));
    }
}
