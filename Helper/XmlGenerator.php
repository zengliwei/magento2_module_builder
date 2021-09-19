<?php
/**
 * Copyright (c) Zengliwei. All rights reserved.
 * Each source file in this distribution is licensed under OSL 3.0, see LICENSE for details.
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
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
class XmlGenerator
{
    /**
     * Is assoc array
     *
     * @param array $array
     * @return bool
     */
    public function isAssocArray(array $array)
    {
        foreach (array_keys($array) as $k) {
            if (!is_numeric($k)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Assign data to node
     *
     * @param SimpleXMLElement $node
     * @param array            $data
     * @return void
     */
    public function assignDataToNode(
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
                if ($this->isAssocArray($value)) {
                    $this->assignDataToNode($node->addChild($key, $value[0] ?? null), $value);
                } else {
                    foreach ($value as $info) {
                        $this->assignDataToNode($node->addChild($key, $info[0] ?? null), $info);
                    }
                }
            }
        }
    }

    /**
     * Transform array to XML
     *
     * @param array  $array
     * @param string $root
     * @return string
     */
    public function arrayToXml(array $array, $root = 'root')
    {
        $root = new SimpleXMLElement("<$root/>");
        $this->assignDataToNode($root, $array);
        return $root->asXML();
    }
}
