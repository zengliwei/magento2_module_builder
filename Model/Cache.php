<?php
/**
 * Copyright (c) Zengliwei. All rights reserved.
 * Each source file in this distribution is licensed under OSL 3.0, see LICENSE for details.
 */

namespace CrazyCat\ModuleBuilder\Model;

use CrazyCat\ModuleBuilder\Model\Cache\Type\ModuleBuilder;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
class Cache extends DataObject
{
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     */
    public function __construct(
        CacheInterface $cache,
        SerializerInterface $serializer
    ) {
        $this->cache = $cache;
        $this->serializer = $serializer;

        parent::__construct(
            ($plain = $this->cache->load(ModuleBuilder::TYPE_IDENTIFIER))
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
            ModuleBuilder::TYPE_IDENTIFIER,
            [ModuleBuilder::CACHE_TAG]
        );
    }
}
