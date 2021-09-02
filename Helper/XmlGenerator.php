<?php
/*
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

namespace CrazyCat\ModuleBuilder\Helper;

use SimpleXMLElement;

/**
 * SimpleXML of PHP is recommended to use to create or retrieve XML instead of completely management,
 * as there is not any update or remove methods in the related classes.
 *
 * This class is used to create XML by a specified array, of which format is like:
 * [
 *     'node_a' => string|bool|null, // assign value only
 *     'node_b' => [ // assign attribute(s)
 *         '@attribute_a' => string|bool|null,
 *         '@attribute_b' => string|bool|null
 *     ],
 *     'node_c' => [ // value together with attribute(s)
 *         '@attribute_a' => string|bool|null,
 *         string|bool|null
 *     ],
 *     'node_d' => [ // assign child node(s)
 *         '@attribute_a' => string|bool|null,
 *         'node_d_a' => ...,
 *         'node_d_b' => ...
 *     ],
 *     'node_e' => [ // multiple nodes in same level with same name
 *         [string|bool|null],
 *         [
 *             '@attribute_a' => string|bool|null,
 *             string|bool|null
 *         ],
 *         [
 *             '@attribute_a' => string|bool|null,
 *             'node_e_a' => string|bool|null
 *         ]
 *     ]
 * ]
 *
 * @package CrazyCat\ModuleBuilder
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
class XmlGenerator
{
    /**
     * @param array $array
     * @return bool
     */
    public static function isAssocArray(array $array)
    {
        foreach (array_keys($array) as $k) {
            if (!is_numeric($k)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param SimpleXMLElement $node
     * @param array            $data
     * @return void
     */
    public static function assignDataToNode(
        SimpleXMLElement $node,
        array $data
    ) {
        foreach ($data as $key => $value) {
            if (strpos($key, '@') === 0) {
                $node->addAttribute(substr($key, 1), $value);
            } elseif (is_numeric($key)) {
                continue;
            } elseif (is_scalar($value) || $value === null) {
                $node->addChild($key, $value);
            } elseif (is_array($value)) {
                if (self::isAssocArray($value)) {
                    self::assignDataToNode($node->addChild($key, $value[0] ?? null), $value);
                } else {
                    foreach ($value as $info) {
                        self::assignDataToNode($node->addChild($key, $info[0] ?? null), $info);
                    }
                }
            }
        }
    }

    /**
     * @param array  $array
     * @param string $root
     * @return string
     */
    public static function arrayToXml(array $array, $root = 'root')
    {
        $root = new SimpleXMLElement("<$root/>");
        self::assignDataToNode($root, $array);
        return $root->asXML();
    }
}
