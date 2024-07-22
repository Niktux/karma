<?php

declare(strict_types = 1);

namespace Karma\Generator\NameTranslators;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NullTranslatorTest extends TestCase
{
    #[DataProvider('providerTestTranslate')]
    public function testTranslate(string $file, string $variable, string $expected): void
    {
        $t = new NullTranslator();

        self::assertSame($expected, $t->translate($file, $variable));
    }

    public static function providerTestTranslate(): array
    {
        return [
            ['master.conf', 'burger', 'burger'],
            ['master.conf', 'worker.level', 'worker.level'],
            ['master.conf', 'app.master.var.pony.master', 'app.master.var.pony.master'],
            ['master.conf', 'master', 'master'],
            ['master.conf', 'master.master', 'master.master'],

            ['db.conf', 'port', 'port'],
            ['db.conf', 'db', 'db'],
            ['logger.conf', 'worker.level', 'worker.level'],
            ['logger.conf', 'worker.file', 'worker.file'],
            ['logger_levels.conf', 'worker', 'worker'],
            ['ldap.conf', 'host', 'host'],
            ['ldap.auth.conf', 'host', 'host'],
            ['app.conf', 'app.debug', 'app.debug'],
        ];
    }
}