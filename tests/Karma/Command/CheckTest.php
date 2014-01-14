<?php

require_once __DIR__ . '/CommandTestCase.php';

use Gaufrette\Adapter\InMemory;
use Symfony\Component\Console\Output\OutputInterface;

class CheckTest extends CommandTestCase
{
    private function runCheck()
    {
        $this->runCommand('check', array(
            'sourcePath' => 'src/',
        ), array(
            'verbosity' => OutputInterface::VERBOSITY_VERY_VERBOSE,
        ));
    }
    
    public function testDistFileWithoutVariable()
    {
        $this->app['sources.fileSystem.adapter'] = new InMemory(array(
            'src/fileOk-dist' => '<%foo%>',
            'src/fileNok-dist' => '',
        ));
        
        $this->runCheck();
        
        $this->assertNotDisplay("~fileOk-dist does not contain variables~");
        $this->assertDisplay("~fileNok-dist does not contain variables~");
    }
    
    public function testNotValuedVariableNotFound()
    {
        $this->app['sources.fileSystem.adapter'] = new InMemory(array(
            'src/file1-dist' => '<%foo%>',
            'src/file2-dist' => '<%bar%>',
            'src/file3-dist' => '<%baz%>',
        ));
    
        $this->runCheck();
    
        $this->assertNotDisplay("~Variable foo is not declared~");
        $this->assertNotDisplay("~Variable bar is not declared~");
        $this->assertDisplay("~Variable baz is not declared~");
    }
}