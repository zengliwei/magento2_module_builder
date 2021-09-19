<?php
/**
 * Copyright (c) Zengliwei. All rights reserved.
 * Each source file in this distribution is licensed under OSL 3.0, see LICENSE for details.
 */

namespace CrazyCat\ModuleBuilder\Console\Command;

use CrazyCat\ModuleBuilder\Helper\XmlGenerator;
use CrazyCat\ModuleBuilder\Model\Generator\LayoutGenerator;
use CrazyCat\ModuleBuilder\Model\Generator\Php\ClassGenerator;
use CrazyCat\ModuleBuilder\Model\Generator\UiFormGenerator;
use CrazyCat\ModuleBuilder\Model\Generator\UiListingGenerator;
use CrazyCat\ModuleBuilder\Model\Generator\XmlConfigGenerator;
use Exception;
use Laminas\Code\Generator\DocBlock\Tag\GenericTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Magento\Framework\App\Area;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\Route\Config\Reader;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
class CreateAdminhtmlUi extends AbstractCreateCommand
{
    private const ARG_CONTROLLER_PATH = 'controller-path';
    private const ARG_MODEL_PATH = 'model-path';
    private const OPT_ROUTE_NAME = 'route-name';

    /**
     * @var AreaList
     */
    private $areaList;

    /**
     * @var Reader
     */
    private $routeConfigReader;

    /**
     * @param AreaList    $areaList
     * @param Reader      $routeConfigReader
     * @param Context     $context
     * @param string|null $name
     */
    public function __construct(
        AreaList $areaList,
        Reader $routeConfigReader,
        Context $context,
        string $name = null
    ) {
        $this->areaList = $areaList;
        $this->routeConfigReader = $routeConfigReader;
        parent::__construct($context, $name);
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('module-builder:create-adminhtml-ui');
        $this->setDescription('Create list and edit pages of admin panel');
        $this->setDefinition(
            [
                new InputArgument(
                    self::ARG_CONTROLLER_PATH,
                    InputArgument::REQUIRED,
                    'Controller path related to the Controller folder, use backslash as separator'
                ),
                new InputArgument(
                    self::ARG_MODEL_PATH,
                    InputArgument::REQUIRED,
                    'Model path related to the Model folder, use backslash as separator'
                ),
                new InputArgument(
                    self::ARG_MODULE_NAME,
                    InputArgument::OPTIONAL,
                    sprintf(
                        'Module name, use the cached one (%s) if not set',
                        $this->cache->getDataByKey('module_name')
                    )
                ),
                new InputOption(
                    self::OPT_ROUTE_NAME,
                    'r',
                    InputOption::VALUE_REQUIRED,
                    'Route name'
                )
            ]
        );
    }

    /**
     * Get route
     *
     * @param InputInterface $input
     * @throws Exception
     */
    protected function getRoute(InputInterface $input)
    {
        $moduleName = $this->cache->getDataByKey('module_name');
        $routers = $this->routeConfigReader->read(Area::AREA_ADMINHTML);
        $routes = $routers[$this->areaList->getDefaultRouter(Area::AREA_ADMINHTML)]['routes'] ?? null;
        $route = null;
        foreach ($routes as $info) {
            if (in_array($moduleName, $info['modules'])) {
                $route = $info['id'];
                break;
            }
        }
        if ($route === null
            && ($route = $input->getOption(self::OPT_ROUTE_NAME)) === false
        ) {
            throw new Exception('Route name of the module is not specified.');
        }

        $configGenerator = new XmlConfigGenerator();
        $rootNode = $configGenerator->setRoot('config', 'urn:magento:framework:App/etc/routes.xsd');
        XmlGenerator::assignDataToNode($rootNode, [
            'router' => [
                '@id'   => 'admin',
                'route' => [
                    '@id'        => $route,
                    '@frontName' => $route,
                    'module'     => ['@name' => $moduleName, '@before' => 'Magento_Backend']
                ]
            ]
        ]);
        $configGenerator->write($this->cache->getDataByKey('dir') . '/etc/adminhtml/routes.xml');

        return $route;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            [$moduleName, $vendor, $module, $root] = $this->getModuleInfo($input);
        } catch (Exception $e) {
            return $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
        }

        $controllerPath = $input->getArgument(self::ARG_CONTROLLER_PATH);
        if (!preg_match('/[A-Z][a-z]+(\\[A-Z][a-z]+)*/', $controllerPath)) {
            return $output->writeln('<error>Invalid controller path.</error>');
        }

