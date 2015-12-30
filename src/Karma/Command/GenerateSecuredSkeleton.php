<?php

namespace Karma\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Karma\Configuration;
use Karma\Command;
use Karma\Configuration\ValueFilterIterator;
use Karma\Hydrator;

class GenerateSecuredSkeleton extends Command
{
    const
        ENV_PROD= 'prod';

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('generate:secure:skeleton')
            ->setDescription('Generate the secured.conf file')

            ->addOption('env', 'e', InputOption::VALUE_REQUIRED, 'Target environment', self::ENV_PROD)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing file if present')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Target path for generated secured.conf file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $environment = $input->getOption('env');
        $outputPath = $input->getOption('output');
        $overwrite = $input->getOption('force');

        $this->output->writeln(sprintf(
            "<info>Generate secured variables for <comment>%s</comment> environment</info>\n",
            $environment
        ));

        $reader = $this->app['configuration'];
        $reader->setDefaultEnvironment($input->getOption('env'));
        
        $securedVariables = $this->generateSecuredVariables($reader);

        if(!empty($outputPath))
        {
            $this->app['configuration.fileSystem']->write($outputPath, $securedVariables, $overwrite);
        }
        
        $this->output->writeln('');
        $this->output->writeln('***');
        
        $this->output->writeln($securedVariables);
        
        $this->output->writeln('***');
    }

    private function generateSecuredVariables(Configuration $reader)
    {
        $values = new ValueFilterIterator(
            Configuration::NOT_FOUND,
            new \ArrayIterator($reader->getAllValuesForEnvironment())
        );

        $values->ksort();

        $securedConf = "[variables]";
        
        foreach($values as $variable => $value)
        {
            $variablePattern = <<<PATTERN

%s:
    %s = %s
PATTERN;

            $securedConf .= sprintf(
                $variablePattern,
                $variable,
                $reader->getDefaultEnvironment(),
                Hydrator::TODO_VALUE
            );
        }
        
        return $securedConf;
    }
}
