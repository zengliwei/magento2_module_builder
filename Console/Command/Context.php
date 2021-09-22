<?php
/**
 * Copyright (c) Zengliwei. All rights reserved.
 * Each source file in this distribution is licensed under OSL 3.0, see LICENSE for details.
 */

namespace CrazyCat\ModuleBuilder\Console\Command;

use CrazyCat\ModuleBuilder\Helper\XmlGenerator;
use CrazyCat\ModuleBuilder\Model\Cache;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem;
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
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var DriverInterface
     */
    private $filesystemDriver;

    /**
     * @var XmlGenerator
     */
    private $xmlGenerator;

    /**
     * @param Cache              $cache
     * @param ComponentRegistrar $componentRegistrar
     * @param DriverInterface    $driver
     * @param Filesystem         $filesystem
     * @param XmlGenerator       $xmlGenerator
     */
    public function __construct(
        Cache $cache,
        ComponentRegistrar $componentRegistrar,
        DriverInterface $driver,
        Filesystem $filesystem,
        XmlGenerator $xmlGenerator
    ) {
        $this->cache = $cache;
        $this->componentRegistrar = $componentRegistrar;
        $this->filesystem = $filesystem;
        $this->filesystemDriver = $driver;
        $this->xmlGenerator = $xmlGenerator;
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
     * Get filesystem
     *
     * @return Filesystem
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * Get filesystem driver
     *
     * @return DriverInterface
     */
    public function getFilesystemDriver()
    {
        return $this->filesystemDriver;
    }

    /**
     * Get XML generator
     *
     * @return XmlGenerator
     */
    public function getXmlGenerator()
    {
        return $this->xmlGenerator;
    }
}
