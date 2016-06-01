<?php

namespace Karma;

use Gaufrette\Filesystem;
use Psr\Log\NullLogger;
use Karma\FormatterProviders\NullProvider;

class Hydrator implements ConfigurableProcessor
{
    use \Karma\Logging\LoggerAware;

    const
        TODO_VALUE = '__TODO__',
        FIXME_VALUE = '__FIXME__',
        VARIABLE_REGEX = '~<%(?P<variableName>[A-Za-z0-9_\.\-]+)%>~';

    private
        $sources,
        $suffix,
        $reader,
        $dryRun,
        $enableBackup,
        $finder,
        $formatterProvider,
        $currentFormatterName,
        $currentTargetFile,
        $systemEnvironment,
        $unusedVariables,
        $unvaluedVariables,
        $target,
        $nonDistFilesOverwriteAllowed;

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

        $this->formatterProvider = $formatterProvider;
        if($this->formatterProvider === null)
        {
            $this->formatterProvider = new NullProvider();
        }

        $this->currentFormatterName = null;
        $this->currentTargetFile = null;
        $this->systemEnvironment = null;
        $this->unusedVariables = array_flip($reader->getAllVariables());
        $this->unvaluedVariables = array();
        $this->nonDistFilesOverwriteAllowed = false;
    }

    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;

        return $this;
    }

    public function setDryRun($value = true)
    {
        $this->dryRun = (bool) $value;

        return $this;
    }

    public function enableBackup($value = true)
    {
        $this->enableBackup = (bool) $value;

        return $this;
    }
    
    public function allowNonDistFilesOverwrite($nonDistFilesOverwriteAllowed = true)
    {
        $this->nonDistFilesOverwriteAllowed = $nonDistFilesOverwriteAllowed;

        return $this;
    }

    public function setFormatterProvider(FormatterProvider $formatterProvider)
    {
        $this->formatterProvider = $formatterProvider;

        return $this;
    }

    public function setSystemEnvironment($environment)
    {
        $this->systemEnvironment = $environment;

        return $this;
    }

    public function hydrate($environment)
    {
        $distFiles = $this->collectDistFiles();

        foreach($distFiles as $file)
        {
            $this->hydrateFile($file, $environment);
        }

        $this->info(sprintf(
           '%d files generated',
            count($distFiles)
        ));
    }

    private function collectDistFiles()
    {
        $pattern = '.*\-dist$';
        if($this->nonDistFilesOverwriteAllowed === true)
        {
            $pattern = '.*';
        }
        
        return $this->finder->findFiles(sprintf('~%s~', $pattern));
    }

    private function hydrateFile($file, $environment)
    {
        $this->currentTargetFile = preg_replace(sprintf('~(.*)(\%s)$~', $this->suffix), '$1', $file);

        if($this->nonDistFilesOverwriteAllowed)
        {
            $this->currentTargetFile = (new \SplFileInfo($this->currentTargetFile))->getFilename();
        }

        $content = $this->sources->read($file);
        $replacementCounter = $this->parseFileDirectives($file, $content, $environment);

        $targetContent = $this->injectValues($file, $content, $environment, $replacementCounter);

        $this->debug("Write $this->currentTargetFile");

        if($this->dryRun === false)
        {
            $this->backupFile($this->currentTargetFile);

            $this->target->write($this->currentTargetFile, $targetContent, true);
        }
    }

    private function parseFileDirectives($file, & $fileContent, $environment)
    {
        $this->currentFormatterName = null;

        $this->parseFormatterDirective($file, $fileContent);
        $replacementCounter = $this->parseListDirective($file, $fileContent, $environment);

        $fileContent = $this->removeFileDirectives($fileContent);

        return $replacementCounter;
    }

    private function parseFormatterDirective($file, $fileContent)
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

    private function parseListDirective($file, & $fileContent, $environment)
    {
        $replacementCounter = 0;

        $regexDelimiter = '(delimiter="(?P<delimiterName>[^"]*)")?';
        $regexWrapper = '(wrapper="(?P<wrapperPrefix>[^"]*)":"(?P<wrapperSuffix>[^"]*)")?';
        $regex = '~<%\s*karma:list\s*var=(?P<variableName>[\S]+)\s*' . $regexDelimiter . '\s*' . $regexWrapper . '\s*%>~i';

        while(preg_match($regex, $fileContent, $matches))
        {
            $delimiter = '';
            if(isset($matches['delimiterName']))
            {
                $delimiter = $matches['delimiterName'];
            }

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

    private function lookingForSyntaxErrorInListDirective($file, $fileContent)
    {
        if(preg_match('~<%.*karma\s*:\s*list\s*~i', $fileContent))
        {
            // karma:list detected but has not matches full regexp
            throw new \RuntimeException("Invalid karma:list directive in file $file");
        }
    }

    private function generateContentForListDirective($variable, $environment, $delimiter, array $wrapper)
    {
        $values = $this->readValueToInject($variable, $environment);
        $formatter = $this->getFormatterForCurrentTargetFile();

        if(! is_array($values))
        {
            $values = array($values);
        }

        array_walk($values, function (& $value) use ($formatter) {
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

    private function injectValues($sourceFile, $content, $environment, $replacementCounter = 0)
    {
        $replacementCounter += $this->injectScalarValues($content, $environment);
        $replacementCounter += $this->injectListValues($content, $environment);

        if($replacementCounter === 0)
        {
            $this->warning("No variable found in $sourceFile");
        }

        return $content;
    }

    private function readValueToInject($variableName, $environment)
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

    private function checkValueIsAllowed($variableName, $environment, $value)
    {
        if($value === self::FIXME_VALUE)
        {
            throw new \RuntimeException(sprintf(
                'Missing value for variable %s in environment %s (FIXME marker found)',
                $variableName,
                $environment
            ));
        }
        elseif($value === self::TODO_VALUE)
        {
            $this->unvaluedVariables[] = $variableName;
        }
    }

    private function getFormatterForCurrentTargetFile()
    {
        $fileExtension = pathinfo($this->currentTargetFile, PATHINFO_EXTENSION);

        return $this->formatterProvider->getFormatter($fileExtension, $this->currentFormatterName);
    }

    private function injectScalarValues(& $content, $environment)
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

    private function injectListValues(& $content, $environment)
    {
        $formatter = $this->getFormatterForCurrentTargetFile();
        $replacementCounter = 0;

        $eol = $this->detectEol($content);

        while(preg_match(self::VARIABLE_REGEX, $content))
        {
            $lines = explode($eol, $content);
            $result = array();

            foreach($lines as $line)
            {
                if(preg_match(self::VARIABLE_REGEX, $line, $matches))
                {
                    $values = $this->readValueToInject($matches['variableName'], $environment);

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

    private function detectEol($content)
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

    private function backupFile($targetFile)
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

    public function rollback()
    {
        $distFiles = $this->collectDistFiles();

        foreach($distFiles as $file)
        {
            $this->rollbackFile($file);
        }
    }

    private function rollbackFile($file)
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

    public function getUnusedVariables()
    {
        return array_merge(array_flip($this->unusedVariables));
    }

    private function markVariableAsUsed($variableName)
    {
        if(isset($this->unusedVariables[$variableName]))
        {
            unset($this->unusedVariables[$variableName]);
        }
    }

    public function getUnvaluedVariables()
    {
        return $this->unvaluedVariables;
    }
}
