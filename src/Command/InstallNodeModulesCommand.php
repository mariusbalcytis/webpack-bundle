<?php

namespace Maba\Bundle\WebpackBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallNodeModulesCommand extends Command
{
    private $installAssetsHelper;

    public function __construct(
        InstallAssetsHelper $installAssetsHelper
    ) {
        parent::__construct('maba:webpack:install-node-modules');

        $this->installAssetsHelper = $installAssetsHelper;
    }

    protected function configure()
    {
        $this
            ->setDescription('Install node modules in root directory')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command runs either <info>yarn install</info> or <info>npm install</info> depending on existing lock file and installed software.
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->addStylesConfiguration($output);

        $mode = $this->installAssetsHelper->decideInstalledManager($output);
        if ($mode !== null) {
            $this->installAssetsHelper->installNodeModules($mode, $input, $output);
        }
    }

    private function addStylesConfiguration(OutputInterface $output)
    {
        $output->getFormatter()->setStyle('code', new OutputFormatterStyle('white', 'black', ['bold']));
        $output->getFormatter()->setStyle('bold', new OutputFormatterStyle(null, null, ['bold']));
    }
}
