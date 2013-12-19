<?php

namespace Karma;

interface Configuration
{
    const
        NOT_FOUND = 'karma-notfound';
    
    public function read($variable, $environment = null);
    
    public function setDefaultEnvironment($environment);
    
    public function getAllVariables();
    public function getAllValuesForEnvironment($environment = null);
}