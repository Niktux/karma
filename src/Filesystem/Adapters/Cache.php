<?php

declare(strict_types = 1);

namespace Karma\Filesystem\Adapters;

use Gaufrette\Adapter;
use Gaufrette\Adapter\MetadataSupporter;
use Gaufrette\Adapter\InMemory;

class Cache implements Adapter, MetadataSupporter
{
    protected Adapter
        $source,
        $cache,
        $serializeCache;

    protected int
        $ttl;

    public function __construct(Adapter $source, Adapter $cache, int $ttl = 0, ?Adapter $serializeCache = null)
    {
        $this->source = $source;
        $this->cache = $cache;
        $this->ttl = $ttl;

        if(!$serializeCache)
        {
            $serializeCache = new InMemory();
        }

        $this->serializeCache = $serializeCache;
    }

    /**
     * Returns the time to live of the cache.
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * Defines the time to live of the cache.
     */
    public function setTtl(int $ttl)
    {
        $this->ttl = $ttl;
    }

    public function read($key)
    {
        if ($this->needsReload($key))
        {
            $contents = $this->source->read($key);
            $this->cache->write($key, $contents);
        }
        else
        {
            $contents = $this->cache->read($key);
        }

        return $contents;
    }

    public function rename($key, $new)
    {
        return $this->source->rename($key, $new) && $this->cache->rename($key, $new);
    }

    public function write($key, $content, array $metadata = null)
    {
        $bytesSource = $this->source->write($key, $content);

        if(false === $bytesSource)
        {
            return false;
        }

        $bytesCache = $this->cache->write($key, $content);

        if($bytesSource !== $bytesCache)
        {
            return false;
        }

        return $bytesSource;
    }

    public function exists($key)
    {
        if ($this->needsReload($key))
        {
            return $this->source->exists($key);
        }

        return $this->cache->exists($key);
    }

    public function mtime($key)
    {
        return $this->source->mtime($key);
    }

    public function keys()
    {
        $cacheFile = 'keys.cache';

        if ($this->needsRebuild($cacheFile))
        {
            $keys = $this->source->keys();
            sort($keys);
            $this->serializeCache->write($cacheFile, serialize($keys));
        }
        else
        {
            $keys = unserialize($this->serializeCache->read($cacheFile));
        }

        return $keys;
    }

    public function delete($key)
    {
        return $this->source->delete($key) && $this->cache->delete($key);
    }

    public function isDirectory($key)
    {
        return $this->source->isDirectory($key);
    }

    public function setMetadata($key, $metadata)
    {
        if($this->source instanceof MetadataSupporter)
        {
            $this->source->setMetadata($key, $metadata);
        }

        if($this->cache instanceof MetadataSupporter)
        {
            $this->cache->setMetadata($key, $metadata);
        }
    }

    public function getMetadata($key)
    {
        if($this->source instanceof MetadataSupporter)
        {
            return $this->source->getMetadata($key);
        }

        return false;
    }

    /**
     * Indicates whether the cache for the specified key needs to be reloaded.
     */
    public function needsReload(string $key): bool
    {
        $needsReload = true;

        if($this->cache->exists($key))
        {
            try
            {
                $dateCache = $this->cache->mtime($key);
                $needsReload = false;

                if(time() - $this->ttl >= $dateCache)
                {
                    $dateSource = $this->source->mtime($key);
                    $needsReload = $dateCache < $dateSource;
                }
            }
            catch (\RuntimeException $e)
            {
            }
        }

        return $needsReload;
    }

    /**
     * Indicates whether the serialized cache file needs to be rebuild.
     */
    public function needsRebuild(string $cacheFile): bool
    {
        $needsRebuild = true;

        if($this->serializeCache->exists($cacheFile))
        {
            try
            {
                $needsRebuild = time() - $this->ttl >= $this->serializeCache->mtime($cacheFile);
            }
            catch(\RuntimeException $e)
            {
            }
        }

        return (bool) $needsRebuild;
    }
}
