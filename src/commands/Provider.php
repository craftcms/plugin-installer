<?php

namespace craft\composer\commands;

use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use craft\composer\Plugin;

class Provider implements CommandProvider
{
    protected IOInterface $io;
    protected Plugin $plugin;

    public function __construct(array $arguments)
    {
        $this->io = $arguments['io'];
        $this->plugin = $arguments['plugin'];
    }

    public function getCommands()
    {
        return [new Regenerate($this->plugin)];
    }
}
