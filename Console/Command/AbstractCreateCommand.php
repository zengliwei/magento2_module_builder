<?php
/**
 * Copyright (c) Zengliwei. All rights reserved.
 * Each source file in this distribution is licensed under OSL 3.0, see LICENSE for details.
 */

namespace CrazyCat\ModuleBuilder\Console\Command;

use Closure;
use CrazyCat\ModuleBuilder\Model\Cache;
use Exception;
use Laminas\Code\Generator\FileGenerator;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @package CrazyCat\ModuleBuilder
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
abstract class AbstractCreateCommand extends Command
{
    protected const ARG_MODULE_NAME = 'module-name';

    protected Cache $cache;
    protected ComponentRegistrar $componentRegistrar;

    public function __construct(
        Context $context,
        string $name = null
    ) {
        $this->cache = $context->getCache();
        $this->componentRegistrar = $context->getComponentRegistrar();
        parent::__construct($name);
    }

    /**
     * @param InputInterface $input
     * @return array
     * @throws Exception
     */
    protected function getModuleInfo(InputInterface $input)
    {
        $moduleName = $input->getArgument(self::ARG_MODULE_NAME) ?: $this->cache->getDataByKey('module_name');
        if (!($dir = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName))) {
            throw new Exception('Module does not exists.');
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
     * @param string $filename
     * @param Closure $callback
     * @return void
     */
    protected function generateFile($filename, $callback)
    {
        if (is_file($filename)) {
            return;
        }

        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        (new FileGenerator())
            ->setFilename($filename)
            ->setClass($callback())
            ->write();
    }
}
