<?php
/**
 * Copyright (c) 2020 Zengliwei
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFINGEMENT. IN NO EVENT SHALL THE AUTHORS
 * OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace CrazyCat\ModuleBuilder\Model\Generator;

use Magento\Framework\Exception\LocalizedException;
use SimpleXMLElement;

/**
 * @package CrazyCat\ModuleBuilder
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
abstract class AbstractXmlConfig
{
    protected SimpleXMLElement $root;

    /**
     * @param SimpleXMLElement $node
     * @param array            $attributes
     * @param array            $allowedAttributes
     * @throws LocalizedException
     */
    protected function assignAttributes($node, $attributes, $allowedAttributes)
    {
        foreach ($attributes as $attribute => $value) {
            if (!isset($allowedAttributes[$attribute])
                || gettype($value) != $allowedAttributes[$attribute]
            ) {
                throw new LocalizedException(
                    __('Attribute %1 dose not match type %2.', $attribute, $allowedAttributes[$attribute])
                );
            }
            $node->addAttribute($attribute, $value);
        }
    }

    /**
     * @param SimpleXMLElement $node
     * @param array            $arguments
     */
    protected function assignArguments($node, $arguments, $nodeName = 'argument', $isInner = false)
    {
        foreach ($arguments as $argument) {
            $argumentNode = $node->addChild(
                $isInner ? 'item' : $nodeName,
                (isset($argument['value']) && !is_array($argument['value'])) ? $argument['value'] : null
            );
            $argumentNode->addAttribute('name', $argument['name']);
            $argumentNode->addAttribute('xmlns:xsi:type', gettype($argument['value']));
            if (is_array($argument['value'])) {
                $this->assignArguments($argumentNode, $argument['value'], $nodeName, true);
            }
        }
    }

    /**
     * @param array $source
     * @return array
     */
    public function transformArray(array $source)
    {
        $dist = [];
        foreach ($source as $name => $value) {
            if (is_array($value)) {
                $value = $this->transformArray($value);
            }
            $dist[] = ['name' => $name, 'value' => $value];
        }
        return $dist;
    }

    /**
     * @return bool|string
     */
    public function generate()
    {
        return $this->root->asXML();
    }

    /**
     * @param string $filename
     * @return void
     */
    public function write($filename)
    {
        $this->root->saveXML($filename);
    }
}
