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

use CrazyCat\ModuleBuilder\Helper\XmlGenerator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use SimpleXMLElement;

/**
 * @package CrazyCat\ModuleBuilder
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
abstract class AbstractXmlConfig extends XmlGenerator
{
    protected SimpleXMLElement $root;

    /**
     * @param SimpleXMLElement $node
     * @param array            $attributes
     * @param array            $allowedAttributes
     * @throws LocalizedException
     */
    protected function assignAttributes(
        SimpleXMLElement $node,
        array $attributes,
        array $allowedAttributes
    ) {
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
     * @param string           $nodeName
     * @return void
     */
    protected function assignArguments(
        SimpleXMLElement $node,
        array $arguments,
        $nodeName = 'argument'
    ) {
        $this->assignDataToNode($node, $this->toArgumentArray($arguments, $nodeName));
    }

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
                if ($this->isAssocArray($arguments[$nodeName])) {
                    $arguments[$nodeName] = [$arguments[$nodeName]];
                }
                $arguments[$nodeName][] = $argument;
            }
        }
        return $arguments;
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
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $this->root->saveXML($filename);
    }
}
