<?php
/**
 * Copyright (c) Zengliwei. All rights reserved.
 * Each source file in this distribution is licensed under OSL 3.0, see LICENSE for details.
 */

namespace CrazyCat\ModuleBuilder\Model\Generator\Php;

/**
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
class PropertyGenerator extends \Laminas\Code\Generator\PropertyGenerator
{
    /**
     * @var string|null
     */
    protected $type = null;

    /**
     * Set type
     *
     * @param string|null $type
     * @return PropertyGenerator
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function generate()
    {
        $output = parent::generate();
        return substr_replace(
            $output,
            $this->type ? $this->type . ' ' : '',
            strpos($output, '$'),
            0
        );
    }
}
