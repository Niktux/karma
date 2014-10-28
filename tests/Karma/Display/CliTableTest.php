<?php

namespace Karma\Display;

use Karma\Display\CliTable;

/**
 * @group Unix
 */
class CliTableTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerTestRender
     */
    public function testRender($headers, $input, $expected)
    {
        $table = new CliTable($input);
        $table->setHeaders($headers);
        
        $this->assertSame($expected, $table->render());
    }
    
    public function providerTestRender()
    {
        return array(
            array(array('Variable', 'Dev', 'Prod'), array(
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
            array(array('Variable', 'Dev', 'Production'), array(
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
            array('a', 'a1', 'a2'),
            array('B', 'B1', 'B2'),
            array('cccc', 'cccc1', 'cccc2'),
        ));
        
        $table->setHeaders(array('', 'e1', 'e2'))
              ->setValueRenderingFunction(function ($value){
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
    
    public function testWeirdCharacter()
    {
        $table = new CliTable(array(
            array('a', 'a1', 'a2'),
            array('B', 'B1', 'B2'),
            array('x<y2', 'cccc1', 'cccc2'),
        ));
        
        $table->setHeaders(array('', 'e1', 'e2'))
              ->enableFormattingTags();
        
        $expected = <<<RESULT
|----------------------|
|      | e1    | e2    |
|----------------------|
| a    | a1    | a2    |
| B    | B1    | B2    |
| x<y2 | cccc1 | cccc2 |
|----------------------|
RESULT;
        
        $this->assertSame($expected, $table->render());
    }
    
    public function testEnableFormattingTags()
    {
        $table = new CliTable(array(
            array('a', 'a1', 'a2'),
            array('B', 'B1', 'B2'),
            array('<color=blue>c</color>', 'cccc1', 'cccc2'),
        ));

        $table->setHeaders(array('1234', 'e1', 'e2'))
              ->enableFormattingTags();
    
        // Expects thats tags have no impact on column size computation
        $expected = <<<RESULT
|----------------------|
| 1234 | e1    | e2    |
|----------------------|
| a    | a1    | a2    |
| B    | B1    | B2    |
| <color=blue>c</color>    | cccc1 | cccc2 |
|----------------------|
RESULT;
    
        $this->assertSame($expected, $table->render());
    }    
    
    /**
     * @dataProvider providerTestSanityChecks
     * @expectedException \InvalidArgumentException
     */
    public function testSanityChecks(array $values)
    {
        $table = new CliTable($values);
        $table->render();
    }
    
    public function providerTestSanityChecks()
    {
        return array(
            'one dim array' => array(array('a', 'b', 'c')),
            'one dim assoc array' => array(array('a' => 0, 'b' => 1, 'c' => 2)),
                        
            'two dim array but inconsistent row length #1' => array(array(array('a'), array('b', 'c'))),
            'two dim array but inconsistent row length #2' => array(array(array('a', 'b'), array('c'))),
        );
    }
    
    /**
     * @dataProvider providerTestDisplayKeys
     */
    public function testDisplayKeys($enableKeys, $expected)
    {
        $values = array(
            'key1' => array('a', 'bb'),
            'key2' => array(true, 3),
            array(42, 51),
            'key4' => array(null, 12),
            array(82, 86),
        );
        
        $table = new CliTable($values);
        $table->setHeaders(array('colA', 'colB'));
        
        $result = $table->displayKeys($enableKeys)
            ->render();
        
        $this->assertSame($expected, $result);
    }
    
    public function providerTestDisplayKeys()
    {
        return array(
            array(true, <<<RESULT
|--------------------|
|      | colA | colB |
|--------------------|
| key1 | a    | bb   |
| key2 | true | 3    |
| 0    | 42   | 51   |
| key4 | NULL | 12   |
| 1    | 82   | 86   |
|--------------------|
RESULT
            ),
            array(false, <<<RESULT
|-------------|
| colA | colB |
|-------------|
| a    | bb   |
| true | 3    |
| 42   | 51   |
| NULL | 12   |
| 82   | 86   |
|-------------|
RESULT
            ),                     
        );
    }
}