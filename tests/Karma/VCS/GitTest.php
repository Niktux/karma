<?php

namespace Karma\VCS;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;
use PHPUnit\Framework\TestCase;

class GitTest extends TestCase
{
    use \Xpmock\TestCaseTrait;

    private
        $fs,
        $git;

    protected function setUp()
    {
        $content1 = <<< GIT_IGNORE
i1.yml
i2.yml
GIT_IGNORE;

        $content2 = <<< GIT_IGNORE
# trap.yml
i3.yml
GIT_IGNORE;

        $this->fs = new Filesystem(new InMemory(array(
            '.gitignore' => $content1,
            'path/to/.gitignore' => $content2,
            'other/path/gitignore' => 'nonignored.yml',
            'anotherFile' => 'some content',
        )));

        $command = $this->mock('Karma\VCS\Git\CommandLauncher')
            ->initialize()
            ->run($this->returnValue('t1.yml' . "\n" . 't2.yml'))
            ->new();

        $this->git = new Git($this->fs, '.', $command);
    }

    /**
     * @dataProvider providerTestIsTracked
     */
    public function testIsTracked($file, $expected)
    {
        $this->assertSame($expected, $this->git->isTracked($file));
    }

    public function providerTestIsTracked()
    {
        return array(
            array('i1.yml', false),
            array('i2.yml', false),
            array('i3.yml', false),
            array('trap.yml', false),
            array('other.yml', false),
            array('nonignored.yml', false),
            array('i4.yml', false),

            array('t1.yml', true),
            array('t2.yml', true),
        );
    }

    /**
     * @dataProvider providerTestIsIgnored
     */
    public function testIsIgnored($file, $expected)
    {
        $this->assertSame($expected, $this->git->isIgnored($file));
    }

    public function providerTestIsIgnored()
    {
        return array(
            array('i1.yml', true),
            array('i2.yml', true),
            array('i3.yml', true),

            array('trap.yml', false),
            array('other.yml', false),
            array('nonignored.yml', false),
            array('i4.yml', false),
        );
    }

    /**
     * @dataProvider providerTestIgnoreFile
     */
    public function testIgnoreFile($filepath, $nbOccurenceInGitIgnoreBefore, $nbOccurenceInGitIgnoreAfter)
    {
        $this->assertSame($nbOccurenceInGitIgnoreBefore, substr_count($this->fs->read('.gitignore'), $filepath));

        $this->git->ignoreFile($filepath);

        $this->assertSame(
            $nbOccurenceInGitIgnoreAfter,
            substr_count($this->fs->read('.gitignore'), $filepath),
            "$filepath must be found $nbOccurenceInGitIgnoreAfter time(s) in .gitignore"
        );
    }

    public function providerTestIgnoreFile()
    {
        return array(
            array('newIgnored.yml', 0, 1),
            array('i1.yml', 1, 1),
            array('i3.yml', 0, 0),
        );
    }

    public function testMissingMainGitIgnoreFile()
    {
        $mainGitIgnoreFile = '.gitignore';

        $this->fs->delete($mainGitIgnoreFile);
        $this->assertFalse($this->fs->has($mainGitIgnoreFile));

        $this->git->ignoreFile('someFile');
        $this->assertTrue($this->fs->has($mainGitIgnoreFile), '.gitignore must be created when needed after ignoring a file');
    }

    /**
     * @dataProvider providerTestComment
     */
    public function testComment($contentBeforeLines, $contentAfterLines)
    {
        $contentBefore = implode("\n", $contentBeforeLines);
        $contentAfter = implode("\n", $contentAfterLines);

        $mainGitIgnoreFile = '.gitignore';
        $this->fs->write($mainGitIgnoreFile, $contentBefore, true);

        $this->git->ignoreFile('someFile');

        $this->assertSame($contentAfter, $this->fs->read($mainGitIgnoreFile));
    }

    public function providerTestComment()
    {
        return array(
            'no change' => array(array(
                Git::KARMA_COMMENT,
                'someFile',
            ), array(
                Git::KARMA_COMMENT,
                'someFile',
            )),

            'comment in middle' => array(array(
                'cache/',
                Git::KARMA_COMMENT,
                'anyFile',
                '',
                'vendor/',
            ), array(
                'cache/',
                Git::KARMA_COMMENT,
                'someFile',
                'anyFile',
                '',
                'vendor/',
            )),

            'comment is missing' => array(array(
                'cache/',
                'vendor/',
            ), array(
                'cache/',
                'vendor/',
                '',
                Git::KARMA_COMMENT,
                'someFile',
            )),

            'comment is missing but an empty line exists' => array(array(
                'cache/',
                'vendor/',
                '',
            ), array(
                'cache/',
                'vendor/',
                '',
                Git::KARMA_COMMENT,
                'someFile',
            )),
        );
    }
}
