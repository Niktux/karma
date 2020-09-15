<?php

declare(strict_types = 1);

namespace Karma;

use Gaufrette\Filesystem;
use Psr\Log\NullLogger;
use Karma\FormatterProviders\NullProvider;

class Hydrator implements ConfigurableProcessor
{
    use \Karma\Logging\LoggerAware;

    private const
        TODO_VALUE = '__TODO__',
        FIXME_VALUE = '__FIXME__',
        VARIABLE_REGEX = '~<%(?P<variableName>[A-Za-z0-9_\.\-]+)%>~';

    private Filesystem
        $sources,
        $target;
    private Configuration
        $reader;
    private Finder
        $finder;
    private string
        $suffix;
    private bool
        $dryRun,
        $enableBackup,
        $nonDistFilesOverwriteAllowed;
    private FormatterProvider
        $formatterProvider;
    private ?string
        $currentFormatterName,
        $currentTargetFile,
        $systemEnvironment;
    private array
        $unusedVariables,
        $unvaluedVariables,
        $hydratedFiles;

    public function __construct(Filesystem $sources, Filesystem $target, Configuration $reader, Finder $finder, FormatterProvider $formatterProvider = null)
    {
        $this->logger = new NullLogger();

        $this->sources = $sources;
        $this->target = $target;
        $this->reader = $reader;
        $this->finder = $finder;

        $this->suffix = Application::DEFAULT_DISTFILE_SUFFIX;
        $this->dryRun = false;
        $this->enableBackup = false;
        $this->nonDistFilesOverwriteAllowed = false;

        $this->formatterProvider = $formatterProvider ?? new NullProvider();

        $this->currentFormatterName = null;
        $this->currentTargetFile = null;
        $this->systemEnvironment = null;
        $this->unusedVariables = array_flip($reader->getAllVariables());
        $this->unvaluedVariables = [];
        $this->hydratedFiles = [];
    }

    public function setSuffix(string $suffix): ConfigurableProcessor
    {
        $this->suffix = $suffix;

        return $this;
    }

    public function setDryRun(bool $value = true): ConfigurableProcessor
    {
        $this->dryRun = $value;

        return $this;
    }

    public function enableBackup(bool $value = true): ConfigurableProcessor
    {
        $this->enableBackup = $value;

        return $this;
    }
    
    public function allowNonDistFilesOverwrite(bool $nonDistFilesOverwriteAllowed = true): ConfigurableProcessor
    {
        $this->nonDistFilesOverwriteAllowed = $nonDistFilesOverwriteAllowed;

        return $this;
    }

    public function setFormatterProvider(FormatterProvider $formatterProvider): ConfigurableProcessor
    {
        $this->formatterProvider = $formatterProvider;

        return $this;
    }

    public function setSystemEnvironment(?string $environment): ConfigurableProcessor
    {
        $this->systemEnvironment = $environment;

        return $this;
    }

    public function hydrate(string $environment): void
    {
        $files = $this->collectFiles();

        foreach($files as $file)
        {
            $this->hydrateFile($file, $environment);
        }

        if($this->nonDistFilesOverwriteAllowed === true)
        {
            $this->copyNonDistFiles();
        }

        $this->info(sprintf(
           '%d files generated',
            count($files)
        ));
    }

    private function collectFiles(): iterable
    {
        $pattern = sprintf('.*%s$', preg_quote($this->suffix, '~'));
        
        return $this->finder->findFiles(sprintf('~%s~', $pattern));
    }
    
    private function copyNonDistFiles(): void
    {
        $filesToCopy = $this->collectNonDistFiles();

        foreach($filesToCopy as $file)
        {
            $this->target->write($file, $this->sources->read($file));
        }
    }
    
    private function collectNonDistFiles(): iterable
    {
        $pattern = sprintf('(?<!%s)$', preg_quote($this->suffix, '~'));
        
        return $this->finder->findFiles(sprintf('~%s~', $pattern));
    }
    

