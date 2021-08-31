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

namespace CrazyCat\ModuleBuilder\Model\Generator;

use SimpleXMLElement;

/**
 * @package CrazyCat\ModuleBuilder
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
class UiFormGenerator extends AbstractXmlConfig
{
    public function __construct(
        $namespace,
        $dataProviderClass,
        $submitUrl
    ) {
        $this->root = new SimpleXMLElement('<?xml version="1.0"?><form/>');
        $this->root->addAttribute(
            'xsi:noNamespaceSchemaLocation',
            'urn:magento:module:Magento_Ui:etc/ui_configuration.xsd',
            'http://www.w3.org/2001/XMLSchema-instance',
        );

        $dataSourceName = 'form_data_source';
        $dataProviderName = 'form_data_provider';
        $provider = "{$namespace}.{$dataSourceName}";
        $sourceProvider = "{$namespace}.{$dataProviderName}";

        $this->assignArguments($this->root, [
            [
                'name'  => 'data',
                'value' => [
                    [
                        'name'  => 'js_config',
                        'value' => [
                            ['name' => 'provider', 'value' => $provider]
                        ]
                    ],
                    ['name' => 'label', 'value' => 'General Information'],
                    ['name' => 'template', 'value' => 'templates/form/collapsible']
                ]
            ]
        ]);

        $settingsNode = $this->root->addChild('settings');
        $settingsNode->addChild('namespace', $namespace);
        $settingsNode->addChild('dataScope', 'data');

        $depsNode = $settingsNode->addChild('deps');
        $depsNode->addChild('dep', $sourceProvider);

        $this->initDataSource($dataSourceName, $dataProviderClass, $dataProviderName, $submitUrl);
    }

    /**
     * @param string $dataSourceName
     * @param string $dataProviderClass
     * @param string $dataProviderName
     * @param string $submitUrl
     * @return void
     */
    protected function initDataSource($dataSourceName, $dataProviderClass, $dataProviderName, $submitUrl)
    {
        $dataSourceNode = $this->root->addChild('dataSource');
        $dataSourceNode->addAttribute('name', $dataSourceName);

        $this->assignArguments($dataSourceNode, [
            [
                'name'  => 'data',
                'value' => [
                    [
                        'name'  => 'js_config',
                        'value' => [
                            ['name' => 'component', 'value' => 'Magento_Ui/js/form/provider']
                        ]
                    ],
                ]
            ]
        ]);

        $settingsNode = $dataSourceNode->addChild('settings');
        $submitUrlNode = $settingsNode->addChild('submitUrl');
        $submitUrlNode->addAttribute('path', $submitUrl);

        $dataProviderNode = $dataSourceNode->addChild('dataProvider');
        $dataProviderNode->addAttribute('class', $dataProviderClass);
        $dataProviderNode->addAttribute('name', $dataProviderName);

        $dataProviderSettingsNode = $dataProviderNode->addChild('settings');
        $dataProviderSettingsNode->addChild('requestFieldName', 'id');
        $dataProviderSettingsNode->addChild('primaryFieldName', 'id');
    }

    public function addButton($name, $label, $class, $url = null, $aclResource = null, $params = [])
    {
        $settingsNode = $this->root->xpath('/form/settings')[0];

        $buttonsNodes = $settingsNode->xpath('buttons');
        $buttonsNode = empty($buttonsNodes)
            ? $settingsNode->addChild('buttons')
            : $buttonsNodes[0];

        $buttonNode = $buttonsNode->addChild('button');
        $buttonNode->addAttribute('name', $name);
        $buttonNode->addChild('label', $label);
        $buttonNode->addChild('class', $class);
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
     * @param string      $name
     * @param string|null $label
     * @return SimpleXMLElement
     */
    public function addFieldset($name, $label = null)
    {
        $fieldsetNode = $this->root->addChild('fieldset');
        $fieldsetNode->addAttribute('name', $name);

        $settingsNode = $fieldsetNode->addChild('settings');
        $settingsNode->addChild('label', $label);

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
    public function addField($fieldsetNode, $name, $formElement, $settings = [], $arguments = [])
    {
        $fieldNode = $fieldsetNode->addChild('field');
        $fieldNode->addAttribute('name', $name);
        $fieldNode->addAttribute('formElement', $formElement);

        $this->assignArguments($fieldNode, $arguments);

        $settingsNode = $fieldNode->addChild('settings');
        foreach ($settings as $nodeName => $value) {
            $settingNode = $settingsNode->addChild($nodeName, is_array($value) ? null : $value);
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $settingNode->addChild($k, $v);
                }
            }
        }
    }
}
