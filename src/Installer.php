<?php
/**
 * @link    https://craftcms.com/
 * @license MIT
 */

namespace craft\composer;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Repository\InstalledRepositoryInterface;

/**
 * Installer is the Composer installer that installs Craft CMS plugins.
 *
 * @author Pixel & Tonic, Inc. <support@craftcms.com>
 */
class Installer extends LibraryInstaller
{
    // Constants
    // =========================================================================

    const PLUGINS_FILE = 'craftcms/plugins.php';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function supports($packageType)
    {
        return $packageType === 'craft-plugin';
    }

    /**
     * @inheritdoc
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        // Install the plugin in vendor/ like a normal Composer library
        parent::install($repo, $package);

        // Add the plugin info to plugins.php
        $this->addPlugin($package);
    }

    /**
     * @inheritdoc
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        // Update the plugin in vendor/ like a normal Composer library
        parent::update($repo, $initial, $target);

        // Remove the old plugin info from plugins.php
        $this->removePlugin($initial);

        // Add the new plugin info to plugins.php
        $this->addPlugin($target);
    }

    /**
     * @inheritdoc
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        // Uninstall the plugin from vendor/ like a normal Composer library
        parent::uninstall($repo, $package);

        // Remove the plugin info from plugins.php
        $this->removePlugin($package);
    }

    protected function addPlugin(PackageInterface $package)
    {
        $plugin = [
            'name' => $package->getName(),
            'version' => $package->getVersion(),
        ];

        $plugins = $this->loadPlugins();
        $plugins[$package->getName()] = $plugin;
        $this->savePlugins($plugins);
    }

    protected function removePlugin(PackageInterface $package)
    {
        $plugins = $this->loadPlugins();
        unset($plugins[$package->getName()]);
        $this->savePlugins($plugins);
    }

    protected function loadPlugins()
    {
        $file = $this->vendorDir.'/'.static::PLUGINS_FILE;

        if (!is_file($file)) {
            return [];
        }

        // Invalidate opcache of plugins.php if it exists
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($file, true);
        }


        return require($file);
    }

    protected function savePlugins(array $plugins)
    {
        $file = $this->vendorDir.'/'.static::PLUGINS_FILE;

        if (!file_exists(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }

        $array = var_export($plugins, true);
        file_put_contents($file, "<?php\n\nreturn $array;\n");

        // Invalidate opcache of plugins.php if it exists
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($file, true);
        }
    }
}