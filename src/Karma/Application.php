<?php

namespace Karma;

use Karma\Configuration\Reader;
use Karma\Configuration\Parser;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local;
use Gaufrette\Adapter\Cache;
use Karma\VCS\Vcs;
use Karma\VCS\Git;
use Karma\VCS\Git\GitWrapperAdapter;
use Karma\FormatterProviders\ProfileProvider;
use Karma\Generator\NameTranslators\FilePrefixTranslator;
use Karma\Generator\VariableProvider;
use Karma\Generator\ConfigurationFileGenerators\YamlGenerator;
use Karma\Filesystem\Adapters\MultipleAdapter;

class Application extends \Pimple
{
    const
        VERSION = '5.4.0',
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
        $this->initializeVcs();

        $this->initializeServices();
    }

    private function initializeParameters()
    {
        $this['configuration.path'] = 'conf';
        $this['configuration.masterFile'] = 'master.conf';

        $this['sources.path'] = 'src';

        $this['distFiles.suffix'] = '-dist';
    }

    private function initializeConfiguration()
    {
        $this['configuration.fileSystem.adapter'] = function($c) {
            return new Local($c['configuration.path']);
        };

        $this['configuration.fileSystem'] = function($c) {
            return new Filesystem($c['configuration.fileSystem.adapter']);
        };

        $this['parser'] = $this->share(function($c) {
            $parser = new Parser($c['configuration.fileSystem']);

            $parser->enableIncludeSupport()
                ->enableExternalSupport()
                ->enableGroupSupport()
                ->setLogger($c['logger'])
                ->parse($c['configuration.masterFile']);

            return $parser;
        });

        $this['configuration'] = $this->share(function($c) {
            $parser = $c['parser'];

            return new Reader(
                $parser->getVariables(),
                $parser->getExternalVariables(),
                $parser->getGroups(),
                $parser->getDefaultEnvironmentsForGroups()
            );
        });
    }

    private function initializeProfile()
    {
        $this['profile.fileSystem.adapter'] = function($c) {
            return new Local(getcwd());
        };

        $this['profile.fileSystem'] = function($c) {
            return new Filesystem($c['profile.fileSystem.adapter']);
        };

        $this['profile'] = $this->share(function($c) {
            return new ProfileReader($c['profile.fileSystem']);
        });
    }

    private function initializeSourceFileSystem()
    {
        $this['sources.fileSystem.adapter'] = function($c) {
            
            $paths = $c['sources.path'];
            
            if(! is_array($paths))
            {
                $paths = array($paths);
            }
            
            $adapter = new MultipleAdapter();
             
            foreach($paths as $path)
            {
                $adapter->mount($path, new Local($path));
            }
             
            return $adapter;
        };

        $this['sources.fileSystem'] = function($c) {
            return new Filesystem($c['sources.fileSystem.adapter']);
        };

        $this['sources.fileSystem.finder'] = function($c) {
            return $c['sources.fileSystem'];
        };

        $this['sources.fileSystem.cached'] = function($c) {
            $cache = $c['finder.cache.adapter'];
            $adapter = new Cache(
                $c['sources.fileSystem.adapter'],
                $cache,
                $c['finder.cache.duration'],
                $cache
            );

            return new Filesystem($adapter);
        };
    }

    private function initializeFinder()
    {
        $this['finder.cache.path'] = self::FINDER_CACHE_DIRECTORY;
        $this['finder.cache.duration'] = self::FINDER_CACHE_DURATION;

        $this['finder'] = function($c) {
            return new Finder($this['sources.fileSystem.finder']);
        };

        $this['finder.cache.adapter'] = function($c) {
            return new Local($c['finder.cache.path'], true);
        };
    }

    private function initializeVcs()
    {
        $this['rootPath'] = getcwd();

        $this['vcs.fileSystem.adapter'] = function($c) {
            return new Local($c['rootPath']);
        };

        $this['vcs.fileSystem'] = function($c) {
            return new Filesystem($c['vcs.fileSystem.adapter']);
        };

        $this['git.command'] = function($c) {
            return new GitWrapperAdapter();
        };

        $git = function($c) {
            return new Git($this['vcs.fileSystem'], $this['rootPath'], $this['git.command']);
        };

        $this['git'] = $git;
        $this['vcs'] = $git;

        $this['vcsHandler'] = $this->protect(function (Vcs $vcs) {
            $handler = new VcsHandler($vcs, $this['finder']);

            $handler->setLogger($this['logger'])
                ->setSuffix($this['distFiles.suffix']);

            return $handler;
        });
    }

    private function initializeServices()
    {
        $this['logger'] = $this->share(function($c) {
            return new \Psr\Log\NullLogger();
        });

        $this['formatter.provider'] = $this->share(function ($c) {
            return new ProfileProvider($c['profile']);
        });

        $this['hydrator'] = function($c) {
            $hydrator = new Hydrator($c['sources.fileSystem'], $c['configuration'], $c['finder'], $c['formatter.provider']);

            $hydrator->setLogger($c['logger'])
                ->setSuffix($c['distFiles.suffix']);

            return $hydrator;
        };

        $this['generator.nameTranslator'] = $this->share(function ($c) {
            $translator = new FilePrefixTranslator();
            $translator->changeMasterFile($c['configuration.masterFile']);

            return $translator;
        });

        $this['generator.variableProvider'] = function ($c) {
            $provider = new VariableProvider($c['parser'], $c['configuration.masterFile']);

            $profile = $c['profile'];
            $options = $profile->getGeneratorOptions();
            if(! isset($options['translator']) || $options['translator'] === 'prefix')
            {
                $provider->setNameTranslator($c['generator.nameTranslator']);
            }

            return $provider;
        };

        $this['configurationFilesGenerator'] = $this->share(function ($c) {
            return new YamlGenerator($c['sources.fileSystem'], $c['configuration'], $c['generator.variableProvider']);
        });
    }
}