        $modelPath = $input->getArgument(self::ARG_MODEL_PATH);
        if (!preg_match('/[A-Z][a-z]+(\\[A-Z][a-z]+)*/', $modelPath)) {
            return $output->writeln('<error>Invalid model path.</error>');
        }

        try {
            $route = $this->getRoute($input);
        } catch (Exception $e) {
            return $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
        }

        $controllerDir = $root . '/Controller/Adminhtml/' . str_replace('\\', '/', $controllerPath) . '/';
        $modelDir = $root . '/Model/' . str_replace('\\', '/', $modelPath) . '/';
        $resourceModelDir = $root . '/Model/ResourceModel/' . str_replace('\\', '/', $modelPath) . '/';
        $etcDir = $root . '/etc/';
        $layoutDir = $root . '/view/adminhtml/layout/';
        $uiComponentDir = $root . '/view/adminhtml/ui_component/';

        $controllerNamespace = $vendor . '\\' . $module . '\\Controller\\Adminhtml\\' . $controllerPath;
        $modelClass = $vendor . '\\' . $module . '\\Model\\' . $modelPath;
        $resourceModelClass = $vendor . '\\' . $module . '\\Model\\ResourceModel\\' . $modelPath;
        $collectionClass = $resourceModelClass . '\\Collection';
        $formDataProviderClass = $modelClass . '\\DataProvider';
        $listingDataProviderClass = $resourceModelClass . '\\Grid\\Collection';

        $key = strtolower(str_replace('\\', '_', $controllerPath));
        $persistKey = strtolower($module . '_' . $key);
        $uiNamespace = $route . '_' . $key;

        $aclResource = $moduleName . '::' . $uiNamespace;
        $uiListingActionPath = $route . '/' . $key;
        $uiFormSubmitUrl = $uiListingActionPath . '/save';

        $controllerInfo = [
            'persist_key' => $persistKey,
            'active_menu' => $moduleName . '::' . $persistKey,
            'page_title'  => str_replace('\\', ' ', $controllerPath),
            'model_class' => $modelClass
        ];

        $this->createIndexController($controllerDir, $controllerNamespace, $controllerInfo);
        $this->createNewController($controllerDir, $controllerNamespace);
        $this->createEditController($controllerDir, $controllerNamespace, $controllerInfo);
        $this->createDeleteController($controllerDir, $controllerNamespace, $controllerInfo);
        $this->createSaveController($controllerDir, $controllerNamespace, $controllerInfo);
        $this->createMassSaveController($controllerDir, $controllerNamespace, $controllerInfo);

        $this->createIndexLayout($layoutDir, $uiNamespace);
        $this->createNewLayout($layoutDir, $uiNamespace);
        $this->createEditLayout($layoutDir, $uiNamespace);

        $this->createListingDataProvider(
            $resourceModelDir,
            $etcDir,
            $uiNamespace,
            $listingDataProviderClass,
            $collectionClass,
            $resourceModelClass
        );
        $this->createFormDataProvider($modelDir, $uiNamespace, $formDataProviderClass, $collectionClass);

        $this->createListingUiComponent($uiComponentDir, $uiNamespace, $aclResource, $uiListingActionPath);
        $this->createFormUiComponent($uiComponentDir, $uiNamespace, $formDataProviderClass, $uiFormSubmitUrl);

