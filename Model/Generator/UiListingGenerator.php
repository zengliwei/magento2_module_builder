<?php
/**
 * Copyright (c) Zengliwei. All rights reserved.
 * Each source file in this distribution is licensed under OSL 3.0, see LICENSE for details.
 */

namespace CrazyCat\ModuleBuilder\Model\Generator;

use CrazyCat\Base\Ui\Component\Listing\Column\Actions;
use CrazyCat\ModuleBuilder\Helper\XmlGenerator;
use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;

/**
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
class UiListingGenerator extends XmlConfigGenerator
{
    /**
     * @var array
     */
    protected $columnSettingsAttr = [
        'sortOrder'   => 'int',
        'class'       => 'string',
        'component'   => 'string',
        'template'    => 'string',
        'provider'    => 'string',
        'extends'     => 'string',
        'displayArea' => 'string'
    ];

    /**
     * @param string $namespace
     * @param string $aclResource
     * @param string $actionPath
     * @throws Exception
     */
    public function __construct(
        $namespace,
        $aclResource,
        $actionPath
    ) {
        $this->setRoot('listing', 'urn:magento:module:Magento_Ui:etc/ui_configuration.xsd');

        $dataProviderName = "{$namespace}_data_provider";
        $provider = "{$namespace}.listing_data_source";
        $sourceProvider = "{$namespace}.{$dataProviderName}";
        $editorProvider = "{$namespace}.{$namespace}.listing_columns_editor";

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
                'spinner' => 'listing_columns',
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
                    '@class'   => DataProvider::class,
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
                '@name'            => 'listing_columns',
                'settings'         => [
                    'childDefaults' => [
                        'param' => [
                            '@name'           => 'fieldAction',
                            '@xmlns:xsi:type' => 'array',
                            'item'            => [
                                [
                                    '@name'           => 'provider',
                                    '@xmlns:xsi:type' => 'string',
                                    $editorProvider
                                ],
                                [
                                    '@name'           => 'target',
                                    '@xmlns:xsi:type' => 'string',
                                    'startEdit'
                                ],
                                [
                                    '@name'           => 'params',
                                    '@xmlns:xsi:type' => 'array',
                                    'item'            => [
                                        [
                                            '@name'           => '0',
                                            '@xmlns:xsi:type' => 'string',
                                            '${ $.$data.rowIndex }'
                                        ],
                                        [
                                            '@name'           => '1',
                                            '@xmlns:xsi:type' => 'boolean',
                                            'true'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'editorConfig'  => [
                        'param' => [
                            [
                                '@name'           => 'clientConfig',
                                '@xmlns:xsi:type' => 'array',
                                'item'            => [
                                    [
                                        '@name'           => 'saveUrl',
                                        '@xmlns:xsi:type' => 'url',
                                        '@path'           => "{$actionPath}/massSave"
                                    ],
                                    [
                                        '@name'           => 'validateBeforeSave',
                                        '@xmlns:xsi:type' => 'boolean',
                                        'false'
                                    ]
                                ]
                            ],
                            [
                                '@name'           => 'indexField',
                                '@xmlns:xsi:type' => 'string',
                                'id'
                            ],
                            [
                                '@name'           => 'enabled',
                                '@xmlns:xsi:type' => 'boolean',
                                'true'
                            ],
                            [
                                '@name'           => 'selectProvider',
                                '@xmlns:xsi:type' => 'string',
                                "{$namespace}.{$namespace}.listing_columns.ids"
                            ]
                        ]
                    ]
                ],
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
                    '@class'     => Actions::class,
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
     * Add column
     *
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
