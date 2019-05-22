<?php
/**
 * @link    https://craftcms.com/
 * @license MIT
 */

namespace craft\composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

/**
 * Plugin is the Composer plugin that registers the Craft CMS composer installer.
 *
 * @author Pixel & Tonic, Inc. <support@craftcms.com>
 */
class Plugin implements PluginInterface
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        // Register the plugin installer
        $installer = new Installer($io, $composer);
        $composer->getInstallationManager()->addInstaller($installer);

        // Is this a plugin at root? Elementary, my dear Watson 🕵️!
        if ($installer->supports($composer->getPackage()->getType())) {
            $installer->addPlugin($composer->getPackage(), true);
        }
    }
}
