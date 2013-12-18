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
        DEFAULT_MASTER_FILE = 'master.conf';
    
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
        $this['configuration.fileSystem.adapter'] = function($c) {
            return new Local($c['configuration.path']);
        };
        
        $this['configuration.fileSystem'] = function($c) {
            return new Filesystem($c['configuration.fileSystem.adapter']);    
        };
        
        $this['parser'] = function($c) {
            return new Parser($c['configuration.fileSystem']);    
        };
        
        $this['configuration'] = function($c) {
            return new Reader($c['parser'], $c['configuration.masterFile']);    
        };
        
        $this['sources.fileSystem.adapter'] = function($c) {
            return new Local($c['sources.path']);
        };
        
        $this['sources.fileSystem'] = function($c) {
            return new Filesystem($c['sources.fileSystem.adapter']);
        };
        
        $this['hydrator'] = function($c) {
            return new Hydrator($c['sources.fileSystem'], $c['distFiles.suffix'], $c['configuration']);    
        };
    }
}