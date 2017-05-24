<?php

namespace mglaman\PlatformDocker\Command;

use mglaman\Docker\Compose;
use mglaman\Docker\Docker;
use mglaman\PlatformDocker\BrowserTrait;
use mglaman\PlatformDocker\Command\Docker\DockerCommand;
use mglaman\PlatformDocker\Platform;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MailCommand extends DockerCommand
{
    use BrowserTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
          ->setName('mail')
          ->setDescription('Displays link to mailcatcher, with port.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mailcatcher_port = Docker::getContainerPort(Compose::getContainerName(Platform::projectName(), 'mailcatcher'), 1080);
        if (!$mailcatcher_port) {
            $output->writeln('<error>The Mailcatcher container is not running.</error>');
            return;
        }
        $this->openUrl("http://localhost:$mailcatcher_port", $this->stdErr, $this->stdOut);
    }
}
