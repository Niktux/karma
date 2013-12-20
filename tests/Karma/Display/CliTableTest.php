<?php

use Karma\Display\CliTable;

class CliTableTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerTestRender
     */
    public function testRender($input, $expected)
    {
        $table = new CliTable($input);
        $result = $table->render();
        
        $this->assertSame($expected, $result);
    }
    
    public function providerTestRender()
    {
        return array(
            array(array(
                array('Variable', 'Dev', 'Prod'),
                array('x', 153, 15.24),
                array('db.password', null, 'azertyroot1234#'),
                array('toto', 'some_value', false),
            ), <<<RESULT
|--------------------------------------------|
| Variable    | Dev        | Prod            |
|--------------------------------------------|
| x           | 153        | 15.24           |
| db.password | NULL       | azertyroot1234# |
| toto        | some_value | false           |
|--------------------------------------------|
RESULT
            ),
            array(array(
                array('Variable', 'Dev', 'Production'),
                array('x', 153, 15.24),
                array('db.password', null, 'root'),
                array('toto', '0', true),
            ), <<<RESULT
|---------------------------------|
| Variable    | Dev  | Production |
|---------------------------------|
| x           | 153  | 15.24      |
| db.password | NULL | root       |
| toto        | 0    | true       |
|---------------------------------|
RESULT
            ),            
        );
    }
}