<?php

class InMemoryTest extends PHPUnit_Framework_TestCase
{
    private
        $files,
        $vcs;
    
    protected function setUp()
    {
        $this->vcs = new \Karma\VCS\InMemory('~tracked~i', '~ignored~i');
    }
    
    /**
     * @dataProvider providerTestIsIgnored 
     */
    public function testIsIgnored($file, $expected)
    {
        $this->assertSame($expected, $this->vcs->isIgnored($file));

        $this->vcs->ignoreFile($file);
        $this->assertSame(true, $this->vcs->isIgnored($file));
    }
    
    public function providerTestIsIgnored()
    {
        return array(
        	array('ignored.yml', true),
        	array('path/to/someOtherIgnoredFile.yml', true),
        	array('a.yml', false),
        	array('ignore.yml', false),
        );
    }
    
    /**
     * @dataProvider providerTestIsTracked 
     */
    public function testIsTracked($file, $expected)
    {
        $this->assertSame($expected, $this->vcs->isTracked($file));

        $this->vcs->untrackFile($file);
        $this->assertSame(false, $this->vcs->isTracked($file));
    }
    
    public function providerTestIsTracked()
    {
        return array(
        	array('tracked.yml', true),
        	array('path/to/ignoredAndTracked.yml', true),
        	array('path/to/someOtherIgnoredFile.yml', false),
        	array('a.yml', false),
        	array('path/to/track.yml', false),
        );
    }
}