<?php

declare(strict_types = 1);

namespace Karma\Configuration;

use Karma\Configuration\InMemoryReader;
use Karma\Configuration;
use PHPUnit\Framework\TestCase;

class InMemoryReaderTest extends TestCase
{
    private InMemoryReader
        $reader;

    protected function setUp(): void
    {
        $this->reader = new InMemoryReader([
            '@foo:dev' => 'foodev',
            '@foo:prod' => 'fooprod',
            'bar:dev' => 'bardev',
            'baz:recette' => 'bazrecette',
        ]);
    }

    /**
     * @dataProvider providerTestRead
     */
    public function testRead($variable, $environment, $expected)
    {
        self::assertSame($expected, $this->reader->read($variable, $environment));
    }

    /**
     * @dataProvider providerTestRead
     */
    public function testReadWithDefaultEnvironment($variable, $environment, $expected)
    {
        $this->reader->setDefaultEnvironment($environment);
        self::assertSame($expected, $this->reader->read($variable));
    }

    public function providerTestRead()
    {
        return [
            ['baz', 'recette', 'bazrecette'],
            ['bar', 'dev', 'bardev'],
            ['foo', 'prod', 'fooprod'],
            ['foo', 'dev', 'foodev'],
        ];
    }

    public function testVariableDoesNotExist()
    {
        $this->expectException(\RuntimeException::class);

        $this->reader->read('doesnotexist', 'dev');
    }

    public function testGetAllVariables()
    {
        $variables = $this->reader->getAllVariables();
        sort($variables);

        $expected = ['foo', 'bar', 'baz'];
        sort($expected);

        self::assertSame($expected, $variables);
    }

    /**
     * @dataProvider providerTestGetAllValuesForEnvironment
     */
    public function testGetAllValuesForEnvironment($environment, array $expectedValues)
    {
        $variables = $this->reader->getAllValuesForEnvironment($environment);
        self::assertIsArray($variables);

        $keys = array_keys($variables);
        $expectedKeys = array_keys($expectedValues);
        sort($keys);
        sort($expectedKeys);
        self::assertSame($expectedKeys, $keys);

        foreach($keys as $variable)
        {
            self::assertSame($expectedValues[$variable], $variables[$variable], "Value for $variable");
        }
    }

    public function providerTestGetAllValuesForEnvironment()
    {
        return [
            ['dev', [
                'foo' => 'foodev',
                'bar' => 'bardev',
                'baz' => Configuration::NOT_FOUND,
            ]],
            ['recette', [
                'foo' => Configuration::NOT_FOUND,
                'bar' => Configuration::NOT_FOUND,
                'baz' => 'bazrecette',
            ]],
            ['prod', [
                'foo' => 'fooprod',
                'bar' => Configuration::NOT_FOUND,
                'baz' => Configuration::NOT_FOUND,
            ]],
        ];
    }

    public function testOverrideVariable()
    {
        $environment = 'dev';

        self::assertSame('foodev', $this->reader->read('foo', $environment));
        self::assertSame('bardev', $this->reader->read('bar', $environment));

        $this->reader->overrideVariable('foo', 'foofoo');

        self::assertSame('foofoo', $this->reader->read('foo', $environment));
        self::assertSame('bardev', $this->reader->read('bar', $environment));

        $this->reader->overrideVariable('bar', null);

        self::assertSame('foofoo', $this->reader->read('foo', $environment));
        self::assertSame(null, $this->reader->read('bar', $environment));
    }

    public function testCustomData()
    {
        $var = 'param';

        $reader = new InMemoryReader([
            'param:dev' => '${param}',
            'param:staging' => 'Some${nested}param',
            'param:demo' => ['Some${nested}param', '${param}'],
        ]);

        self::assertSame('${param}', $reader->read($var, 'dev'));
        self::assertSame('Some${nested}param', $reader->read($var, 'staging'));
        self::assertSame(['Some${nested}param', '${param}'], $reader->read($var, 'demo'));

        $reader->setCustomData('PARAM', 'caseSensitive');

        self::assertSame('${param}', $reader->read($var, 'dev'));
        self::assertSame('Some${nested}param', $reader->read($var, 'staging'));
        self::assertSame(['Some${nested}param', '${param}'], $reader->read($var, 'demo'));

        $reader->setCustomData('param', 'foobar');

        self::assertSame('foobar', $reader->read($var, 'dev'));
        self::assertSame('Some${nested}param', $reader->read($var, 'staging'));
        self::assertSame(['Some${nested}param', 'foobar'], $reader->read($var, 'demo'));

        $reader->setCustomData('nested', 'Base');

        self::assertSame('foobar', $reader->read($var, 'dev'));
        self::assertSame('SomeBaseparam', $reader->read($var, 'staging'));
        self::assertSame(['SomeBaseparam', 'foobar'], $reader->read($var, 'demo'));
    }

    /**
     * @dataProvider providerTestIsSystem
     */
    public function testIsSystem($variable, $expected)
    {
        self::assertSame($expected, $this->reader->isSystem($variable));
    }

    public function providerTestIsSystem()
    {
        return [
            ['foo', true],
            ['bar', false],
            ['does_not_exist', false],
        ];
    }
}
