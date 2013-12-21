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
        $this->assertSame($expected, $table->render());
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
    
    public function testValueRenderingFunction()
    {
        $table = new CliTable(array(
            array('', 'e1', 'e2'),
            array('a', 'a1', 'a2'),
            array('B', 'B1', 'B2'),
            array('cccc', 'cccc1', 'cccc2'),
        ));
        
        $table->setValueRenderingFunction(function ($value){
            return strtoupper($value);
        });
        
        $expected = <<<RESULT
|----------------------|
|      | E1    | E2    |
|----------------------|
| A    | A1    | A2    |
| B    | B1    | B2    |
| CCCC | CCCC1 | CCCC2 |
|----------------------|
RESULT;
        
        $this->assertSame($expected, $table->render());
    }
    
    public function testEnableFormattingTags()
    {
        $table = new CliTable(array(
            array('', 'e1', 'e2'),
            array('a', 'a1', 'a2'),
            array('B', 'B1', 'B2'),
            array('<color=blue>cccc</color>', 'cccc1', 'cccc2'),
        ));
    
        $table->enableFormattingTags();
    
        // Expects thats tags have no impact on column size computation
        $expected = <<<RESULT
|----------------------|
|      | e1    | e2    |
|----------------------|
| a    | a1    | a2    |
| B    | B1    | B2    |
| <color=blue>cccc</color> | cccc1 | cccc2 |
|----------------------|
RESULT;
    
        $this->assertSame($expected, $table->render());
    }    
}