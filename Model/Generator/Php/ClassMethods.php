<?php
/**
 * Copyright (c) Zengliwei. All rights reserved.
 * Each source file in this distribution is licensed under OSL 3.0, see LICENSE for details.
 */

namespace CrazyCat\ModuleBuilder\Model\Generator\Php;

use Laminas\Code\Generator\Exception\InvalidArgumentException;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Generator\PropertyValueGenerator;

trait ClassMethods
{
    /**
     * Add Constant
     *
     * @param string                      $name   Non-empty string
     * @param string|int|null|float|array $value  Scalar
     * @return self
     * @throws InvalidArgumentException
     */
    public function addConstant($name, $value)
    {
        if (empty($name) || !is_string($name)) {
            throw new InvalidArgumentException(
                sprintf('%s expects string for name', __METHOD__)
            );
        }

        return $this->addConstantFromGenerator(
            new PropertyGenerator(
                $name,
                new PropertyValueGenerator($value),
                PropertyGenerator::FLAG_PUBLIC | PropertyGenerator::FLAG_CONSTANT
            )
        );
    }

    /**
     * Add Property from scalars
     *
     * @param string       $name
     * @param string|array $defaultValue
     * @param int          $flags
     * @param string|null  $type
     * @return self
     * @throws InvalidArgumentException
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
