<?php
/**
 * Copyright (c) Zengliwei. All rights reserved.
 * Each source file in this distribution is licensed under OSL 3.0, see LICENSE for details.
 */

namespace CrazyCat\ModuleBuilder\Console\Command;

use CrazyCat\ModuleBuilder\Model\Cache;
use Magento\Framework\Component\ComponentRegistrar;

/**
 * @package CrazyCat\ModuleBuilder
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
class Context
{
    private Cache $cache;
    private ComponentRegistrar $componentRegistrar;

    public function __construct(
        Cache $cache,
        ComponentRegistrar $componentRegistrar
    ) {
        $this->cache = $cache;
        $this->componentRegistrar = $componentRegistrar;
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function getComponentRegistrar()
    {
        return $this->componentRegistrar;
    }
}
