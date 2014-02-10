<?php

require_once __DIR__ . '/CommandTestCase.php';

class VCSTest extends CommandTestCase
{
    public function testVcs()
    {
        $this->runCommand('vcs', array(
        ));
        
        $this->assertDisplay('~Looking for vcs~');
    }
}