<?php
/**
 * Copyright (c) Zengliwei. All rights reserved.
 * Each source file in this distribution is licensed under OSL 3.0, see LICENSE for details.
 */

namespace CrazyCat\ModuleBuilder\Model\Generator\Php;

use Laminas\Code\Generator\Exception\InvalidArgumentException;

/**
 * @package CrazyCat\ModuleBuilder
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
class ClassGenerator extends \Laminas\Code\Generator\ClassGenerator
{
    /**
     * @inheritDoc
     */
    public function addProperty($name, $defaultValue = null, $flags = PropertyGenerator::FLAG_PUBLIC, $type = null)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException(
                sprintf('%s::%s expects string for name', get_class($this), __FUNCTION__)
            );
        }

        // backwards compatibility
        // @todo remove this on next major version
        if ($flags === PropertyGenerator::FLAG_CONSTANT) {
            return $this->addConstant($name, $defaultValue);
        }

        return $this->addPropertyFromGenerator((new PropertyGenerator($name, $defaultValue, $flags))->setType($type));
    }
}
