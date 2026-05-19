<?php

namespace craft\composer\commands;

use Composer\Command\BaseCommand;
use Composer\InstalledVersions;
use Composer\Package\PackageInterface;
use Composer\Semver\Constraint\MatchAllConstraint;
use craft\composer\Installer;
use craft\composer\Plugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Regenerate extends BaseCommand
{
    public function __construct(
        protected readonly Plugin $plugin,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('craft-plugin-regenerate');
        $this->setDescription(sprintf('Rebuild the %s file from the installed packages.', Installer::PLUGINS_FILE));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf('Regenerating %s...', Installer::PLUGINS_FILE));

        /** @var PackageInterface[] $pluginPackages */
        $pluginPackages = array_map(function ($name) {
            return $this->getComposer()
                ->getRepositoryManager()
                ->getLocalRepository()
                ->findPackage($name, new MatchAllConstraint);
        }, InstalledVersions::getInstalledPackagesByType('craft-plugin'));

        if (empty($pluginPackages)) {
            $output->writeln('No plugins are installed.');

            return 0;
        }

        $output->writeln(sprintf('Found %d plugin package(s):', count($pluginPackages)));

        foreach ($pluginPackages as $package) {
            $output->writeln(sprintf('  - Re-adding %s', $package->getName()));
            $this->plugin->getInstaller()->addPlugin($package);
        }

        $output->writeln('Done!');

        return 0;
    }
}