    private function hydrateFile(string $file, string $environment): void
    {
        $this->currentTargetFile = preg_replace(sprintf(
            '~(.*)(%s)$~',
            preg_quote($this->suffix, '~')
        ), '$1', $file);

        if($this->nonDistFilesOverwriteAllowed)
        {
            $this->currentTargetFile = (new \SplFileInfo($this->currentTargetFile))->getFilename();
        }

        $content = (string) $this->sources->read($file);
        $replacementCounter = $this->parseFileDirectives($file, $content, $environment);

        $targetContent = $this->injectValues($file, $content, $environment, $replacementCounter);

        $this->debug("Write $this->currentTargetFile");

        if($this->dryRun === false)
        {
            if($this->hasBeenHydrated($this->currentTargetFile) && $this->nonDistFilesOverwriteAllowed)
            {
                throw new \RuntimeException(sprintf('The fileName "%s" is defined in 2 config folders (not allowed with targetPath config enabled)', $this->currentTargetFile));
            }

            $this->backupFile($this->currentTargetFile);
            $this->target->write($this->currentTargetFile, $targetContent, true);
        }

        $this->hydratedFiles[$this->currentTargetFile] = $replacementCounter;
    }

    private function hasBeenHydrated(string $file): bool
    {
        return array_key_exists($file, $this->hydratedFiles);
    }

    private function parseFileDirectives(string $file, string & $fileContent, string $environment): int
    {
        $this->currentFormatterName = null;

        $this->parseFormatterDirective($file, $fileContent);
        $replacementCounter = $this->parseListDirective($file, $fileContent, $environment);

        $fileContent = $this->removeFileDirectives($fileContent);

        return $replacementCounter;
    }

    private function parseFormatterDirective(string $file, string $fileContent): void
    {
        if($count = preg_match_all('~<%\s*karma:formatter\s*=\s*(?P<formatterName>[^%]+)%>~', $fileContent, $matches))
        {
            if($count !== 1)
            {
                throw new \RuntimeException(sprintf(
                    'Syntax error in %s : only one formatter directive is allowed (%d found)',
                    $file,
                    $count
                ));
            }

            $this->currentFormatterName = strtolower(trim($matches['formatterName'][0]));
        }
    }

    private function parseListDirective(string $file, string & $fileContent, string $environment): int
    {
        $replacementCounter = 0;

        $regexDelimiter = '(delimiter="(?P<delimiterName>[^"]*)")?';
        $regexWrapper = '(wrapper="(?P<wrapperPrefix>[^"]*)":"(?P<wrapperSuffix>[^"]*)")?';
        $regex = '~<%\s*karma:list\s*var=(?P<variableName>[\S]+)\s*' . $regexDelimiter . '\s*' . $regexWrapper . '\s*%>~i';

        while(preg_match($regex, $fileContent, $matches))
        {
            $delimiter = $matches['delimiterName'] ?? '';

            $wrapper = ['prefix' => '', 'suffix' => ''];
            if(isset($matches['wrapperPrefix'], $matches['wrapperSuffix']))
            {
                $wrapper = [
                    'prefix' => $matches['wrapperPrefix'],
                    'suffix' => $matches['wrapperSuffix']
                ];
            }

            $generatedList = $this->generateContentForListDirective($matches['variableName'], $environment, $delimiter, $wrapper);
            $fileContent = str_replace($matches[0], $generatedList, $fileContent);

            $replacementCounter++;
        }

        $this->lookingForSyntaxErrorInListDirective($file, $fileContent);

        return $replacementCounter;
    }

    private function lookingForSyntaxErrorInListDirective(string $file, string $fileContent): void
    {
        if(preg_match('~<%.*karma\s*:\s*list\s*~i', $fileContent))
        {
            // karma:list detected but has not matches full regexp
            throw new \RuntimeException("Invalid karma:list directive in file $file");
        }
    }

    private function generateContentForListDirective(string $variable, string $environment, string $delimiter, array $wrapper): string
    {
        $values = $this->readValueToInject($variable, $environment);
        $formatter = $this->getFormatterForCurrentTargetFile();

        if(! is_array($values))
        {
            $values = [$values];
        }

        array_walk($values, static function (& $value) use ($formatter) {
            $value = $formatter->format($value);
        });

        $generated = implode($delimiter, $values);
        return sprintf(
            '%s%s%s',
            ! empty($generated) ? $wrapper['prefix'] : '',
            $generated,
            ! empty($generated) ? $wrapper['suffix'] : ''
        );
    }

    private function removeFileDirectives($fileContent)
    {
        return preg_replace('~(<%\s*karma:[^%]*%>\s*)~i', '', $fileContent);
    }

    private function injectValues(string $sourceFile, string $content, string $environment, int & $replacementCounter = 0): string
    {
        $replacementCounter += $this->injectScalarValues($content, $environment);
        $replacementCounter += $this->injectListValues($content, $environment);

        if($replacementCounter === 0)
        {
            $this->warning("No variable found in $sourceFile");
        }

        return $content;
    }

