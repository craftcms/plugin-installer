<?php
/**
 * @link    https://craftcms.com/
 * @license MIT
 */

namespace craft\composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use craft\composer\commands\Provider;

/**
 * Plugin is the Composer plugin that registers the Craft CMS composer installer.
 *
 * @author Pixel & Tonic, Inc. <support@craftcms.com>
 */
class Plugin implements PluginInterface, Capable
{
    private Installer $installer;

    public function activate(Composer $composer, IOInterface $io)
    {
        // Register the plugin installer
        $this->installer = new Installer($io, $composer, 'craft-plugin');
        $composer->getInstallationManager()->addInstaller($this->installer);

        // Is this a plugin at root? Elementary, my dear Watson 🕵️!
        if ($this->installer->supports($composer->getPackage()->getType())) {
            $this->installer->addPlugin($composer->getPackage(), true);
        }
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        $composer->getInstallationManager()->removeInstaller($this->installer);
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {}

    public function getCapabilities()
    {
        return [
            CommandProvider::class => Provider::class,
        ];
    }

    public function getInstaller(): Installer
    {
        return $this->installer;
    }
}
