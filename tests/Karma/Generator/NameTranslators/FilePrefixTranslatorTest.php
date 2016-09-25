<?php

namespace Karma\Generator\NameTranslators;

class FilePrefixTranslatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerTestTranslate
     */
    public function testTranslate($file, $variable, $expected)
    {
        $t = new FilePrefixTranslator();

        $this->assertSame($expected, $t->translate($file, $variable));
    }

    public function providerTestTranslate()
    {
        return array(
            // no prefix for master.conf
            array('master.conf', 'burger', 'burger'),
            array('master.conf', 'worker.level', 'worker.level'),
            array('master.conf', 'app.master.var.pony.master', 'app.master.var.pony.master'),
            array('master.conf', 'master', 'master'),
            array('master.conf', 'master.master', 'master.master'),

            // prefix for other files
            array('db.conf', 'port', 'db.port'),
            array('db.conf', 'db', 'db.db'),
            array('logger.conf', 'worker.level', 'logger.worker.level'),
            array('logger.conf', 'worker.file', 'logger.worker.file'),
            array('logger_levels.conf', 'worker', 'logger_levels.worker'),
            array('ldap.conf', 'host', 'ldap.host'),
            array('ldap.auth.conf', 'host', 'ldap.auth.host'),
            array('app.conf', 'app.debug', 'app.app.debug'),
        );
    }

    /**
     * @dataProvider providerTestTranslateWithDifferentMasterFile
     */
    public function testTranslateWithDifferentMasterFile($file, $variable, $expected)
    {
        $t = new FilePrefixTranslator();
        $t->changeMasterFile('db.conf');

        $this->assertSame($expected, $t->translate($file, $variable));
    }

    public function providerTestTranslateWithDifferentMasterFile()
    {
        return array(
            // no prefix for master.conf
            array('master.conf', 'burger', 'master.burger'),
            array('master.conf', 'worker.level', 'master.worker.level'),
            array('master.conf', 'app.master.var.pony.master', 'master.app.master.var.pony.master'),
            array('master.conf', 'master', 'master.master'),
            array('master.conf', 'master.master', 'master.master.master'),

            // prefix for other files
            array('db.conf', 'port', 'port'),
            array('db.conf', 'db', 'db'),
            array('logger.conf', 'worker.level', 'logger.worker.level'),
            array('logger.conf', 'worker.file', 'logger.worker.file'),
            array('logger_levels.conf', 'worker', 'logger_levels.worker'),
            array('ldap.conf', 'host', 'ldap.host'),
            array('ldap.auth.conf', 'host', 'ldap.auth.host'),
            array('app.conf', 'app.debug', 'app.app.debug'),
        );
    }

    /**
     * @dataProvider providerTestTranslateWithPrefixForMasterFile
     */
    public function testTranslateWithPrefixForMasterFile($file, $variable, $expected)
    {
        $t = new FilePrefixTranslator();
        $t->setPrefixForMasterFile('pony');

        $this->assertSame($expected, $t->translate($file, $variable));
    }

    public function providerTestTranslateWithPrefixForMasterFile()
    {
        return array(
            // no prefix for master.conf
            array('master.conf', 'burger', 'pony.burger'),
            array('master.conf', 'worker.level', 'pony.worker.level'),
            array('master.conf', 'app.master.var.pony.master', 'pony.app.master.var.pony.master'),
            array('master.conf', 'master', 'pony.master'),
            array('master.conf', 'master.master', 'pony.master.master'),

            // prefix for other files
            array('db.conf', 'port', 'db.port'),
            array('db.conf', 'db', 'db.db'),
            array('logger.conf', 'worker.level', 'logger.worker.level'),
            array('logger.conf', 'worker.file', 'logger.worker.file'),
            array('logger_levels.conf', 'worker', 'logger_levels.worker'),
            array('ldap.conf', 'host', 'ldap.host'),
            array('ldap.auth.conf', 'host', 'ldap.auth.host'),
            array('app.conf', 'app.debug', 'app.app.debug'),
        );
    }
}