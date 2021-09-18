<?php
/**
 * Copyright (c) Zengliwei. All rights reserved.
 * Each source file in this distribution is licensed under OSL 3.0, see LICENSE for details.
 */

namespace CrazyCat\ModuleBuilder\Console\Command;

use CrazyCat\ModuleBuilder\Model\Generator\DbSchemaGenerator;
use Exception;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlock\Tag\GenericTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package CrazyCat\ModuleBuilder
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
class CreateModel extends AbstractCreateCommand
{
    private const ARG_MAIN_TABLE = 'main-table';
    private const ARG_MODEL_PATH = 'model-path';

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
                    sprintf(
                        'Module name, use the cached one (%s) if not set',
                        $this->cache->getDataByKey('module_name')
                    )
                )
            ]
        );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            [, $vendor, $module, $root] = $this->getModuleInfo($input);
        } catch (Exception $e) {
            return $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
        }

        $modelPath = $input->getArgument(self::ARG_MODEL_PATH);
        if (!preg_match('/[A-Z][a-z]+(\\[A-Z][a-z]+)*/', $modelPath)) {
            return $output->writeln('<error>Invalid model path.</error>');
        }
        $path = str_replace('\\', '/', $modelPath);
        $key = strtolower($module . '_' . str_replace('\\', '_', $modelPath));

        $modelClass = $vendor . '\\' . $module . '\\Model\\' . $modelPath;
        $resourceClass = $vendor . '\\' . $module . '\\Model\\ResourceModel\\' . $modelPath;
        $collectionClass = $vendor . '\\' . $module . '\\Model\\ResourceModel\\' . $modelPath . '\\Collection';

        $tableName = $input->getArgument(self::ARG_MAIN_TABLE);
        $tableComment = str_replace('\\', ' ', $modelPath) . ' Table';
        $indexPrefix = strtoupper($key);

        $this->createDatabaseTable($tableName, $tableComment, $indexPrefix);

        $this->createResourceModel(
            $root . '/Model/ResourceModel/' . $path . '.php',
            $resourceClass,
            $tableName
        );

        $this->createModel(
            $root . '/Model/' . $path . '.php',
            $modelClass,
            $resourceClass
        );

        $this->createCollection(
            $root . '/Model/ResourceModel/' . $path . '/Collection.php',
            $collectionClass,
            $modelClass,
            $resourceClass,
            $key
        );

        $output->writeln('<info>Model created.</info>');
    }

    /**
     * @param string $tableName
     * @param string $tableComment
     * @param string $indexPrefix
     * @throws LocalizedException
     */
    private function createDatabaseTable($tableName, $tableComment, $indexPrefix)
    {
        $dbSchemaGenerator = new DbSchemaGenerator();
        $tableNode = $dbSchemaGenerator->addTable($tableName, $tableComment);
        $dbSchemaGenerator->addColumn($tableNode, 'id', 'int', 'ID', [
            'identity' => true,
            'unsigned' => true,
            'nullable' => false
        ]);
        $dbSchemaGenerator->addPrimaryIndex($tableNode, $indexPrefix . '_ID', ['id']);
        $dbSchemaGenerator->write($this->cache->getDataByKey('dir') . '/etc/db_schema.xml');
    }

    /**
     * @param string $filename
     * @param string $class
     * @param string $mainTable
     * @return ClassGenerator
     */
    private function createResourceModel($filename, $class, $mainTable)
    {
        $this->generateFile($filename, function () use ($class, $mainTable) {
            return (new ClassGenerator($class))
                ->setExtendedClass('Magento\Framework\Model\ResourceModel\Db\AbstractDb')
                ->addUse('Magento\Framework\Model\ResourceModel\Db\AbstractDb')
                ->addMethod(
                    '_construct',
                    [],
                    MethodGenerator::FLAG_PROTECTED,
                    '$this->_init(\'' . $mainTable . '\', \'id\');',
                    (new DocBlockGenerator())->setTag((new GenericTag('inheritDoc')))
                );
        });
    }

    /**
     * @param string $filename
     * @param string $class
     * @param string $resourceClass
     * @return ClassGenerator
     */
    private function createModel($filename, $class, $resourceClass)
    {
        $this->generateFile($filename, function () use ($class, $resourceClass) {
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
        });
    }

    /**
     * @param string $filename
     * @param string $class
     * @param string $modelClass
     * @param string $resourceClass
     * @param string $key
     * @return ClassGenerator
     */
    private function createCollection($filename, $class, $modelClass, $resourceClass, $key)
    {
        $this->generateFile($filename, function () use ($class, $modelClass, $resourceClass, $key) {
            return (new ClassGenerator($class))
                ->setExtendedClass('Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection')
                ->addUse('Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection')
                ->addUse($modelClass, 'Model')
                ->addUse($resourceClass, 'ResourceModel')
                ->addProperty('_idFieldName', 'id', PropertyGenerator::FLAG_PROTECTED)
                ->addProperty('_eventPrefix', $key . '_collection', PropertyGenerator::FLAG_PROTECTED)
                ->addProperty('_eventObject', 'collection', PropertyGenerator::FLAG_PROTECTED)
                ->addMethod(
                    '_construct',
                    [],
                    MethodGenerator::FLAG_PROTECTED,
                    '$this->_init(Model::class, ResourceModel::class);',
                    (new DocBlockGenerator())->setTag((new GenericTag('inheritDoc')))
                );
        });
    }
}
