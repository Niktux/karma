<?php

declare(strict_types = 1);

namespace Karma\Generator\ConfigurationFileGenerators;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;
use Karma\Configuration\Reader;
use Karma\Application;
use Karma\Configuration\Parser;
use Karma\Generator\VariableProvider;
use Karma\Generator\NameTranslators\FilePrefixTranslator;
use Karma\Generator\NameTranslators\NullTranslator;
use PHPUnit\Framework\TestCase;

class YamlGeneratorTest extends TestCase
{
    private
        $fs,
        $variableProvider,
        $generator;

    protected function setUp(): void
    {
        $this->fs = new Filesystem(new InMemory());

        $parser = $this->initializeParserAndConfFiles();
        $variables = $parser->parse(Application::DEFAULT_MASTER_FILE);
        $reader = new Reader($variables, $parser->getExternalVariables(), $parser->getGroups(), $parser->getDefaultEnvironmentsForGroups());

        $this->variableProvider = new VariableProvider($parser);
        $this->variableProvider->setNameTranslator(new FilePrefixTranslator());

        $this->generator = new YamlGenerator($this->fs, $reader, $this->variableProvider);
    }

    private function initializeParserAndConfFiles()
    {
        $masterContent = <<<CONFFILE
[externals]
external.conf

[includes]
db.conf

[variables]
logger.worker.level:
    staging = info
    default = warning

logger.worker.file:
    default = worker.log

logger.queue.file:
    default = queue.log

logger.debug:
    dev = true
    default = false
CONFFILE;

        $externalContent = <<<CONFFILE
[variables]
# db.conf
pass:
    prod = veryComplexPass
CONFFILE;

        $dbContent = <<<CONFFILE
[variables]
@pass:
    dev = 1234
    prod = <external>
    default = root

host:
    dev = dev-sql
    staging = [stg-sql1, stg-sql2, stg-sql3 ]
CONFFILE;

        $files = array(
            Application::DEFAULT_MASTER_FILE => $masterContent,
            'external.conf' => $externalContent,
            'db.conf' => $dbContent,
        );

        $parser = new Parser(new Filesystem(new InMemory($files)));
        $parser->enableIncludeSupport()
            ->enableExternalSupport();

        return $parser;
    }

    public function testDryRun()
    {
        $this->assertNumberOfFilesIs(0);

        $this->generator->setDryRun();
        $this->generator->generate('dev');
        $this->assertNumberOfFilesIs(0);

        $this->generator->enableBackup();
        $this->generator->generate('dev');
        $this->assertNumberOfFilesIs(0);
    }

    public function testGenerateForDev()
    {
        $this->generator->generate('dev');

        $this->assertNumberOfFilesIs(2);
        $this->assertHasFile('db.yml');
        $this->assertHasFile('logger.yml');

        $this->assertFileContains('db.yml', <<< YAML
pass: 1234
host: dev-sql

YAML
);

        $this->assertFileContains('logger.yml', <<< YAML
worker:
    level: warning
    file: worker.log
queue:
    file: queue.log
debug: true

YAML
);
    }

    public function testGenerateForStaging()
    {
        $this->generator->generate('staging');

        $this->assertNumberOfFilesIs(2);
        $this->assertHasFile('db.yml');
        $this->assertHasFile('logger.yml');

        $this->assertFileContains('db.yml', <<< YAML
pass: root
host:
    - stg-sql1
    - stg-sql2
    - stg-sql3

YAML
);

        $this->assertFileContains('logger.yml', <<< YAML
worker:
    level: info
    file: worker.log
queue:
    file: queue.log
debug: false

YAML
);
    }

    public function testOverride()
    {
        $this->fs->write('db.yml', 'burger over ponies');

        $this->generator->generate('dev');

        $this->assertFileContains('db.yml', <<< YAML
pass: 1234
host: dev-sql

YAML
        );
    }

    public function testBackup()
    {
        $this->fs->write('db.yml', 'burger over ponies');

        $this->generator->enableBackup();
        $this->generator->generate('dev');

        $this->assertFileContains('db.yml', <<< YAML
pass: 1234
host: dev-sql

YAML
        );

        $this->assertHasFile('db.yml~');
        $this->assertFileContains('db.yml~', 'burger over ponies');
    }

    public function testOverrideBackupedFiles()
    {
        $this->fs->write('db.yml', 'burger over ponies');
        $this->fs->write('db.yml~', 'old backup');

        $this->generator->enableBackup();
        $this->generator->generate('dev');

        $this->assertFileContains('db.yml', <<< YAML
pass: 1234
host: dev-sql

YAML
        );

        $this->assertHasFile('db.yml~');
        $this->assertFileContains('db.yml~', 'burger over ponies');
    }

    public function testGenerateForDevWithStagingSystem()
    {
        $this->generator->setSystemEnvironment('staging');
        $this->generator->generate('dev');

        $this->assertNumberOfFilesIs(2);
        $this->assertHasFile('db.yml');
        $this->assertHasFile('logger.yml');

        $this->assertFileContains('db.yml', <<< YAML
pass: root
host: dev-sql

YAML
        );
    }

    private function assertNumberOfFilesIs($expectedCount)
    {
        $this->assertCount($expectedCount, $this->fs->keys(), "Filesystem must contain exactly $expectedCount files");
    }

    private function assertHasFile($filename)
    {
        $this->assertTrue($this->fs->has($filename), "$filename should exist");
    }

    private function assertFileContains($filename, $content)
    {
        $this->assertSame($content, $this->fs->read($filename), "Unexpected content for file $filename");
    }

    public function testVariableNameTooShort()
    {
        $this->expectException(\RuntimeException::class);

        $this->variableProvider->setNameTranslator(new NullTranslator());

        // Throw exception because of variable "pass"
        $this->generator->generate('dev');
    }
}
