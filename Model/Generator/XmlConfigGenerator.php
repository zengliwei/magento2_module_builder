<?php
/**
 * Copyright (c) Zengliwei. All rights reserved.
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

use CrazyCat\ModuleBuilder\Helper\XmlGenerator;
use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use SimpleXMLElement;

/**
 * @package CrazyCat\ModuleBuilder
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
class XmlConfigGenerator
{
    protected SimpleXMLElement $root;

    /**
     * @param array  $source
     * @param string $nodeName
     * @return array
     */
    protected function toArgumentArray(array $source, $nodeName = 'argument')
    {
        $arguments = [];
        foreach ($source as $key => $value) {
            $argument = [
                '@name'           => $key,
                '@xmlns:xsi:type' => ($value instanceof Phrase) ? 'string' : gettype($value)
            ];

            if (is_array($value)) {
                $argument = array_merge($argument, $this->toArgumentArray($value, 'item'));
            } elseif ($value instanceof Phrase) {
                $argument['@translate'] = 'true';
                $argument[0] = $value;
            } elseif (is_scalar($value)) {
                $argument[0] = $value;
            }

            if (!isset($arguments[$nodeName])) {
                $arguments[$nodeName] = $argument;
            } else { // multiple nodes with same name in same level
                if (XmlGenerator::isAssocArray($arguments[$nodeName])) {
                    $arguments[$nodeName] = [$arguments[$nodeName]];
                }
                $arguments[$nodeName][] = $argument;
            }
        }
        return $arguments;
    }

    /**
     * @param SimpleXMLElement $node
     * @param array            $arguments
     * @param string           $nodeName
     * @return void
     */
    public function assignArguments(
        SimpleXMLElement $node,
        array $arguments,
        $nodeName = 'argument'
    ) {
        XmlGenerator::assignDataToNode($node, $this->toArgumentArray($arguments, $nodeName));
    }

    /**
     * @param SimpleXMLElement $node
     * @param array            $attributes
     * @param array            $allowedAttributes
     * @throws LocalizedException
     */
    public function assignAttributes(
        SimpleXMLElement $node,
        array $attributes,
        array $allowedAttributes
    ) {
        foreach ($attributes as $attribute => $value) {
            if ($value === null) {
                continue;
            }
            if (!isset($allowedAttributes[$attribute])
                || gettype($value) != $allowedAttributes[$attribute]
            ) {
                throw new LocalizedException(
                    __('Attribute %1 dose not match type %2.', $attribute, $allowedAttributes[$attribute])
                );
            }
            $node->addAttribute($attribute, is_bool($value) ? ($value ? 'true' : 'false') : $value);
        }
    }

    /**
     * @return SimpleXMLElement
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @return SimpleXMLElement
     * @throws Exception
     */
    public function setRoot($rootName, $schemaLocation)
    {
        $this->root = new SimpleXMLElement('<?xml version="1.0"?><' . $rootName . '/>');
        $this->root->addAttribute(
            'xsi:noNamespaceSchemaLocation',
            $schemaLocation,
            'http://www.w3.org/2001/XMLSchema-instance',
        );
        return $this->root;
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
     * @param bool   $override
     * @return void
     */
    public function write($filename, $override = false)
    {
        if (is_file($filename) && !$override) {
            return;
        }
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $this->root->saveXML($filename);
    }
}
