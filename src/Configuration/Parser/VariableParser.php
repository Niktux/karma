<?php

declare(strict_types = 1);

namespace Karma\Configuration\Parser;

use Karma\Configuration;

class VariableParser extends AbstractSectionParser
{
    use Configuration\FilterInputVariable;

    private const
        ASSIGNMENT = '=',
        ENV_SEPARATOR = ',';
    private ?string
        $currentVariable;
    private array
        $variables;
    private bool
        $valueFound;

    public function __construct()
    {
        parent::__construct();

        $this->currentVariable = null;
        $this->variables = [];
        $this->valueFound = false;
    }

    protected function parseLine(string $line): void
    {
        [$variableName, $isSystem] = $this->extractVariableName($line);

        if($variableName !== null)
        {
            $this->changeCurrentVariable($variableName, $isSystem);

            return;
        }

        $this->parseEnvironmentValue($line);
    }

    private function extractVariableName(string $line): array
    {
        $variableName = null;
        $isSystem = false;
        $flag = Configuration::SYSTEM_VARIABLE_FLAG;

        if(preg_match("~^\s*(?P<systemVariableFlag>$flag)?(?P<variableName>[^:=]+):$~", $line, $matches))
        {
            $variableName = trim($matches['variableName']);
            $isSystem = $matches['systemVariableFlag'] === $flag;

            if(preg_match('~\s~', $variableName))
            {
                throw new \RuntimeException(sprintf(
                    'Blank characters are not allowed in variable name : "%s" (in file %s line %d)',
                    $variableName,
                    $this->currentFilePath,
                    $this->currentLineNumber
                ));
            }
        }

        return [$variableName, $isSystem];
    }

    private function checkCurrentVariableState(): void
    {
        if($this->currentVariable !== null && $this->valueFound === false)
        {
            $this->triggerError(sprintf(
                'Variable %s has no value (declared in file %s line %d)',
                $this->currentVariable,
                $this->variables[$this->currentVariable]['file'],
                $this->variables[$this->currentVariable]['line']
            ));
        }
    }

    private function changeCurrentVariable(string $variableName, bool $isSystem): void
    {
        $this->checkCurrentVariableState();
        $this->currentVariable = $variableName;

        if(isset($this->variables[$this->currentVariable]))
        {
            $this->triggerError(sprintf(
                'Variable %s is already declared (in %s line %d)',
                $this->currentVariable,
                $this->variables[$this->currentVariable]['file'],
                $this->variables[$this->currentVariable]['line']
            ));
        }

        $this->variables[$this->currentVariable] = [
            'env' => [],
            'file' => $this->currentFilePath,
            'line' => $this->currentLineNumber,
            'system' => $isSystem,
        ];

        $this->valueFound = false;
    }

    private function parseEnvironmentValue(string $line): void
    {
        if($this->currentVariable === null)
        {
            $this->triggerError('Missing variable name');
        }

        if(substr_count($line, self::ASSIGNMENT) < 1)
        {
            $this->triggerError("line must contains = ($line)");
        }

        list($envList, $value) = explode(self::ASSIGNMENT, $line, 2);
        $environments = array_map('trim', explode(self::ENV_SEPARATOR, $envList));

        $value = $this->parseList($value);
        $value = $this->filterValue($value);

        foreach($environments as $environment)
        {
            if(array_key_exists($environment, $this->variables[$this->currentVariable]['env']))
            {
                $this->triggerError("Duplicated value for environment $environment and variable $this->currentVariable");
            }

            $this->variables[$this->currentVariable]['env'][$environment] = $value;
        }

        $this->valueFound = true;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function setCurrentFile(string $filePath): void
    {
        parent::setCurrentFile($filePath);

        $this->currentVariable = null;
    }

    public function endOfFileCheck(): void
    {
        $this->checkCurrentVariableState();
    }
}
