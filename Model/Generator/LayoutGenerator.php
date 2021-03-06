<?php
/**
 * Copyright (c) Zengliwei. All rights reserved.
 * Each source file in this distribution is licensed under OSL 3.0, see LICENSE for details.
 */

namespace CrazyCat\ModuleBuilder\Model\Generator;

use Magento\Framework\Exception\LocalizedException;
use SimpleXMLElement;

/**
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
class LayoutGenerator extends XmlConfigGenerator
{
    /**
     * @var SimpleXMLElement
     */
    protected $body;

    /**
     * @var array
     */
    protected $blockAttr = [
        'after'       => 'string',
        'before'      => 'string',
        'as'          => 'string',
        'output'      => 'bool',
        'group'       => 'string',
        'ttl'         => 'int',
        'acl'         => 'string',
        'aclResource' => 'string',
        'cacheable'   => 'bool',
        'ifconfig'    => 'string',
        'template'    => 'string'
    ];

    /**
     * @var array
     */
    protected $containerAttr = [
        'after'     => 'string',
        'before'    => 'string',
        'as'        => 'string',
        'output'    => 'bool',
        'htmlClass' => 'string',
        'htmlId'    => 'string',
        'htmlTag'   => 'string',
        'label'     => 'string'
    ];

    /**
     * @var array
     */
    protected $uiComponentAttr = [
        'after'       => 'string',
        'before'      => 'string',
        'as'          => 'string',
        'output'      => 'bool',
        'group'       => 'string',
        'ttl'         => 'int',
        'aclResource' => 'string',
        'cacheable'   => 'bool',
        'ifconfig'    => 'string',
        'component'   => 'string'
    ];

    /**
     * @var array
     */
    protected $referenceBlockAttr = [
        'display'  => 'bool',
        'remove'   => 'bool',
        'class'    => 'string',
        'template' => 'string'
    ];

    /**
     * @var array
     */
    protected $referenceContainerAttr = [
        'display'   => 'bool',
        'remove'    => 'bool',
        'htmlClass' => 'string',
        'htmlId'    => 'string',
        'htmlTag'   => 'string',
        'label'     => 'string'
    ];

    /**
     * @var array
     */
    protected $moveAttr = [
        'after'  => 'string',
        'before' => 'string',
        'as'     => 'string'
    ];

    /**
     * Set root node on construct
     */
    public function __construct()
    {
        $this->setRoot('page', 'urn:magento:framework:View/Layout/etc/page_configuration.xsd');
        $this->body = $this->root->addChild('body');
    }

    /**
     * Set page layout
     *
     * @param string $layout
     */
    public function setPageLayout($layout)
    {
        $this->root->addAttribute('layout', $layout);
    }

    /**
     * Add update
     *
     * @param string $handle
     */
    public function addUpdate($handle)
    {
        $update = $this->root->addChild('update');
        $update->addAttribute('handle', $handle);
    }

    /**
     * Add block
     *
     * @param SimpleXMLElement $parent
     * @param string           $class
     * @param string           $name
     * @param array            $attributes
     * @param array            $arguments  ['argument' => mixed]
     * @param array            $actions    [['method' => string, 'ifconfig' => string, 'arguments' => arguments]]
     * @return SimpleXMLElement
     * @throws LocalizedException
     */
    public function addBlock(
        SimpleXMLElement $parent,
        $class,
        $name,
        $attributes = [],
        $arguments = [],
        $actions = []
    ) {
        $node = $parent->addChild('block');
        $node->addAttribute('class', $class);
        $node->addAttribute('name', $name);
        $this->assignAttributes($node, $attributes, $this->blockAttr);

        if (!empty($arguments)) {
            $this->assignArguments($node, $arguments);
        }

        foreach ($actions as $action) {
            $actionNode = $node->addChild('action');
            $actionNode->addAttribute('method', $action['method']);
            if (isset($action['ifconfig'])) {
                $actionNode->addAttribute('ifconfig', $action['ifconfig']);
            }
            if (isset($action['arguments'])) {
                $this->assignArguments($actionNode, $action['arguments']);
            }
        }

        return $node;
    }

    /**
     * Add container
     *
     * @param SimpleXMLElement $parent
     * @param string           $name
     * @param array            $attributes
     * @return SimpleXMLElement
     * @throws LocalizedException
     */
    public function addContainer(SimpleXMLElement $parent, $name, $attributes = [])
    {
        $node = $parent->addChild('container');
        $node->addAttribute('name', $name);
        $this->assignAttributes($node, $attributes, $this->containerAttr);
        return $node;
    }

    /**
     * Add UI component
     *
     * @param SimpleXMLElement $parent
     * @param string           $name
     * @param array            $attributes
     * @return SimpleXMLElement
     * @throws LocalizedException
     */
    public function addUiComponent(SimpleXMLElement $parent, $name, $attributes = [])
    {
        $node = $parent->addChild('uiComponent');
        $node->addAttribute('name', $name);
        $this->assignAttributes($node, $attributes, $this->uiComponentAttr);
        return $node;
    }

    /**
     * Add body attribute
     *
     * @param string $name
     * @param array  $value
     * @return void
     */
    public function addBodyAttribute($name, $value)
    {
        $node = $this->body->addChild('attribute');
        $node->addAttribute('name', $name);
        $node->addAttribute('value', $value);
    }

    /**
     * Reference block
     *
     * @param string $name
     * @param array  $attributes
     * @return void
     * @throws LocalizedException
     */
    public function referenceBlock($name, $attributes = [])
    {
        $node = $this->body->addChild('referenceBlock');
        $node->addAttribute('name', $name);
        $this->assignAttributes($node, $attributes, $this->referenceBlockAttr);
        return $node;
    }

    /**
     * Reference container
     *
     * @param string $name
     * @param array  $attributes
     * @return void
     * @throws LocalizedException
     */
    public function referenceContainer($name, $attributes = [])
    {
        $node = $this->body->addChild('referenceContainer');
        $node->addAttribute('name', $name);
        $this->assignAttributes($node, $attributes, $this->referenceContainerAttr);
        return $node;
    }

    /**
     * Move element
     *
     * @param string $element
     * @param string $destination
     * @param array  $attributes
     * @return void
     * @throws LocalizedException
     */
    public function move($element, $destination, $attributes = [])
    {
        $node = $this->body->addChild('move');
        $node->addAttribute('element', $element);
        $node->addAttribute('destination', $destination);
        $this->assignAttributes($node, $attributes, $this->moveAttr);
        return $node;
    }
}
