<?php

declare(strict_types = 1);

namespace Karma\Filesystem\Adapters;

use Gaufrette\Adapter;

final class MultipleAdapter implements Adapter
{
    private array
        $mountTable;
    
    public function __construct()
    {
        $this->mountTable = [];
    }
    
    public function mount(string $mountPoint, Adapter $adapter): void
    {
        $directorySeparator = '/';
        $mountPoint = rtrim($mountPoint, $directorySeparator) . $directorySeparator;
        
        $this->mountTable[$mountPoint] = $adapter;
    }
    
    private function find($key): array
    {
        $mountPoints = array_keys($this->mountTable);
        
        foreach($mountPoints as $mountPoint)
        {
            if(strpos($key, $mountPoint) === 0)
            {
                $relativeKey = substr($key, strlen($mountPoint));
                
                return array($this->mountTable[$mountPoint], $relativeKey);
            }
        }

        throw new \RuntimeException("Key $key not found");
    }
    
    public function read($key)
    {
        [$adapter, $relativeKey] = $this->find($key);
        
        return $adapter->read($relativeKey);
    }
    
    public function write($key, $content)
    {
        [$adapter, $relativeKey] = $this->find($key);
        
        return $adapter->write($relativeKey, $content);
    }
    
    public function exists($key): bool
    {
        try
        {
            [$adapter, $relativeKey] = $this->find($key);
        }
        catch(\RuntimeException $e)
        {
            return false;
        }
        
        return $adapter->exists($relativeKey);
    }
    
    public function keys(): array
    {
        $keys = [];

        foreach($this->mountTable as $mountPoint => $adapter)
        {
            $adapterKeys = $adapter->keys();
            
            array_walk($adapterKeys, static function(& $key) use($mountPoint) {
                $key = $mountPoint . $key;
            });
                        
            $keys = array_merge($keys, $adapterKeys);
        }
        
        return $keys;
    }
    
    public function mtime($key)
    {
        [$adapter, $relativeKey] = $this->find($key);
        
        return $adapter->mtime($relativeKey);
    }
    
    public function delete($key): bool
    {
        [$adapter, $relativeKey] = $this->find($key);
        
        return $adapter->delete($relativeKey);
    }
    
    public function rename($sourceKey, $targetKey)
    {
        throw new \RuntimeException('Not implemented yet : ' . __METHOD__);
    }
    
    public function isDirectory($key): bool
    {
        [$adapter, $relativeKey] = $this->find($key);
        
        return $adapter->isDirectory($relativeKey);
    }
}
