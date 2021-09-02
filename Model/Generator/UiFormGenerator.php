<?php
/**
 * Copyright (c) 2021 Zengliwei. All rights reserved.
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
use SimpleXMLElement;

/**
 * @package CrazyCat\ModuleBuilder
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
class UiFormGenerator extends XmlConfigGenerator
{
    public function __construct(
        $namespace,
        $dataProviderClass,
        $submitUrl
    ) {
        $this->setRoot('form', 'urn:magento:module:Magento_Ui:etc/ui_configuration.xsd');

        $dataProviderName = "{$namespace}_data_provider";
        $provider = "{$namespace}.form_data_source";
        $sourceProvider = "{$namespace}.{$dataProviderName}";

        $this->assignArguments($this->root, [
            'data' => [
                'js_config' => ['provider' => $provider],
                'label'     => __('General Information'),
                'template'  => 'templates/form/collapsible'
            ]
        ]);

        XmlGenerator::assignDataToNode($this->root, [
            'settings'   => [
                'dataScope' => 'data',
                'namespace' => $namespace,
                'deps'      => ['dep' => $sourceProvider],
                'buttons'   => null
            ],
            'dataSource' => [
                '@name'        => 'form_data_source',
                'argument'     => [
                    '@name'           => 'data',
                    '@xmlns:xsi:type' => 'array',
                    'item'            => [
                        '@name'           => 'js_config',
                        '@xmlns:xsi:type' => 'array',
                        'item'            => [
                            '@name'           => 'component',
                            '@xmlns:xsi:type' => 'string',
                            'Magento_Ui/js/form/provider'
                        ]
                    ]
                ],
                'settings'     => [
                    'submitUrl' => ['@path' => $submitUrl]
                ],
                'dataProvider' => [
                    '@class'   => $dataProviderClass,
                    '@name'    => $dataProviderName,
                    'settings' => [
                        'requestFieldName' => 'id',
                        'primaryFieldName' => 'id'
                    ]
                ]
            ]
        ]);
    }

    /**
     * @param string      $name
     * @param string      $label
     * @param string      $class
     * @param string|null $url
     * @param string|null $aclResource
     * @param array       $params
     */
    public function addButton(
        $name,
        $label,
        $class,
        $url = null,
        $aclResource = null,
        $params = []
    ) {
        $buttonsNode = $this->root->xpath('/form/settings/buttons')[0];
        $buttonNode = $buttonsNode->addChild('button');
        XmlGenerator::assignDataToNode($buttonNode, [
            '@name'  => $name,
            '@class' => $class,
            'label'  => ['@translate' => 'true', $label]
        ]);
        if ($url !== null) {
            $urlNode = $buttonNode->addChild('url');
            $urlNode->addAttribute('path', $url);
        }
        if ($aclResource !== null) {
            $buttonNode->addChild('aclResource', $aclResource);
        }
        $this->assignArguments($buttonNode, $params, 'param');
    }

    /**
     * @param string $name
     * @param string $label
     * @param bool   $collapsible
     * @return SimpleXMLElement
     */
    public function addFieldset($name, $label, $collapsible = false)
    {
        $fieldsetNode = $this->root->addChild('fieldset');
        XmlGenerator::assignDataToNode($fieldsetNode, [
            '@name'    => $name,
            'settings' => [
                'label'       => ['@translate' => true, $label],
                'collapsible' => $collapsible ? 'true' : 'false'
            ]
        ]);
        return $fieldsetNode;
    }

    /**
     * @param SimpleXMLElement $fieldsetNode
     * @param string           $name
     * @param string           $formElement
     * @param array            $settings
     * @param array            $arguments
     * @return SimpleXMLElement
     */
    public function addField(
        SimpleXMLElement $fieldsetNode,
        $name,
        $formElement,
        array $settings = [],
        array $arguments = []
    ) {
        $fieldNode = $fieldsetNode->addChild('field');
        $this->assignArguments($fieldNode, $arguments);
        XmlGenerator::assignDataToNode($fieldNode, [
            '@name'        => $name,
            '@formElement' => $formElement,
            'settings'     => $settings
        ]);
    }
}
