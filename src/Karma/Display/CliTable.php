<?php

namespace Karma\Display;

class CliTable
{
    private
        $values,
        $nbColumns,
        $columnsSize,
        $header,
        $rows,
        $valueRenderFunction;
    
    public function __construct(array $values)
    {
        $this->values = $values;
        
        $this->header = array_shift($values);
        $this->nbColumns = count($this->header);
        
        $this->rows = $values;
        $this->valueRenderFunction = null;
    }
    
    public function setValueRenderingFunction(\Closure $function)
    {
        $this->valueRenderFunction = $function;
        
        return $this;
    }
    
    public function render()
    {
        $this->computeColumnsSize();
        
        $nbSeparators = $this->nbColumns + 1;
        $paddingSize = 2 * $this->nbColumns;
        $totalSize = array_sum($this->columnsSize) + $nbSeparators + $paddingSize;

        $separatorRow = '|' . str_pad('', $totalSize - 2, '-') . '|';
        
        $lines = array();
        $lines[] = $separatorRow;
        $lines[] = $this->renderLine($this->header);
        $lines[] = $separatorRow;
        
        foreach($this->rows as $row)
        {
            $lines[] = $this->renderLine($row);
        }
        
        $lines[] = $separatorRow;
        
        return implode(PHP_EOL, $lines);
    }
    
    private function computeColumnsSize()
    {
        $this->columnsSize = array_pad(array(), $this->nbColumns, -1);
        
        $this->updateColumnsSize(array($this->header));
        $this->updateColumnsSize($this->rows);
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
                $length = strlen($value);
                
                $this->columnsSize[$i] = max($length, $this->columnsSize[$i]);
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
    
    private function renderLine(array $row)
    {
        $columns = array();
        
        for($i = 0; $i < $this->nbColumns; $i++)
        {
            $value = $this->renderValueAsString($row[$i]);
            $columns[] = '| ' . str_pad($value, $this->columnsSize[$i], ' ') . ' ';
        }
        
        return implode('', $columns) . '|';
    }
}