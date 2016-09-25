<?php

namespace Karma\Formatters;

class RulesTests extends \PHPUnit_Framework_TestCase
{
    private
        $formatter;

    protected function setUp()
    {
        $rules = array(
            ' <true>' => 'string true',
            '<false> ' => 'string false',
            '<null>' => 0,
            'foobar' => 'barfoo',
            'footrue' => true,
            ' <string> ' => '"<string>"',
        );

        $this->formatter = new Rules($rules);
    }

    /**
     * @dataProvider providerTestFormat
     */
    public function testFormat($input, $expected)
    {
        $result = $this->formatter->format($input);
        $this->assertSame($expected, $result);
    }

    public function providerTestFormat()
    {
        return array(
            'boolean true' => array(true, 'string true'),
            'string true' => array('true', '"true"'),
            'other string true' => array('<true>', '"<true>"'),
            'footrue' => array('footrue', true),

            'boolean false' => array(false, 'string false'),
            'string false' => array('false', '"false"'),
            'other string false' => array('<false>', '"<false>"'),

            'null' => array(null, 0),
            'string null' => array('null', '"null"'),
            'other string null' => array('<null>', '"<null>"'),

            'zero' => array(0, 0),
            'string zero' => array('0', '"0"'),
            'other string zero' => array('<0>', '"<0>"'),

            'foo' => array('foo', '"foo"'),
            'foobar' => array('foobar', 'barfoo'),
            'barfoobarfoo' => array('barfoobarfoo', '"barfoobarfoo"'),
        );
    }
}
