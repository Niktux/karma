<?php

declare(strict_types = 1);

namespace Karma\Console;

use Karma\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Karma\Application;
use Karma\Configuration\FilterInputVariable;
use Karma\ConfigurableProcessor;

abstract class ConfigureActionCommand extends Command
{
    use FilterInputVariable;

    private const
        ENV_DEV = 'dev',
        OPTION_ASSIGNMENT = '=';

    private bool
        $dryRun,
        $isBackupEnabled;
    private ?string
        $systemEnvironment;
    private string
        $name,
        $description,
        $outputTitle;

    protected string
        $environment;

    public function __construct(Application $app, string $name, string $description, string $outputTitle)
    {
        $this->name = $name;
        $this->description = $description;
        $this->outputTitle = $outputTitle;

        parent::__construct($app);

        $this->dryRun = false;
        $this->isBackupEnabled = false;

        $this->environment = self::ENV_DEV;
        $this->systemEnvironment = null;
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName($this->name)
            ->setDescription($this->description)

            ->addArgument('sourcePath', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'source path to hydrate/generate')

            ->addOption('targetPath', 't', InputOption::VALUE_REQUIRED, 'target path to hydrate/generate', null)
            ->addOption('env', 'e', InputOption::VALUE_REQUIRED, 'Target environment', self::ENV_DEV)
            ->addOption('system', 's', InputOption::VALUE_REQUIRED, 'Target environment for system variables', null)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation mode')
            ->addOption('backup', 'b', InputOption::VALUE_NONE, 'Backup overwritten files')
            ->addOption('override', 'o', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Override variable values', array())
            ->addOption('data', 'd', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Custom data values', array())
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->processInputs($input);

        $processor = $this->getProcessor();
        $this->configureProcessor($processor);
        $this->launchConfigurationAction($processor);

        return 0;
    }

    private function processInputs(InputInterface $input): void
    {
        $this->environment = (string) $input->getOption('env');
        $this->systemEnvironment = $input->getOption('system');

        if($input->getOption('dry-run'))
        {
            $this->dryRun = true;
            $this->output->writeln("<fg=cyan>Run in dry-run mode</fg=cyan>");
        }

        if($input->getOption('backup'))
        {
            $this->isBackupEnabled = true;
            $this->output->writeln("<fg=cyan>Backup enabled</fg=cyan>");
        }

        $profile = $this->app['profile'];
        $sourcePath = $input->getArgument('sourcePath');
        if(empty($sourcePath))
        {
            if($profile->hasSourcePath() !== true)
            {
                throw new \RuntimeException('Missing argument sourcePath');
            }

            $sourcePath = $profile->getSourcePath();
        }

        if(! is_array($sourcePath))
        {
            $sourcePath = [$sourcePath];
        }

        $targetPath = $input->getOption('targetPath');

        if(is_array($targetPath))
        {
            throw new \RuntimeException('Invalid argument targetPath : could not be multiple (single path required as string)');
        }

        if(empty($targetPath) && $profile->hasTargetPath() === true)
        {
            $targetPath = $profile->getTargetPath();
        }

        $this->output->writeln(sprintf(
            "<info>%s <comment>%s</comment> with <comment>%s</comment> values</info>",
            $this->outputTitle,
            implode(' ', $sourcePath),
            $this->environment
        ));
        $this->output->writeln('');

        $this->app['sources.path'] = $sourcePath;
        $this->app['target.path'] = $targetPath;

        $this->processOverriddenVariables(
            $this->parseOptionWithAssignments($input, 'override')
        );
        $this->processCustomData(
            $this->parseOptionWithAssignments($input, 'data')
        );
    }

    abstract protected function launchConfigurationAction(ConfigurableProcessor $processor): void;
    abstract protected function getProcessor(): ConfigurableProcessor;

    private function configureProcessor(ConfigurableProcessor $processor): void
    {
        if($this->dryRun === true)
        {
            $processor->setDryRun();
        }

        if($this->isBackupEnabled === true)
        {
            $processor->enableBackup();
        }

        if($this->systemEnvironment !== null)
        {
            $processor->setSystemEnvironment($this->systemEnvironment);

             $this->app['logger']->info(sprintf(
                'Hydrate <important>system</important> variables with <important>%s</important> values',
                $this->systemEnvironment
            ));
        }
    }

    private function parseOptionWithAssignments(InputInterface $input, $optionName): array
    {
        $strings = $input->getOption($optionName);

        if(! is_array($strings))
        {
            $strings = [$strings];
        }

        $data = [];

        foreach($strings as $string)
        {
            if(strpos($string, self::OPTION_ASSIGNMENT) === false)
            {
                throw new \InvalidArgumentException(sprintf(
                    '%s option must contain %c : --%s <variable>=<value>',
                    $optionName,
                    self::OPTION_ASSIGNMENT,
                    $optionName
                ));
            }

            [$variable, $value] = explode(self::OPTION_ASSIGNMENT, $string, 2);

            if(array_key_exists($variable, $data))
            {
                throw new \InvalidArgumentException("Duplicated %s option value : $variable");
            }

            $data[$variable] = $value;
        }

        return $data;
    }

    private function processOverriddenVariables(array $overrides): void
    {
        $reader = $this->app['configuration'];
        $logger = $this->app['logger'];

        foreach($overrides as $variable => $value)
        {
            $logger->info(sprintf(
               'Override <important>%s</important> with value <important>%s</important>',
               $variable,
               $value
            ));

            $value = $this->parseList($value);

            $reader->overrideVariable($variable, $this->filterValue($value));
        }
    }

    private function processCustomData(array $data): void
    {
        $reader = $this->app['configuration'];
        $logger = $this->app['logger'];

        foreach($data as $variable => $value)
        {
            $logger->info(sprintf(
               'Set custom data <important>%s</important> with value <important>%s</important>',
               $variable,
               $value
            ));

            $reader->setCustomData($variable, $this->filterValue($value));
        }
    }
}
