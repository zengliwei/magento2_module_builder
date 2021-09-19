<?php
/**
 * Copyright (c) Zengliwei. All rights reserved.
 * Each source file in this distribution is licensed under OSL 3.0, see LICENSE for details.
 */

namespace CrazyCat\ModuleBuilder\Console\Command;

use CrazyCat\ModuleBuilder\Model\Cache;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem\DriverInterface;

/**
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
class Context
{
    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var ComponentRegistrar
     */
    private $componentRegistrar;

    /**
     * @var DriverInterface
     */
    private $filesystemDriver;

    /**
     * @param Cache              $cache
     * @param ComponentRegistrar $componentRegistrar
     * @param DriverInterface    $driver
     */
    public function __construct(
        Cache $cache,
        ComponentRegistrar $componentRegistrar,
        DriverInterface $driver
    ) {
        $this->cache = $cache;
        $this->componentRegistrar = $componentRegistrar;
        $this->filesystemDriver = $driver;
    }

    /**
     * Get cache model
     *
     * @return Cache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Get component registrar
     *
     * @return ComponentRegistrar
     */
    public function getComponentRegistrar()
    {
        return $this->componentRegistrar;
    }

    /**
     * Get filesystem driver
     *
     * @return ComponentRegistrar
     */
    public function getFilesystemDriver()
    {
        return $this->filesystemDriver;
    }
}
