<?php

declare(strict_types = 1);

namespace Karma;

use Karma\Configuration\Reader;
use Karma\Configuration\Parser;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local;
use Gaufrette\Adapter\Cache;
use Karma\FormatterProviders\ProfileProvider;
use Karma\Generator\NameTranslators\FilePrefixTranslator;
use Karma\Generator\VariableProvider;
use Karma\Generator\ConfigurationFileGenerators\YamlGenerator;
use Karma\Filesystem\Adapters\MultipleAdapter;
use Karma\Filesystem\Adapters\SingleLocalFile;
use Pimple\Container;

class Application extends Container
{
    const
        VERSION = '7.0.0',
        DEFAULT_DISTFILE_SUFFIX = '-dist',
        DEFAULT_CONF_DIRECTORY = 'env',
        DEFAULT_MASTER_FILE = 'master.conf',
        BACKUP_SUFFIX = '~',
        FINDER_CACHE_DIRECTORY = 'cache/karma',
        FINDER_CACHE_DURATION = 86400,
        PROFILE_FILENAME = '.karma';

    public function __construct()
    {
        parent::__construct();

        $this->initializeParameters();

        $this->initializeConfiguration();
        $this->initializeProfile();
        $this->initializeFinder();
        $this->initializeSourceFileSystem();

        $this->initializeServices();
    }

    private function initializeParameters(): void
    {
        $this['configuration.path'] = 'conf';
        $this['configuration.masterFile'] = 'master.conf';

        $this['sources.path'] = ['src'];
        $this['target.path'] = null;
        $this['hydrator.allowNonDistFilesOverwrite'] = false;

        $this['distFiles.suffix'] = '-dist';
    }

    private function initializeConfiguration(): void
    {
        $this['configuration.fileSystem.adapter'] = $this->factory(function(Container $c) {
            return new Local($c['configuration.path']);
        });

        $this['configuration.fileSystem'] = $this->factory(function(Container $c) {
            return new Filesystem($c['configuration.fileSystem.adapter']);
        });

        $this['parser'] = function(Container $c) {
            $parser = new Parser($c['configuration.fileSystem']);

            $parser->enableIncludeSupport()
                ->enableExternalSupport()
                ->enableGroupSupport()
                ->setLogger($c['logger'])
                ->parse($c['configuration.masterFile']);

            return $parser;
        };

        $this['configuration'] = function(Container $c) {
            $parser = $c['parser'];

            return new Reader(
                $parser->getVariables(),
                $parser->getExternalVariables(),
                $parser->getGroups(),
                $parser->getDefaultEnvironmentsForGroups()
            );
        };
    }

    private function initializeProfile(): void
    {
        $this['profile.fileSystem.adapter'] = $this->factory(function(Container $c) {
            return new Local(getcwd());
        });

        $this['profile.fileSystem'] = $this->factory(function(Container $c) {
            return new Filesystem($c['profile.fileSystem.adapter']);
        });

        $this['profile'] = function(Container $c) {
            return new ProfileReader($c['profile.fileSystem']);
        };
    }

    private function initializeSourceFileSystem(): void
    {
        $this['sources.fileSystem.adapter'] = $this->factory(function(Container $c) {

            $paths = $c['sources.path'];

            if(! is_array($paths))
            {
                $paths = [$paths];
            }

            $adapter = new MultipleAdapter();

            foreach($paths as $path)
            {
                $localAdapter = new Local($path);

                if(is_file($path))
                {
                    $filename = basename($path);
                    $path = realpath(dirname($path));
                    $localAdapter = new SingleLocalFile($filename, new Local($path));
                }

                $adapter->mount($path, $localAdapter);
            }

            return $adapter;
        });

        $this['target.fileSystem.adapter'] = $this->factory(function(Container $c) {

            if(! empty($c['target.path']))
            {
                $c['hydrator.allowNonDistFilesOverwrite'] = true;

                return new Local($c['target.path']);
            }

            return $this['sources.fileSystem.adapter'];
        });

        $this['target.fileSystem'] = $this->factory(function(Container $c) {
            return new Filesystem($c['target.fileSystem.adapter']);
        });

        $this['sources.fileSystem'] = $this->factory(function(Container $c) {
            return new Filesystem($c['sources.fileSystem.adapter']);
        });

        $this['generate.sources.fileSystem.adapter'] = $this->factory(function(Container $c) {

            $sources = $c['sources.path'];

            if(count($sources) > 1)
            {
                throw new \InvalidArgumentException("Generate command does not allow multiple source paths");
            }

            return new Local(
                array_shift($sources)
            );
        });

        $this['generate.sources.fileSystem'] = $this->factory(function(Container $c) {
            return new Filesystem($c['generate.sources.fileSystem.adapter']);
        });

        $this['sources.fileSystem.finder'] = $this->factory(function(Container $c) {
            return $c['sources.fileSystem'];
        });

        $this['sources.fileSystem.cached'] = $this->factory(function(Container $c) {
            $cache = $c['finder.cache.adapter'];
            $adapter = new Cache(
                $c['sources.fileSystem.adapter'],
                $cache,
                $c['finder.cache.duration'],
                $cache
            );

            return new Filesystem($adapter);
        });
    }

    private function initializeFinder(): void
    {
        $this['finder.cache.path'] = self::FINDER_CACHE_DIRECTORY;
        $this['finder.cache.duration'] = self::FINDER_CACHE_DURATION;

        $this['finder'] = $this->factory(function(Container $c) {
            return new Finder($this['sources.fileSystem.finder']);
        });

        $this['finder.cache.adapter'] = $this->factory(function(Container $c) {
            return new Local($c['finder.cache.path'], true);
        });
    }

    private function initializeServices(): void
    {
        $this['logger'] = $this->factory(function(Container $c) {
            return new \Psr\Log\NullLogger();
        });

        $this['formatter.provider'] = function (Container $c) {
            return new ProfileProvider($c['profile']);
        };

        $this['hydrator'] = $this->factory(function(Container $c) {
            $hydrator = new Hydrator($c['sources.fileSystem'], $c['target.fileSystem'], $c['configuration'], $c['finder'], $c['formatter.provider']);
            $hydrator->allowNonDistFilesOverwrite($c['hydrator.allowNonDistFilesOverwrite']);

            $hydrator->setLogger($c['logger'])
                ->setSuffix($c['distFiles.suffix']);

            return $hydrator;
        });

        $this['generator.nameTranslator'] = function (Container $c) {
            $translator = new FilePrefixTranslator();
            $translator->changeMasterFile($c['configuration.masterFile']);

            return $translator;
        };

        $this['generator.variableProvider'] = $this->factory(function (Container $c) {
            $provider = new VariableProvider($c['parser']);

            $profile = $c['profile'];
            $options = $profile->getGeneratorOptions();
            if(! isset($options['translator']) || $options['translator'] === 'prefix')
            {
                $provider->setNameTranslator($c['generator.nameTranslator']);
            }

            return $provider;
        });

        $this['configurationFilesGenerator'] = function (Container $c) {
            return new YamlGenerator($c['generate.sources.fileSystem'], $c['configuration'], $c['generator.variableProvider']);
        };
    }
}
