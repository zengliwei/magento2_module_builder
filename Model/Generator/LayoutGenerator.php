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

use Magento\Framework\Exception\LocalizedException;
use SimpleXMLElement;

/**
 * @package CrazyCat\ModuleBuilder
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
class LayoutGenerator extends XmlConfigGenerator
{
    protected SimpleXMLElement $body;

    protected array $blockAttr = [
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

    protected array $containerAttr = [
        'after'     => 'string',
        'before'    => 'string',
        'as'        => 'string',
        'output'    => 'bool',
        'htmlClass' => 'string',
        'htmlId'    => 'string',
        'htmlTag'   => 'string',
        'label'     => 'string'
    ];

    protected array $uiComponentAttr = [
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

    protected array $referenceBlockAttr = [
        'display'  => 'bool',
        'remove'   => 'bool',
        'class'    => 'string',
        'template' => 'string'
    ];

    protected array $referenceContainerAttr = [
        'display'   => 'bool',
        'remove'    => 'bool',
        'htmlClass' => 'string',
        'htmlId'    => 'string',
        'htmlTag'   => 'string',
        'label'     => 'string'
    ];

    protected array $moveAttr = [
        'after'  => 'string',
        'before' => 'string',
        'as'     => 'string'
    ];

    public function __construct()
    {
        $this->setRoot('page', 'urn:magento:framework:View/Layout/etc/page_configuration.xsd');
        $this->body = $this->root->addChild('body');
    }

    /**
     * @param string $layout
     */
    public function setPageLayout($layout)
    {
        $this->root->addAttribute('layout', $layout);
    }

    /**
     * @param string $handle
     */
    public function addUpdate($handle)
    {
        $update = $this->root->addChild('update');
        $update->addAttribute('handle', $handle);
    }

    /**
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
