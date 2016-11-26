<?php
/**
 * @link    https://craftcms.com/
 * @license MIT
 */

namespace craft\composer;

use Composer\Package\CompletePackageInterface;
use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Filesystem;

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
        $extra = $package->getExtra();
        $prettyName = $package->getPrettyName();

        // class (required)
        if (isset($extra['class'])) {
            $class = $extra['class'];
        } else if (($class = $this->findPluginClass($package)) === null) {
            throw new \InvalidArgumentException('Unable to determine the Plugin class for '.$prettyName);
        }

        $plugin = [
            'class' => $class,
        ];

        if (strpos($prettyName, '/') !== false) {
            list($vendor, $name) = explode('/', $prettyName);
        } else {
            $vendor = null;
            $name = $prettyName;
        }

        // name
        if (isset($extra['name'])) {
            $plugin['name'] = $extra['name'];
        } else {
            $plugin['name'] = $name;
        }

        // handle
        if (isset($extra['handle'])) {
            $plugin['handle'] = $extra['handle'];
        } else {
            $plugin['handle'] = $name;
        }

        // version
        if (isset($extra['version'])) {
            $plugin['version'] = $extra['version'];
        } else {
            $plugin['version'] = $package->getPrettyVersion();
        }

        // schemaVersion
        if (isset($extra['schemaVersion'])) {
            $plugin['schemaVersion'] = $extra['schemaVersion'];
        }

        // description
        if (isset($extra['description'])) {
            $plugin['description'] = $extra['description'];
        } else if ($package instanceof CompletePackageInterface && ($description = $package->getDescription())) {
            $plugin['description'] = $description;
        }

        // developer
        if (isset($extra['developer'])) {
            $plugin['developer'] = $extra['developer'];
        } else if ($authorName = $this->getAuthorProperty($package, 'name')) {
            $plugin['developer'] = $authorName;
        } else if ($vendor !== null) {
            $plugin['developer'] = $vendor;
        }

        // developerUrl
        if (isset($extra['developerUrl'])) {
            $plugin['developerUrl'] = $extra['developerUrl'];
        } else if ($package instanceof CompletePackageInterface && ($homepage = $package->getHomepage())) {
            $plugin['developerUrl'] = $homepage;
        } else if ($authorHomepage = $this->getAuthorProperty($package, 'homepage')) {
            $plugin['developerUrl'] = $authorHomepage;
        }

        // documentationUrl
        if (isset($extra['documentationUrl'])) {
            $plugin['documentationUrl'] = $extra['documentationUrl'];
        } else if ($package instanceof CompletePackageInterface && ($support = $package->getSupport()) && isset($support['docs'])) {
            $plugin['documentationUrl'] = $support['docs'];
        }

        // components
        if (isset($extra['components'])) {
            $plugin['components'] = $extra['components'];
        }

        $plugins = $this->loadPlugins();
        $plugins[$package->getName()] = $plugin;
        $this->savePlugins($plugins);
    }

    protected function findPluginClass(PackageInterface $package)
    {
        $autoload = $package->getAutoload();

        if (!empty($autoload['psr-0'])) {
            foreach ($autoload['psr-0'] as $name => $path) {
                if (($class = $this->findPluginClassInAutoloadPath($package, $name, $path)) !== null) {
                    return $class;
                }
            }
        }

        if (!empty($autoload['psr-4'])) {
            foreach ($autoload['psr-4'] as $name => $path) {
                if (is_array($path)) {
                    foreach ($path as $_path) {
                        if (($class = $this->findPluginClassInAutoloadPath($package, $name, $_path)) !== null) {
                            return $class;
                        }
                    }
                } else {
                    if (($class = $this->findPluginClassInAutoloadPath($package, $name, $path)) !== null) {
                        return $class;
                    }
                }
            }
        }

        return null;
    }

    protected function findPluginClassInAutoloadPath(PackageInterface $package, $name, $path)
    {
        // Normalize $path to an absolute path
        $fs = new Filesystem();
        if (!$fs->isAbsolutePath($path)) {
            $path = $this->vendorDir.'/'.$package->getPrettyName().'/'.$path;
        }
        $path = $fs->normalizePath($path);

        if (!file_exists($path.'/Plugin.php')) {
            return null;
        }

        return $name.'Plugin';
    }

    protected function getAuthorProperty(PackageInterface $package, $property)
    {
        if (!$package instanceof CompletePackageInterface) {
            return null;
        }

        $authors = $package->getAuthors();
        $firstAuthor = array_shift($authors);

        if ($firstAuthor === null || !isset($firstAuthor[$property])) {
            return null;
        }

        return $firstAuthor[$property];
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
