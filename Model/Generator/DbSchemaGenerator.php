<?php
/**
 * Copyright (c) Zengliwei. All rights reserved.
 * Each source file in this distribution is licensed under OSL 3.0, see LICENSE for details.
 */

namespace CrazyCat\ModuleBuilder\Model\Generator;

use CrazyCat\ModuleBuilder\Helper\XmlGenerator;
use Magento\Framework\Exception\LocalizedException;
use SimpleXMLElement;

/**
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
class DbSchemaGenerator extends XmlConfigGenerator
{
    /**
     * @var array
     */
    private $tableAttr = [
        'name'      => 'string',
        'comment'   => 'string',
        'disabled'  => 'boolean',
        'onCreate'  => 'string',
        'resource'  => 'string',
        'engine'    => 'string',
        'charset'   => 'string',
        'collation' => 'string'
    ];

    /**
     * @var array
     */
    private $columnAttr = [
        'name'     => 'string',
        'comment'  => 'string',
        'identity' => 'boolean',
        'unsigned' => 'boolean',
        'nullable' => 'boolean',
        'default'  => 'string',
        'length'   => 'string',
        'padding'  => 'string',
        'onCreate' => 'string',
        'disabled' => 'string'
    ];

    /**
     * Set root node on construct
     */
    public function __construct()
    {
        $this->setRoot('schema', 'urn:magento:framework:Setup/Declaration/Schema/etc/schema.xsd');
    }

    /**
     * Add table
     *
     * Optional attributes:
     * - disabled (bool)
     * - onCreate (string)
     * - charset (string)
     * - collation (string)
     *
     * @param string $name
     * @param string $comment
     * @param string $resource  Multiple database setting, default value 'default'
     * @param string $engine    Default engine 'innodb'
     * @param array  $attributes
     * @return SimpleXMLElement
     * @throws LocalizedException
     */
    public function addTable($name, $comment, $resource = 'default', $engine = 'innodb', array $attributes = [])
    {
        $tableNode = $this->root->addChild('table');
        $this->assignAttributes(
            $tableNode,
            array_merge(
                ['name' => $name, 'comment' => $comment, 'resource' => $resource, 'engine' => $engine],
                $attributes
            ),
            $this->tableAttr
        );
        return $tableNode;
    }

    /**
     * Add column
     *
     * Optional attributes:
     * - identity (bool) Is auto increment or not
     * - unsigned (bool)
     * - nullable (bool) Whether allow NULL value
     * - default (string) Default value
     * - length (int)
     * - disabled (bool)
     * - onCreate (string)
     * - padding
     *
     * @param SimpleXMLElement $tableNode
     * @param string           $name
     * @param string           $type
     * @param string           $comment
     * @param array            $attributes
     * @return SimpleXMLElement
     * @throws LocalizedException
     */
    public function addColumn(SimpleXMLElement $tableNode, $name, $type, $comment, array $attributes)
    {
        $columnNode = $tableNode->addChild('column');
        $columnNode->addAttribute('xmlns:xsi:type', $type);
        $this->assignAttributes(
            $columnNode,
            array_merge(['name' => $name, 'comment' => $comment], $attributes),
            $this->columnAttr
        );
        return $columnNode;
    }

    /**
     * Add primary index
     *
     * @param SimpleXMLElement $tableNode
     * @param string           $name
     * @param array            $columnNames
     */
    public function addPrimaryIndex(SimpleXMLElement $tableNode, $name, array $columnNames)
    {
        $constraintNode = $tableNode->addChild('constraint');
        $this->getXmlGenerator()->assignDataToNode($constraintNode, [
            '@xmlns:xsi:type' => 'primary',
            '@referenceId'    => $name,
            'column'          => array_map(function ($column) {
                return ['@name' => $column];
            }, $columnNames)
        ]);
    }

    /**
     * Add BTree index
     *
     * @param SimpleXMLElement $tableNode
     * @param string           $name
     * @param array            $columnNames
     */
    public function addBtreeIndex(SimpleXMLElement $tableNode, $name, array $columnNames)
    {
        $constraintNode = $tableNode->addChild('index');
        $this->getXmlGenerator()->assignDataToNode($constraintNode, [
            '@indexType'   => 'btree',
            '@referenceId' => $name,
            'column'       => array_map(function ($column) {
                return ['@name' => $column];
            }, $columnNames)
        ]);
    }

    /**
     * Add foreign key
     *
     * @param SimpleXMLElement $tableNode
     * @param string           $name
     * @param string           $table
     * @param string           $column
     * @param string           $referenceTable
     * @param string           $referenceColumn
     */
    public function addForeignKey(
        SimpleXMLElement $tableNode,
        $name,
        $table,
        $column,
        $referenceTable,
        $referenceColumn
    ) {
        $constraintNode = $tableNode->addChild('constraint');
        $this->getXmlGenerator()->assignDataToNode($constraintNode, [
            '@xmlns:xsi:type'  => 'foreign',
            '@onDelete'        => 'CASCADE',
            '@referenceId'     => $name,
            '@table'           => $table,
            '@column'          => $column,
            '@referenceTable'  => $referenceTable,
            '@referenceColumn' => $referenceColumn
        ]);
    }
}
