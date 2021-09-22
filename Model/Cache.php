<?php
/**
 * Copyright (c) Zengliwei. All rights reserved.
 * Each source file in this distribution is licensed under OSL 3.0, see LICENSE for details.
 */

namespace CrazyCat\ModuleBuilder\Model;

use CrazyCat\ModuleBuilder\Model\Cache\Type\ModuleBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
class Cache extends DataObject
{
    /**
     * @var ModuleBuilder
     */
    private $cache;

    /**
     * @var ModuleBuilder
     */
    private $cacheKey = 'module_builder_cli';

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param ModuleBuilder       $cache
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ModuleBuilder $cache,
        SerializerInterface $serializer
    ) {
        $this->cache = $cache;
        $this->serializer = $serializer;

        parent::__construct(
            ($plain = $this->cache->load($this->cacheKey))
                ? $this->serializer->unserialize($plain)
                : []
        );
    }

    /**
     * Store cache data on destruct
     */
    public function __destruct()
    {
        $this->cache->save(
            $this->serializer->serialize($this->getData()),
            $this->cacheKey
        );
    }
}
