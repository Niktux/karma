<?php

namespace Karma\Configuration;

class ValueFilterIteratorTest extends \PHPUnit_Framework_TestCase
{
    private
        $values;
    
    protected function setUp()
    {
        $this->values = new \ArrayIterator(array(
            'db.user' => 'root',
            'db.pass' => 'rootroot',
            'db.host' => '192.160.13.12',
            'email' => 'root@db.org',
            'display_errors' => true,
            'trueString' => 'true',
            'someString' => 'Connecting as root is evil ! Mike12',
            'otherString' => 'null',
            'number' => 0,
            'tenNumber' => 10,
            'numberAsString' => '10',
            'stringWithStar' => 'b*te',
            'nullValue' => null,
            'list' => array(100, 'arootb', true, 'goo'),
        ));
    }
    
    /**
     * @dataProvider providerTestFilter
     */
    public function testFilter($filter, $expected)
    {
        $it = new ValueFilterIterator($filter, $this->values);
        
        $this->assertSame($expected, iterator_to_array($it));
    }
    
    public function providerTestFilter()
    {
        return array(
            array('root', array(
                'db.user' => 'root',
            )),
            array('root*', array(
                'db.user' => 'root',
                'db.pass' => 'rootroot',
                'email' => 'root@db.org',
            )),
            array('*root*', array(
                'db.user' => 'root',
                'db.pass' => 'rootroot',
                'email' => 'root@db.org',
                'someString' => 'Connecting as root is evil ! Mike12',
                'list' => array(100, 'arootb', true, 'goo'),
            )),
            array('*root', array(
                'db.user' => 'root',
                'db.pass' => 'rootroot',
            )),
            array('null', array(
                'nullValue' => null,
            )),
            array(null, array(
                'nullValue' => null,
            )),
            array('*null*', array(
                'otherString' => 'null',
            )),
            array('10', array(
                'tenNumber' => 10,
            )),
            array('10*', array(
                'tenNumber' => 10,
                'numberAsString' => '10',
                'list' => array(100, 'arootb', true, 'goo'),
            )),
            array('0', array(
                'number' => 0,
            )),
            array('*0*', array(
                'db.host' => '192.160.13.12',
                'number' => 0,
                'tenNumber' => 10,
                'numberAsString' => '10',
                'list' => array(100, 'arootb', true, 'goo'),                        
            )),
            array('true', array(
                'display_errors' => true,
                'list' => array(100, 'arootb', true, 'goo'),                      
            )),
            array('true*', array(
                'trueString' => 'true',
            )),
            array('*.o*', array(
                'email' => 'root@db.org',
            )),
            array('*o*', array(
                'db.user' => 'root',
                'db.pass' => 'rootroot',
                'email' => 'root@db.org',
                'someString' => 'Connecting as root is evil ! Mike12',
                'list' => array(100, 'arootb', true, 'goo'), // once
            )),
            array('192.160.13.12', array(
                'db.host' => '192.160.13.12',
            )),
            array('root@db.org', array(
                'email' => 'root@db.org',
            )),
            array('*@db.org', array(
                'email' => 'root@db.org',
            )),
            array('db.*', array(
            )),                        
            array('b**te', array(
                'stringWithStar' => 'b*te',
            )),                        
            array('b**t*', array(
                'stringWithStar' => 'b*te',
            )),                        
            array('***te', array(
            )),                        
            array('b***', array(
                'stringWithStar' => 'b*te',
            )),                        
            array('****', array(
            )),                        
        );
    }
}