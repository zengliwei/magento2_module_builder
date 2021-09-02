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
use Magento\Framework\Exception\LocalizedException;

/**
 * @package CrazyCat\ModuleBuilder
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
class UiListingGenerator extends XmlConfigGenerator
{
    protected array $columnSettingsAttr = [
        'sortOrder'   => 'int',
        'class'       => 'string',
        'component'   => 'string',
        'template'    => 'string',
        'provider'    => 'string',
        'extends'     => 'string',
        'displayArea' => 'string'
    ];

    public function __construct(
        $namespace,
        $aclResource,
        $actionPath
    ) {
        $this->setRoot('listing', 'urn:magento:module:Magento_Ui:etc/ui_configuration.xsd');

        $dataProviderName = "{$namespace}_data_provider";
        $columnsName = "{$namespace}_columns";
        $provider = "{$namespace}.listing_data_source";
        $sourceProvider = "{$namespace}.{$dataProviderName}";
        $editorProvider = "{$namespace}.{$namespace}.columns_editor";

        $this->assignArguments($this->root, [
            'data' => [
                'js_config' => ['provider' => $provider]
            ]
        ]);

        XmlGenerator::assignDataToNode($this->root, [
            'settings'       => [
                'buttons' => [
                    'button' => [
                        '@name' => 'add',
                        'label' => ['@translate' => 'true', 'Add New Item'],
                        'class' => 'primary',
                        'url'   => ['@path' => '*/*/new']
                    ]
                ],
                'spinner' => $columnsName,
                'deps'    => ['dep' => $sourceProvider]
            ],
            'dataSource'     => [
                '@name'        => 'listing_data_source',
                '@component'   => 'Magento_Ui/js/grid/provider',
                'settings'     => [
                    'storageConfig' => [
                        'param' => [
                            '@name'           => 'indexField',
                            '@xmlns:xsi:type' => 'string',
                            'id'
                        ]
                    ],
                    'updateUrl'     => [
                        '@path' => 'mui/index/render'
                    ]
                ],
                'aclResource'  => $aclResource,
                'dataProvider' => [
                    '@class'   => 'Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider',
                    '@name'    => $dataProviderName,
                    'settings' => [
                        'primaryFieldName' => 'id',
                        'requestFieldName' => 'id'
                    ]
                ]
            ],
            'listingToolbar' => [
                '@name'           => 'listing_top',
                'settings'        => [
                    'sticky' => 'true'
                ],
                'bookmark'        => ['@name' => 'bookmark'],
                'columnsControls' => ['@name' => 'columns_controls'],
                'filterSearch'    => ['@name' => 'fulltext'],
                'paging'          => ['@name' => 'listing_paging'],
                'filters'         => [
                    '@name'    => 'listing_filters',
                    'settings' => [
                        'templates' => [
                            'filters' => [
                                'select' => [
                                    'param' => [
                                        [
                                            '@name'           => 'template',
                                            '@xmlns:xsi:type' => 'string',
                                            'ui/grid/filters/elements/ui-select'
                                        ],
                                        [
                                            '@name'           => 'component',
                                            '@xmlns:xsi:type' => 'string',
                                            'uMagento_Ui/js/form/element/ui-select'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'massaction'      => [
                    '@name'  => 'listing_actions',
                    'action' => [
                        '@name'    => 'edit',
                        'settings' => [
                            'type'     => 'edit',
                            'label'    => ['@translate' => 'true', 'Edit'],
                            'callback' => [
                                'target'   => 'editSelected',
                                'provider' => $editorProvider
                            ]
                        ]
                    ]
                ]
            ],
            'columns'        => [
                '@name'            => $columnsName,
                'settings'         => [],
                'selectionsColumn' => [
                    '@name'    => 'ids',
                    'settings' => ['indexField' => 'id']
                ],
                'column'           => [
                    '@name'    => 'id',
                    'settings' => [
                        'filter'  => 'textRange',
                        'label'   => ['@translate' => 'true', 'ID'],
                        'sorting' => 'asc'
                    ]
                ],
                'actionsColumn'    => [
                    '@class'     => 'CrazyCat\Base\Ui\Component\Listing\Column\Actions',
                    '@name'      => 'actions',
                    '@sortOrder' => 999,
                    'settings'   => [
                        'fieldAction' => [
                            'params' => [
                                'param' => [
                                    '@name'           => 'route',
                                    '@xmlns:xsi:type' => 'string',
                                    $actionPath
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    }

    /**
     * @param string $name
     * @param array  $settings
     * @param array  $attributes
     * @throws LocalizedException
     */
    public function addColumn(
        $name,
        array $settings,
        array $attributes = []
    ) {
        $columnsNode = $this->root->xpath('/listing/columns')[0];
        $columnNode = $columnsNode->addChild('column');
        XmlGenerator::assignDataToNode($columnNode, ['@name' => $name]);
        $this->assignAttributes($columnNode, $attributes, $this->columnSettingsAttr);
        XmlGenerator::assignDataToNode($columnNode->addChild('settings'), $settings);
    }
}
