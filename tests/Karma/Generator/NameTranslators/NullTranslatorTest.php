<?php

namespace Karma\Generator\NameTranslators;

class NullTranslatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerTestTranslate
     */
    public function testTranslate($file, $variable, $expected)
    {
        $t = new NullTranslator();

        $this->assertSame($expected, $t->translate($file, $variable));
    }

    public function providerTestTranslate()
    {
        return array(

            array('master.conf', 'burger', 'burger'),
            array('master.conf', 'worker.level', 'worker.level'),
            array('master.conf', 'app.master.var.pony.master', 'app.master.var.pony.master'),
            array('master.conf', 'master', 'master'),
            array('master.conf', 'master.master', 'master.master'),

            array('db.conf', 'port', 'port'),
            array('db.conf', 'db', 'db'),
            array('logger.conf', 'worker.level', 'worker.level'),
            array('logger.conf', 'worker.file', 'worker.file'),
            array('logger_levels.conf', 'worker', 'worker'),
            array('ldap.conf', 'host', 'host'),
            array('ldap.auth.conf', 'host', 'host'),
            array('app.conf', 'app.debug', 'app.debug'),
        );
    }
}