    private function readValueToInject(string $variableName, string $environment)
    {
        if($this->systemEnvironment !== null && $this->reader->isSystem($variableName) === true)
        {
            $environment = $this->systemEnvironment;
        }

        $this->markVariableAsUsed($variableName);

        $value = $this->reader->read($variableName, $environment);

        $this->checkValueIsAllowed($variableName, $environment, $value);

        return $value;
    }

    private function checkValueIsAllowed(string $variableName, string $environment, $value): void
    {
        if($value === self::FIXME_VALUE)
        {
            throw new \RuntimeException(sprintf(
                'Missing value for variable %s in environment %s (FIXME marker found)',
                $variableName,
                $environment
            ));
        }

        if($value === self::TODO_VALUE)
        {
            $this->unvaluedVariables[] = $variableName;
        }
    }

    private function getFormatterForCurrentTargetFile(): Formatter
    {
        $fileExtension = pathinfo($this->currentTargetFile, PATHINFO_EXTENSION);

        return $this->formatterProvider->getFormatter($fileExtension, $this->currentFormatterName);
    }

    private function injectScalarValues(string & $content, string $environment): int
    {
        $formatter = $this->getFormatterForCurrentTargetFile();

        $content = preg_replace_callback(self::VARIABLE_REGEX, function(array $matches) use($environment, $formatter)
        {
            $value = $this->readValueToInject($matches['variableName'], $environment);

            if(is_array($value))
            {
                // don't replace lists at this time
                return $matches[0];
            }

            return $formatter->format($value);

        }, $content, -1, $count);

        return $count;
    }

    private function injectListValues(string & $content, string $environment): int
    {
        $formatter = $this->getFormatterForCurrentTargetFile();
        $replacementCounter = 0;

        $eol = $this->detectEol($content);

        while(preg_match(self::VARIABLE_REGEX, $content))
        {
            $lines = explode($eol, $content);
            $result = [];

            foreach($lines as $lineNumber => $line)
            {
                if(preg_match(self::VARIABLE_REGEX, $line, $matches))
                {
                    $values = $this->readValueToInject($matches['variableName'], $environment);

                    if(!is_array($values))
                    {
                        throw new \RuntimeException(sprintf(
                            "Nested variable detected [%s] while writing %s at line %d",
                            $matches['variableName'],
                            $this->currentTargetFile,
                            $lineNumber
                        ));
                    }
                    
                    $replacementCounter++;
                    foreach($values as $value)
                    {
                        $result[] = preg_replace(self::VARIABLE_REGEX, $formatter->format($value), $line, 1);
                    }

                    continue;
                }

                $result[] = $line;
            }

            $content = implode($eol, $result);
        }

        return $replacementCounter;
    }

    private function detectEol(string $content): string
    {
        $types = array("\r\n", "\r", "\n");

        foreach($types as $type)
        {
            if(strpos($content, $type) !== false)
            {
                return $type;
            }
        }

        return "\n";
    }

    private function backupFile(string $targetFile): void
    {
        if($this->enableBackup === true)
        {
            if($this->target->has($targetFile))
            {
                $backupFile = $targetFile . Application::BACKUP_SUFFIX;
                $this->target->write($backupFile, $this->target->read($targetFile), true);
            }
        }
    }

    public function rollback(): void
    {
        $files = $this->collectFiles();

        foreach($files as $file)
        {
            $this->rollbackFile($file);
        }
    }

    private function rollbackFile(string $file): void
    {
        $this->debug("- $file");

        $targetFile = substr($file, 0, strlen($this->suffix) * -1);
        $backupFile = $targetFile . Application::BACKUP_SUFFIX;

        if($this->sources->has($backupFile))
        {
            $this->info("  Writing $targetFile");

            if($this->dryRun === false)
            {
                $backupContent = $this->sources->read($backupFile);
                $this->sources->write($targetFile, $backupContent, true);
            }
        }
    }

    public function getUnusedVariables(): array
    {
        return array_merge(array_flip($this->unusedVariables));
    }

    private function markVariableAsUsed(string $variableName): void
    {
        if(isset($this->unusedVariables[$variableName]))
        {
            unset($this->unusedVariables[$variableName]);
        }
    }

    public function getUnvaluedVariables(): array
    {
        return $this->unvaluedVariables;
    }

    public function hydratedFiles(): array
    {
        return $this->hydratedFiles;
    }
}
