<?php
/**
 * Copyright (c) Zengliwei. All rights reserved.
 * Each source file in this distribution is licensed under OSL 3.0, see LICENSE for details.
 */

namespace CrazyCat\ModuleBuilder\Console\Command;

use Closure;
use CrazyCat\ModuleBuilder\Model\Cache;
use Laminas\Code\Generator\FileGenerator;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\DriverInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
abstract class AbstractCreateCommand extends Command
{
    protected const ARG_MODULE_NAME = 'module-name';

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var ComponentRegistrar
     */
    protected $componentRegistrar;

    /**
     * @var DriverInterface
     */
    protected $filesystemDriver;

    /**
     * @param Context     $context
     * @param string|null $name
     */
    public function __construct(
        Context $context,
        string $name = null
    ) {
        $this->cache = $context->getCache();
        $this->componentRegistrar = $context->getComponentRegistrar();
        $this->filesystemDriver = $context->getFilesystemDriver();
        parent::__construct($name);
    }

    /**
     * Get module information
     *
     * @param InputInterface $input
     * @return array
     * @throws LocalizedException
     */
    protected function getModuleInfo(InputInterface $input)
    {
        $moduleName = $input->getArgument(self::ARG_MODULE_NAME) ?: $this->cache->getDataByKey('module_name');
        if (!($dir = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName))) {
            throw new LocalizedException('Module does not exists.');
        }

        if ($moduleName != $this->cache->getData('module_name')) {
            [$vendor, $module] = explode('_', $moduleName);
            $this->cache->setData(
                [
                    'module_name' => $moduleName,
                    'vendor'      => $vendor,
                    'module'      => $module
                ]
            );
        } else {
            $vendor = $this->cache->getDataByKey('vendor');
            $module = $this->cache->getDataByKey('module');
        }

        return [$moduleName, $vendor, $module, $dir];
    }

    /**
     * Generate file
     *
     * @param string  $filename
     * @param Closure $callback
     * @return void
     * @throws FileSystemException
     */
    protected function generateFile($filename, $callback)
    {
        if ($this->filesystemDriver->isFile($filename)) {
            return;
        }

        $dir = $this->filesystemDriver->getParentDirectory($filename);
        if (!$this->filesystemDriver->isDirectory($dir)) {
            $this->filesystemDriver->createDirectory($dir, 0755);
        }

        (new FileGenerator())
            ->setFilename($filename)
            ->setClass($callback())
            ->write();
    }
}
