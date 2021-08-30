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

namespace CrazyCat\ModuleBuilder\Console\Command;

use CrazyCat\ModuleBuilder\Model\Cache;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlock\Tag\GenericTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\FileGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Magento\Framework\Component\ComponentRegistrar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package CrazyCat\ModuleBuilder
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
class CreateModel extends Command
{
    private const ARG_MODULE_NAME = 'module-name';
    private const ARG_MODEL_PATH = 'model-path';
    private const ARG_MAIN_TABLE = 'main-table';
    private const OPT_ID_FIELD = 'id-field';

    private Cache $cache;
    private ComponentRegistrar $componentRegistrar;

    public function __construct(
        Cache $cache,
        ComponentRegistrar $componentRegistrar,
        string $name = null
    ) {
        $this->cache = $cache;
        $this->componentRegistrar = $componentRegistrar;
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('module-builder:create-model');
        $this->setDescription('Create a new model');
        $this->setDefinition(
            [
                new InputArgument(
                    self::ARG_MODEL_PATH,
                    InputArgument::REQUIRED,
                    'Model path related to the Model folder, use backslash as separator'
                ),
                new InputArgument(
                    self::ARG_MAIN_TABLE,
                    InputArgument::REQUIRED,
                    'Main table of the model'
                ),
                new InputArgument(
                    self::ARG_MODULE_NAME,
                    InputArgument::OPTIONAL,
                    'Module name, format is like Vendor_Module, case sensitive',
                    $this->cache->getData('module_name')
                ),
                new InputOption(
                    self::OPT_ID_FIELD,
                    'i',
                    InputOption::VALUE_REQUIRED,
                    'ID field name',
                    'id'
                )
            ]
        );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $moduleName = $input->getArgument(self::ARG_MODULE_NAME);
        if (!($dir = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName))) {
            return $output->writeln('<error>Module does not exists.</error>');
        }

        $modelPath = $input->getArgument(self::ARG_MODEL_PATH);
        if (!preg_match('/[A-Z][a-z]+(\\[A-Z][a-z]+)*/', $modelPath)) {
            return $output->writeln('<error>Invalid model path.</error>');
        }

        if ($moduleName != $this->cache->getData('module_name')) {
            [$vendor, $module] = explode('_', $moduleName);
            $this->cache->setData(
                [
                    'module_name' => $moduleName,
                    'vendor'      => $vendor,
                    'module'      => $module,
                    'dir'         => $dir
                ]
            );
        } else {
            $vendor = $this->cache->getDataByKey('vendor');
            $module = $this->cache->getDataByKey('module');
        }

        $path = str_replace('\\', '/', $modelPath);

        $modelClass = $vendor . '\\' . $module . '\\Model\\' . $modelPath;
        $resourceClass = $vendor . '\\' . $module . '\\Model\\ResourceModel\\' . $modelPath;
        $collectionClass = $vendor . '\\' . $module . '\\Model\\ResourceModel\\' . $modelPath . '\\Collection';

        $this->generateFile(
            $dir . '/Model/ResourceModel/' . $path . '.php',
            $this->createResourceModel(
                $resourceClass,
                $input->getArgument(self::ARG_MAIN_TABLE),
                $input->getOption(self::OPT_ID_FIELD)
            )
        );

        $this->generateFile(
            $dir . '/Model/' . $path . '.php',
            $this->createModel($modelClass, $resourceClass)
        );

        $this->generateFile(
            $dir . '/Model/ResourceModel/' . $path . '/Collection.php',
            $this->createCollection($collectionClass, $modelClass, $resourceClass)
        );

        $output->writeln('<info>Model created.</info>');
    }

    /**
     * @param string $class
     * @param string $mainTable
     * @param string $idFieldName
     * @return ClassGenerator
     */
    private function createResourceModel($class, $mainTable, $idFieldName)
    {
        return (new ClassGenerator($class))
            ->setExtendedClass('Magento\Framework\Model\ResourceModel\Db\AbstractDb')
            ->addUse('Magento\Framework\Model\ResourceModel\Db\AbstractDb')
            ->addMethod(
                '_construct',
                [],
                MethodGenerator::FLAG_PROTECTED,
                '$this->_init(\'' . $mainTable . '\', \'' . $idFieldName . '\');',
                (new DocBlockGenerator())->setTag((new GenericTag('inheritDoc')))
            );
    }

    /**
     * @param string $class
     * @param string $resourceClass
     * @return ClassGenerator
     */
    private function createModel($class, $resourceClass)
    {
        return (new ClassGenerator($class))
            ->setExtendedClass('Magento\Framework\Model\AbstractModel')
            ->addUse('Magento\Framework\Model\AbstractModel')
            ->addUse($resourceClass, 'ResourceModel')
            ->addMethod(
                '_construct',
                [],
                MethodGenerator::FLAG_PROTECTED,
                '$this->_init(ResourceModel::class);',
                (new DocBlockGenerator())->setTag((new GenericTag('inheritDoc')))
            );
    }

    /**
     * @param string $class
     * @param string $modelClass
     * @param string $resourceClass
     * @return ClassGenerator
     */
    private function createCollection($class, $modelClass, $resourceClass)
    {
        return (new ClassGenerator($class))
            ->setExtendedClass('Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection')
            ->addUse('Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection')
            ->addUse($modelClass, 'Model')
            ->addUse($resourceClass, 'ResourceModel')
            ->addMethod(
                '_construct',
                [],
                MethodGenerator::FLAG_PROTECTED,
                '$this->_init(Model::class, ResourceModel::class);',
                (new DocBlockGenerator())->setTag((new GenericTag('inheritDoc')))
            );
    }

    /**
     * @param string $filename
     * @param ClassGenerator $classGenerator
     * @return void
     */
    private function generateFile($filename, $classGenerator)
    {
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        (new FileGenerator())
            ->setFilename($filename)
            ->setClass($classGenerator)
            ->write();
    }
}