        $output->writeln('<info>UI related files created.</info>');
    }

    /**
     * Create listing data provider
     *
     * @param string $resourceModelDir
     * @param string $etcDir
     * @param string $uiNamespace
     * @param string $dataProviderClass
     * @param string $collectionClass
     * @param string $resourceModelClass
     * @throws Exception
     */
    private function createListingDataProvider(
        $resourceModelDir,
        $etcDir,
        $uiNamespace,
        $dataProviderClass,
        $collectionClass,
        $resourceModelClass
    ) {
        $this->generateFile(
            $resourceModelDir . 'Grid/Collection.php',
            function () use ($dataProviderClass, $collectionClass, $resourceModelClass, $uiNamespace) {
                return (new ClassGenerator($dataProviderClass))
                    ->addUse('CrazyCat\Base\Model\ResourceModel\Grid\AbstractCollection')
                    ->addUse('Magento\Framework\Api\Search\SearchResultInterface')
                    ->addUse('Magento\Framework\View\Element\UiComponent\DataProvider\Document')
                    ->addUse($collectionClass, 'ResourceCollection')
                    ->addUse($resourceModelClass, 'ResourceModel')
                    ->setExtendedClass($collectionClass)
                    ->setImplementedInterfaces(['Magento\Framework\Api\Search\SearchResultInterface'])
                    ->addTrait('AbstractCollection')
                    ->addProperty('_eventPrefix', "{$uiNamespace}_grid_collection", PropertyGenerator::FLAG_PROTECTED)
                    ->addMethod(
                        '_construct',
                        [],
                        MethodGenerator::FLAG_PROTECTED,
                        '$this->_init(Document::class, ResourceModel::class);',
                        (new DocBlockGenerator())->setTag((new GenericTag('inheritDoc')))
                    );
            }
        );
        $this->addListingDataProviderDi($etcDir, $uiNamespace, $dataProviderClass);
    }

    /**
     * Create form data provider
     *
     * @param string $modelDir
     * @param string $dataProviderClass
     * @param string $collectionClass
     */
    private function createFormDataProvider(
        $modelDir,
        $uiNamespace,
        $dataProviderClass,
        $collectionClass
    ) {
        $this->generateFile(
            $modelDir . 'DataProvider.php',
            function () use ($dataProviderClass, $collectionClass, $uiNamespace) {
                return (new ClassGenerator($dataProviderClass))
                    ->addUse('CrazyCat\Base\Model\AbstractDataProvider')
                    ->addUse($collectionClass)
                    ->setExtendedClass('CrazyCat\Base\Model\AbstractDataProvider')
                    ->addProperty('persistKey', $uiNamespace, PropertyGenerator::FLAG_PROTECTED, 'string')
                    ->addMethod(
                        'init',
                        [],
                        MethodGenerator::FLAG_PROTECTED,
                        '$this->initCollection(Collection::class);',
                        (new DocBlockGenerator())->setTag((new GenericTag('inheritDoc')))
                    );
            }
        );
    }

    /**
     * Add listing data provider DI
     *
     * @param string $etcDir
     * @param string $uiNamespace
     * @param string $dataProviderClass
     * @throws Exception
     */
    private function addListingDataProviderDi($etcDir, $uiNamespace, $dataProviderClass)
    {
        $dataProviderName = $uiNamespace . '_listing_data_provider';
        $filename = $etcDir . 'di.xml';
        if ($this->filesystemDriver->isFile($filename)) {
            $root = simplexml_load_file($filename);
        } else {
            $configGenerator = new XmlConfigGenerator();
            $root = $configGenerator->setRoot('config', 'urn:magento:framework:ObjectManager/etc/config.xsd');
        }
        $factoryClass = 'Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory';
        $argumentNodes = $root->xpath(
            '/config/type[@class="' . $factoryClass . '"]/arguments/argument[@name="collections"]'
        );
        if (empty($argumentNodes)) {
            XmlGenerator::assignDataToNode($root, [
                'type' => [
                    '@name'     => $factoryClass,
                    'arguments' => [
                        'argument' => [
                            '@name'           => 'collections',
                            '@xmlns:xsi:type' => 'array',
                            'item'            => [
                                '@name'           => $dataProviderName,
                                '@xmlns:xsi:type' => 'string',
                                $dataProviderClass
                            ]
                        ]
                    ]
                ]
            ]);
        } else {
            XmlGenerator::assignDataToNode($argumentNodes[0], [
                'item' => ['@name' => $dataProviderName, '@xmlns:xsi:type' => 'string', $dataProviderClass]
            ]);
        }
        $root->saveXML($filename);
    }

    /**
     * Create listing UI component
     *
     * @param string $dir
     * @param string $namespace
     * @param string $aclResource
     * @param string $actionPath
     * @throws LocalizedException
     */
    private function createListingUiComponent($dir, $namespace, $aclResource, $actionPath)
    {
        $namespace .= '_listing';
        $uiListingGenerator = new UiListingGenerator($namespace, $aclResource, $actionPath);
        $uiListingGenerator->addColumn('name', [
            'filter' => 'text',
            'label'  => ['@translate' => 'true', 'Name'],
            'editor' => [
                'editorType' => 'text',
                'validation' => [
                    'rule' => [
                        '@name'           => 'required-entry',
                        '@xmlns:xsi:type' => 'boolean',
                        'true'
                    ]
                ]
            ]
        ]);
        $uiListingGenerator->write($dir . $namespace . '.xml');
    }

    /**
     * Create form UI component
     *
     * @param string $dir
     * @param string $namespace
     * @param string $dataProviderClass
     * @param string $submitUrl
     */
    private function createFormUiComponent($dir, $namespace, $dataProviderClass, $submitUrl)
    {
        $namespace .= '_form';

        $uiFormGenerator = new UiFormGenerator($namespace, $dataProviderClass, $submitUrl);
        $uiFormGenerator->addButton('back', 'Back', 'back', '*/*/index');
        $uiFormGenerator->addButton('reset', 'Reset', 'reset');
        $uiFormGenerator->addButton(
            'save',
            'Save',
            'save primary',
            null,
            null,
            [
                'data_attribute' => [
                    'mage-init' => [
                        'buttonAdapter' => [
                            'actions' => [
                                [
                                    'targetName' => "{$namespace}.{$namespace}",
                                    'actionName' => 'save',
                                    'params'     => [true, ['back' => 'continue']]
                                ]
                            ]
                        ]
                    ]
                ],
                'class_name'     => 'Magento\Ui\Component\Control\SplitButton',
                'options'        => [
                    [
                        'id_hard'        => 'save_and_close',
                        'label'          => 'Save and Close',
                        'data_attribute' => [
                            'mage-init' => [
                                'buttonAdapter' => [
                                    'actions' => [
                                        [
                                            'targetName' => "{$namespace}.{$namespace}",
                                            'actionName' => 'save',
                                            'params'     => [true, ['back' => 'close']]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $fieldsetNode = $uiFormGenerator->addFieldset('general', null);
        $uiFormGenerator->addField(
            $fieldsetNode,
            'id',
            'input',
            ['dataType' => 'text', 'visible' => 'false', 'dataScope' => 'data.id'],
            ['data' => ['config' => ['source' => 'data']]]
        );

        $uiFormGenerator->write($dir . $namespace . '.xml');
    }

    /**
     * Create layout for index action
     *
     * @param string $dir
     * @param string $key
     * @throws LocalizedException
     */
    private function createIndexLayout($dir, $key)
    {
        $layoutGenerator = new LayoutGenerator();
        $container = $layoutGenerator->referenceContainer('content');
        $layoutGenerator->addUiComponent($container, $key . '_listing');
        $layoutGenerator->write($dir . $key . '_index.xml');
    }

    /**
     * Create layout for new action
     *
     * @param string $dir
     * @param string $key
     */
    private function createNewLayout($dir, $key)
    {
        $layoutGenerator = new LayoutGenerator();
        $layoutGenerator->addUpdate($key . '_edit');
        $layoutGenerator->write($dir . $key . '_new.xml');
    }

    /**
     * Create layout for edit action
     *
     * @param string $dir
     * @param string $key
     * @throws LocalizedException
     */
    private function createEditLayout($dir, $key)
    {
        $layoutGenerator = new LayoutGenerator();
        $layoutGenerator->addUpdate('editor');
        $container = $layoutGenerator->referenceContainer('content');
        $layoutGenerator->addUiComponent($container, $key . '_form');
        $layoutGenerator->write($dir . $key . '_edit.xml');
    }

    /**
     * Create index controller
     *
     * @param string $dir
     * @param string $namespace
     * @param array  $info
     * @return void
     * @throws FileSystemException
     */
    private function createIndexController($dir, $namespace, $info)
    {
        $this->generateFile($dir . 'Index.php', function () use ($namespace, $info) {
            return (new ClassGenerator($namespace . '\Index'))
                ->setExtendedClass('CrazyCat\Base\Controller\Adminhtml\AbstractIndexAction')
                ->addUse('CrazyCat\Base\Controller\Adminhtml\AbstractIndexAction')
                ->addMethod(
                    'execute',
                    [],
                    MethodGenerator::FLAG_PUBLIC,
                    sprintf(
                        'return $this->render(\'%s\', \'%s\', \'%s\');',
                        $info['persist_key'],
                        $info['active_menu'],
                        $info['page_title']
                    ),
                    (new DocBlockGenerator())->setTag((new GenericTag('inheritDoc')))
                );
        });
    }

    /**
     * Create new controller
     *
     * @param string $dir
     * @param string $namespace
     * @return void
     * @throws FileSystemException
     */
    private function createNewController($dir, $namespace)
    {
        $this->generateFile($dir . 'NewAction.php', function () use ($namespace) {
            return (new ClassGenerator($namespace . '\NewAction'))
                ->setExtendedClass('CrazyCat\Base\Controller\Adminhtml\AbstractNewAction')
                ->addUse('CrazyCat\Base\Controller\Adminhtml\AbstractNewAction');
        });
    }

    /**
     * Create edit controller
     *
     * @param string $dir
     * @param string $namespace
     * @param array  $info
     * @return void
     * @throws FileSystemException
     */
    private function createEditController($dir, $namespace, $info)
    {
        $this->generateFile($dir . 'Edit.php', function () use ($namespace, $info) {
            return (new ClassGenerator($namespace . '\Edit'))
                ->setExtendedClass('CrazyCat\Base\Controller\Adminhtml\AbstractEditAction')
                ->addUse('CrazyCat\Base\Controller\Adminhtml\AbstractEditAction')
                ->addUse($info['model_class'], 'Model')
                ->addMethod(
                    'execute',
                    [],
                    MethodGenerator::FLAG_PUBLIC,
                    sprintf(
                        'return $this->render(%s, \'%s\', \'%s\', \'%s\', \'%s\');',
                        'Model::class',
                        'Specified item does not exist.',
                        $info['active_menu'],
                        'Create New Item',
                        'Edit Item (ID: %1)'
                    ),
                    (new DocBlockGenerator())->setTag((new GenericTag('inheritDoc')))
                );
        });
    }

    /**
     * Create delete controller
     *
     * @param string $dir
     * @param string $namespace
     * @param array  $info
     * @return void
     * @throws FileSystemException
     */
    private function createDeleteController($dir, $namespace, $info)
    {
        $this->generateFile($dir . 'Delete.php', function () use ($namespace, $info) {
            return (new ClassGenerator($namespace . '\Delete'))
                ->setExtendedClass('CrazyCat\Base\Controller\Adminhtml\AbstractDeleteAction')
                ->addUse('CrazyCat\Base\Controller\Adminhtml\AbstractDeleteAction')
                ->addUse($info['model_class'], 'Model')
                ->addMethod(
                    'execute',
                    [],
                    MethodGenerator::FLAG_PUBLIC,
                    sprintf(
                        'return $this->delete(%s, \'%s\', \'%s\');',
                        'Model::class',
                        'Specified item does not exist.',
                        'Item deleted.'
                    ),
                    (new DocBlockGenerator())->setTag((new GenericTag('inheritDoc')))
                );
        });
    }

    /**
     * Create save controller
     *
     * @param string $dir
     * @param string $namespace
     * @param array  $info
     * @return void
     * @throws FileSystemException
     */
    private function createSaveController($dir, $namespace, $info)
    {
        $this->generateFile($dir . 'Save.php', function () use ($namespace, $info) {
            return (new ClassGenerator($namespace . '\Save'))
                ->setExtendedClass('CrazyCat\Base\Controller\Adminhtml\AbstractSaveAction')
                ->addUse('CrazyCat\Base\Controller\Adminhtml\AbstractSaveAction')
                ->addUse($info['model_class'], 'Model')
                ->addMethod(
                    'execute',
                    [],
                    MethodGenerator::FLAG_PUBLIC,
                    sprintf(
                        'return $this->save(%s, \'%s\', \'%s\', \'%s\');',
                        'Model::class',
                        'Specified item does not exist.',
                        'Item saved successfully.',
                        $info['persist_key']
                    ),
                    (new DocBlockGenerator())->setTag((new GenericTag('inheritDoc')))
                );
        });
    }

    /**
     * Create mass save controller
     *
     * @param string $dir
     * @param string $namespace
     * @param array  $info
     * @return void
     * @throws FileSystemException
     */
    private function createMassSaveController($dir, $namespace, $info)
    {
        $this->generateFile($dir . 'MassSave.php', function () use ($namespace, $info) {
            return (new ClassGenerator($namespace . '\MassSave'))
                ->setExtendedClass('CrazyCat\Base\Controller\Adminhtml\AbstractMassSaveAction')
                ->addUse('CrazyCat\Base\Controller\Adminhtml\AbstractMassSaveAction')
                ->addUse($info['model_class'], 'Model')
                ->addMethod(
                    'execute',
                    [],
                    MethodGenerator::FLAG_PUBLIC,
                    'return $this->save(Model::class);',
                    (new DocBlockGenerator())->setTag((new GenericTag('inheritDoc')))
                );
        });
    }
}
