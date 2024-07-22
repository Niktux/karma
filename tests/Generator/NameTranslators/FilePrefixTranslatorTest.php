<?php

declare(strict_types = 1);

namespace Karma\Generator\NameTranslators;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FilePrefixTranslatorTest extends TestCase
{
    #[DataProvider('providerTestTranslate')]
    public function testTranslate(string $file, string $variable, string $expected): void
    {
        $t = new FilePrefixTranslator();

        self::assertSame($expected, $t->translate($file, $variable));
    }

    public static function providerTestTranslate(): array
    {
        return [
            // no prefix for master.conf
            ['master.conf', 'burger', 'burger'],
            ['master.conf', 'worker.level', 'worker.level'],
            ['master.conf', 'app.master.var.pony.master', 'app.master.var.pony.master'],
            ['master.conf', 'master', 'master'],
            ['master.conf', 'master.master', 'master.master'],

            // prefix for other files
            ['db.conf', 'port', 'db.port'],
            ['db.conf', 'db', 'db.db'],
            ['logger.conf', 'worker.level', 'logger.worker.level'],
            ['logger.conf', 'worker.file', 'logger.worker.file'],
            ['logger_levels.conf', 'worker', 'logger_levels.worker'],
            ['ldap.conf', 'host', 'ldap.host'],
            ['ldap.auth.conf', 'host', 'ldap.auth.host'],
            ['app.conf', 'app.debug', 'app.app.debug'],
        ];
    }

    #[DataProvider('providerTestTranslateWithDifferentMasterFile')]
    public function testTranslateWithDifferentMasterFile(string $file, string $variable, string $expected): void
    {
        $t = new FilePrefixTranslator();
        $t->changeMasterFile('db.conf');

        self::assertSame($expected, $t->translate($file, $variable));
    }

    public static function providerTestTranslateWithDifferentMasterFile(): array
    {
        return [
            // no prefix for master.conf
            ['master.conf', 'burger', 'master.burger'],
            ['master.conf', 'worker.level', 'master.worker.level'],
            ['master.conf', 'app.master.var.pony.master', 'master.app.master.var.pony.master'],
            ['master.conf', 'master', 'master.master'],
            ['master.conf', 'master.master', 'master.master.master'],

            // prefix for other files
            ['db.conf', 'port', 'port'],
            ['db.conf', 'db', 'db'],
            ['logger.conf', 'worker.level', 'logger.worker.level'],
            ['logger.conf', 'worker.file', 'logger.worker.file'],
            ['logger_levels.conf', 'worker', 'logger_levels.worker'],
            ['ldap.conf', 'host', 'ldap.host'],
            ['ldap.auth.conf', 'host', 'ldap.auth.host'],
            ['app.conf', 'app.debug', 'app.app.debug'],
        ];
    }

    #[DataProvider('providerTestTranslateWithPrefixForMasterFile')]
    public function testTranslateWithPrefixForMasterFile(string $file, string $variable, string $expected): void
    {
        $t = new FilePrefixTranslator();
        $t->setPrefixForMasterFile('pony');

        self::assertSame($expected, $t->translate($file, $variable));
    }

    public static function providerTestTranslateWithPrefixForMasterFile(): array
    {
        return [
            // no prefix for master.conf
            ['master.conf', 'burger', 'pony.burger'],
            ['master.conf', 'worker.level', 'pony.worker.level'],
            ['master.conf', 'app.master.var.pony.master', 'pony.app.master.var.pony.master'],
            ['master.conf', 'master', 'pony.master'],
            ['master.conf', 'master.master', 'pony.master.master'],

            // prefix for other files
            ['db.conf', 'port', 'db.port'],
            ['db.conf', 'db', 'db.db'],
            ['logger.conf', 'worker.level', 'logger.worker.level'],
            ['logger.conf', 'worker.file', 'logger.worker.file'],
            ['logger_levels.conf', 'worker', 'logger_levels.worker'],
            ['ldap.conf', 'host', 'ldap.host'],
            ['ldap.auth.conf', 'host', 'ldap.auth.host'],
            ['app.conf', 'app.debug', 'app.app.debug'],
        ];
    }
}