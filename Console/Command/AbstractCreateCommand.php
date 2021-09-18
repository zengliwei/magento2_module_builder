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
