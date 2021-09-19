<?php
/**
 * Copyright (c) Zengliwei. All rights reserved.
 * Each source file in this distribution is licensed under OSL 3.0, see LICENSE for details.
 */

namespace CrazyCat\ModuleBuilder\Model\Generator;

use CrazyCat\ModuleBuilder\Helper\XmlGenerator;
use Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Phrase;
use SimpleXMLElement;

/**
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
class XmlConfigGenerator
{
    /**
     * @var SimpleXMLElement
     */
    protected $root;

    /**
     * Returns parent directory's path
     *
     * @param string $path
     * @return string
     */
    protected function dirName($path)
    {
        return ObjectManager::getInstance()->get(DriverInterface::class)->getParentDirectory($path);
    }

    /**
     * Tells whether the filename is a regular directory
     *
     * @param string $path
     * @return bool
     * @throws FileSystemException
     */
    protected function isDir($path)
    {
        return ObjectManager::getInstance()->get(DriverInterface::class)->isDirectory($path);
    }

    /**
     * Tells whether the filename is a regular file
     *
     * @param string $path
     * @return bool
     * @throws FileSystemException
     */
    protected function isFile($path)
    {
        return ObjectManager::getInstance()->get(DriverInterface::class)->isFile($path);
    }

    /**
     * Create directory
     *
     * @param string $path
     * @param int    $permissions
     * @return bool
     * @throws FileSystemException
     */
    protected function mkdir($path, $permissions = 0755)
    {
        return ObjectManager::getInstance()->get(DriverInterface::class)->createDirectory($path, $permissions);
    }

    /**
     * Get XML generator
     *
     * @return XmlGenerator
     */
    protected function getXmlGenerator()
    {
        return ObjectManager::getInstance()->get(XmlGenerator::class);
    }

    /**
     * Transform source array to argument array
     *
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
                if ($this->getXmlGenerator()->isAssocArray($arguments[$nodeName])) {
                    $arguments[$nodeName] = [$arguments[$nodeName]];
                }
                $arguments[$nodeName][] = $argument;
            }
        }
        return $arguments;
    }

    /**
     * Assign arguments
     *
     * @param SimpleXMLElement $node
     * @param array            $arguments
     * @param string           $nodeName
     * @return void
     */
    public function assignArguments(
        SimpleXMLElement $node,
        array $arguments,
        $nodeName = 'argument'
    ) {
        $this->getXmlGenerator()->assignDataToNode($node, $this->toArgumentArray($arguments, $nodeName));
    }

    /**
     * Assign attributes
     *
     * @param SimpleXMLElement $node
     * @param array            $attributes
     * @param array            $allowedAttributes
     * @throws LocalizedException
     */
    public function assignAttributes(
        SimpleXMLElement $node,
        array $attributes,
        array $allowedAttributes
    ) {
        foreach ($attributes as $attribute => $value) {
            if ($value === null) {
                continue;
            }
            if (!isset($allowedAttributes[$attribute])
                || gettype($value) != $allowedAttributes[$attribute]
            ) {
                throw new LocalizedException(
                    __('Attribute %1 dose not match type %2.', $attribute, $allowedAttributes[$attribute])
                );
            }
            $node->addAttribute($attribute, is_bool($value) ? ($value ? 'true' : 'false') : $value);
        }
    }

    /**
     * Get root node
     *
     * @return SimpleXMLElement
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * Set root
     *
     * @param string $rootName
     * @param string $schemaLocation
     * @return SimpleXMLElement
     * @throws Exception
     */
    public function setRoot($rootName, $schemaLocation)
    {
        $this->root = new SimpleXMLElement('<?xml version="1.0"?><' . $rootName . '/>');
        $this->root->addAttribute(
            'xsi:noNamespaceSchemaLocation',
            $schemaLocation,
            'http://www.w3.org/2001/XMLSchema-instance',
        );
        return $this->root;
    }

    /**
     * Generate XML string
     *
     * @return bool|string
     */
    public function generate()
    {
        return $this->root->asXML();
    }

    /**
     * Generate XML file
     *
     * @param string $filename
     * @param bool   $override
     * @return void
     * @throws FileSystemException
     */
    public function write($filename, $override = false)
    {
        if ($this->isFile($filename) && !$override) {
            return;
        }
        $dir = $this->dirName($filename);
        if (!$this->isDir($dir)) {
            $this->mkdir($dir, 0755);
        }
        $this->root->saveXML($filename);
    }
}
