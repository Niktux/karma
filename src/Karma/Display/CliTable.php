<?php

namespace Karma\Display;

class CliTable
{
    private
        $headers,
        $rows,
        $nbColumns,
        $columnsSize,
        $valueRenderFunction,
        $enableFormattingTags,
        $displayKeys;

    public function __construct(array $values)
    {
        $this->rows = $values;

        $this->headers = array();
        $this->nbColumns = 0;

        $this->valueRenderFunction = null;
        $this->enableFormattingTags = false;
        $this->displayKeys = false;
    }

    public function setValueRenderingFunction(\Closure $function)
    {
        $this->valueRenderFunction = $function;

        return $this;
    }

    public function enableFormattingTags($value = true)
    {
        $this->enableFormattingTags = (bool) $value;

        return $this;
    }

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    public function displayKeys($value = true)
    {
        $this->displayKeys = (bool) $value;

        return $this;
    }

    public function render()
    {
        $this->manageKeys();
        $this->computeColumnsSize();

        $nbSeparators = $this->nbColumns + 1;
        $paddingSize = 2 * $this->nbColumns;
        $totalSize = array_sum($this->columnsSize) + $nbSeparators + $paddingSize;

        $separatorRow = str_pad('|', $totalSize - 1, '-') . '|';

        $lines = array();
        $lines[] = $separatorRow;

        if(! empty($this->headers))
        {
            $lines[] = $this->renderLine($this->headers);
            $lines[] = $separatorRow;
        }

        foreach($this->rows as $row)
        {
            $lines[] = $this->renderLine($row);
        }

        $lines[] = $separatorRow;

        return implode(PHP_EOL, $lines);
    }

    private function manageKeys()
    {
        if($this->displayKeys !== true)
        {
            return;
        }

        if(! empty($this->headers))
        {
            array_unshift($this->headers, '');
        }

        foreach($this->rows as $key => $row)
        {
            array_unshift($row, $key);
            $this->rows[$key] = $row;
        }
    }

    private function computeColumnsSize()
    {
        $this->computeNbColumns();

        $this->columnsSize = array_pad(array(), $this->nbColumns, -1);

        if(! empty($this->headers))
        {
            $this->updateColumnsSize(array($this->headers));
        }

        $this->updateColumnsSize($this->rows);
    }

    private function computeNbColumns()
    {
        $this->nbColumns = 0;

        foreach($this->rows as $row)
        {
            $this->nbColumns = max($this->nbColumns, count($row));
        }

        $this->nbColumns = max($this->nbColumns, count($this->headers));
    }

    private function updateColumnsSize(array $newValues)
    {
        foreach($newValues as $row)
        {
            if(! is_array($row))
            {
                throw new \InvalidArgumentException('Rows must be arrays');
            }

            if(count($row) !== $this->nbColumns)
            {
                throw new \InvalidArgumentException('Rows must all have the same number of columns');
            }

            for($i = 0; $i < $this->nbColumns; $i++)
            {
                $value = $this->renderValueAsString($row[$i]);

                if($this->enableFormattingTags === true)
                {
                    $value = $this->stripTags($value);
                }

                $this->columnsSize[$i] = max(strlen($value), $this->columnsSize[$i]);
            }
        }
    }

    private function renderValueAsString($value)
    {
        if($value === false)
        {
            $value = 'false';
        }
        elseif($value === true)
        {
            $value = 'true';
        }
        elseif($value === null)
        {
            $value = 'NULL';
        }

        if($this->valueRenderFunction instanceof \Closure)
        {
            $f = $this->valueRenderFunction;
            $value = $f($value);
        }

        return (string) $value;
    }

    private function stripTags($value)
    {
        return preg_replace ('/<[^>]*>/', '', $value);
    }

    private function renderLine(array $row)
    {
        $columns = array();

        for($i = 0; $i < $this->nbColumns; $i++)
        {
            $value = $this->renderValueAsString($row[$i]);

            $displayedString = str_pad(
                $value,
                $this->computeRealColumnSizeFor($value, $this->columnsSize[$i]),
                ' '
            );

            $columns[] = sprintf('| %s ', $displayedString);
        }

        return implode('', $columns) . '|';
    }

    private function computeRealColumnSizeFor($value, $columnSize)
    {
        $displayedValue = $value;

        if($this->enableFormattingTags === true)
        {
            $displayedValue = $this->stripTags($displayedValue);
        }

        $paddingLength = $columnSize - strlen($displayedValue);

        return strlen($value) + $paddingLength;
    }
}
