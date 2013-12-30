<?php

namespace Karma;

use Karma\Configuration\Reader;
use Karma\Configuration\Parser;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local;

class Application extends \Pimple
{
    const
        DEFAULT_DISTFILE_SUFFIX = '-dist',
        DEFAULT_CONF_DIRECTORY = 'conf',
        DEFAULT_MASTER_FILE = 'master.conf',
        BACKUP_SUFFIX = '~';
    
    public function __construct()
    {
        parent::__construct();
        
        $this->initializeParameters();
        $this->initializeServices();
    }
    
    private function initializeParameters()
    {
        $this['configuration.path'] = 'conf';
        $this['configuration.masterFile'] = 'master.conf';
        
        $this['sources.path'] = 'src';
        
        $this['distFiles.suffix'] = '-dist';
    }
    
    private function initializeServices()
    {
        $this['logger'] = $this->share(function($c) {
            return new \Psr\Log\NullLogger();    
        });
        
        $this['configuration.fileSystem.adapter'] = function($c) {
            return new Local($c['configuration.path']);
        };
        
        $this['configuration.fileSystem'] = function($c) {
            return new Filesystem($c['configuration.fileSystem.adapter']);    
        };
        
        $this['parser'] = function($c) {
            $parser = new Parser($c['configuration.fileSystem']);

            $parser->enableIncludeSupport()
                ->enableExternalSupport()
                ->setLogger($c['logger']);
            
            return $parser;
        };
        
        $this['configuration'] = function($c) {
            $parser = $c['parser'];
            $variables = $parser->parse($c['configuration.masterFile']);
            
            return new Reader($variables, $parser->getExternalVariables());    
        };
        
        $this['sources.fileSystem.adapter'] = function($c) {
            return new Local($c['sources.path']);
        };
        
        $this['sources.fileSystem'] = function($c) {
            return new Filesystem($c['sources.fileSystem.adapter']);
        };
        
        $this['hydrator'] = function($c) {
            $hydrator = new Hydrator($c['sources.fileSystem'], $c['configuration']);

            $hydrator->setLogger($c['logger'])
                ->setSuffix($c['distFiles.suffix']);
            
            return $hydrator;
        };
    }
}