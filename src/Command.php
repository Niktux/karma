<?php

declare(strict_types = 1);

namespace Karma;

use Karma\Logging\OutputAware;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Karma\Logging\OutputInterfaceAdapter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

abstract class Command extends \Symfony\Component\Console\Command\Command
{
    use OutputAware;

    protected Application
        $app;

    public function __construct(Application $app)
    {
        parent::__construct();

        $this->app = $app;
    }

    protected function configure(): void
    {
        $this->addOption('cache', null, InputOption::VALUE_NONE, 'Cache the dist files list');
        $this->addOption('no-title', null, InputOption::VALUE_NONE, 'Do not display logo title');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->configureOutputInterface($output);
        $this->printHeader($input->getOption('no-title'));

        $profile = $this->app['profile'];

        $confDir = Application::DEFAULT_CONF_DIRECTORY;
        if($profile->hasConfigurationDirectory())
        {
            $confDir = $profile->configurationDirectory();
        }

        $masterFile = Application::DEFAULT_MASTER_FILE;
        if($profile->hasMasterFilename())
        {
            $masterFile = $profile->masterFilename();
        }

        $suffix = Application::DEFAULT_DISTFILE_SUFFIX;
        if($profile->hasTemplatesSuffix())
        {
            $suffix = $profile->templatesSuffix();
        }

        $this->app['configuration.path']       = $confDir;
        $this->app['configuration.masterFile'] = $masterFile;
        $this->app['distFiles.suffix'] = $suffix;

        $this->app['logger'] = new OutputInterfaceAdapter($output);

        if($input->getOption('cache'))
        {
            $this->enableFinderCache();
        }

        return 0;
    }

    private function configureOutputInterface(OutputInterface $output): void
    {
        $style = new OutputFormatterStyle('cyan', null, array('bold'));
        $output->getFormatter()->setStyle('important', $style);

        $this->setOutput($output);
    }

    private function enableFinderCache(): void
    {
        $this->app['sources.fileSystem.finder'] = $this->app['sources.fileSystem.cached'];
    }

    protected function formatValue($value)
    {
        if($value === false)
        {
            $value = 'false';
        }
        elseif($value === true)
        {
            $value = 'true';
        }
        elseif($value === null)
        {
            $value = '<fg=white;options=bold>NULL</fg=white;options=bold>';
        }
        elseif($value === Configuration::NOT_FOUND)
        {
            $value = '<error>NOT FOUND</error>';
        }
        elseif(is_array($value))
        {
            array_walk($value, function(& $item) {
                $item = $this->formatValue($item);
            });

            $value = sprintf('[%s]', implode(', ', $value));
        }

        return $value;
    }

    private function printHeader(bool $noTitle = true): void
    {
        if($noTitle === true)
        {
            $this->output->writeln('Karma ' . Application::VERSION);
            return;
        }

        $this->output->writeln(
           $this->getLogo($this->output->isDecorated())
        );
    }

    private function getLogo(bool $outputDecorated = true): string
    {
        $logo = <<<ASCIIART
.@@@@...@@..
...@@..@....
...@@.@@....    %K A R M A*
...@@.@@@...               %VERSION*
...@@...@@..
...@@....@@.

ASCIIART;

        $background = 'fg=magenta';
        $text = 'fg=white';

        $toBackground = "</$text><$background>";
        $toText = "</$background><$text>";

        // insert style tags
        if($outputDecorated === true)
        {
            $logo = str_replace('@', "$toText@$toBackground", $logo);
            $logo = str_replace('.', '@', $logo);
        }

        $logo = str_replace(['%', '*'], [$toText, $toBackground], $logo);

        return sprintf(
            '<%s>%s</%s>',
            $background,
            str_replace('VERSION', Application::VERSION, $logo),
            $background
        );
    }
}
