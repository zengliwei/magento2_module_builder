<?php
/**
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

use CrazyCat\ModuleBuilder\Model\Generator\LayoutGenerator;
use CrazyCat\ModuleBuilder\Model\Generator\UiFormGenerator;
use CrazyCat\ModuleBuilder\Model\Generator\UiListingGenerator;
use Exception;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlock\Tag\GenericTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Magento\Framework\App\Area;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\Route\Config\Reader;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package CrazyCat\ModuleBuilder
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
class CreateAdminhtmlUi extends AbstractCreateCommand
{
    private const ARG_CONTROLLER_PATH = 'controller-path';
    private const ARG_MODEL_PATH = 'model-path';
    private const OPT_ROUTE_NAME = 'route-name';

    private AreaList $areaList;
    private Reader $routeConfigReader;

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
        $this->setDescription('Create a list page in admin panel');
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

        $xmlStr = <<<XML
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:App/etc/routes.xsd">
    <router id="admin">
        <route id="$route" frontName="$route">
            <module name="$moduleName" before="Magento_Backend"/>
        </route>
    </router>
</config>
XML;
        $dir = $this->cache->getDataByKey('dir') . '/etc/adminhtml';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($dir . '/routes.xml', $xmlStr);

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

        $dir = $root . '/Controller/Adminhtml/' . str_replace('\\', '/', $controllerPath) . '/';
        $namespace = $vendor . '\\' . $module . '\\Controller\\' . $controllerPath . '\\';
        $key = strtolower($module . '_' . str_replace('\\', '_', $controllerPath));
        $controllerInfo = [
            'key'         => $key,
            'active_menu' => $moduleName . '::' . $key,
            'page_title'  => str_replace('\\', ' ', $controllerPath),
            'model_class' => $vendor . '\\' . $module . '\\Model\\' . $modelPath
        ];

        $this->createIndexController($dir . 'Index.php', $namespace . 'Index', $controllerInfo);
        $this->createNewController($dir . 'NewAction.php', $namespace . 'NewAction');
        $this->createEditController($dir . 'Edit.php', $namespace . 'Edit', $controllerInfo);
        $this->createDeleteController($dir . 'Delete.php', $namespace . 'Delete', $controllerInfo);
        $this->createSaveController($dir . 'Save.php', $namespace . 'Save', $controllerInfo);
        $this->createMassSaveController($dir . 'MassSave.php', $namespace . 'MassSave', $controllerInfo);

        $layoutKey = $route . '_' . $key;

        if (!is_dir($dir = $root . '/view/adminhtml/layout')) {
            mkdir($dir, 0755, true);
        }
        $this->createIndexLayout($layoutKey, $dir);
        $this->createNewLayout($layoutKey, $dir);
        $this->createEditLayout($layoutKey, $dir);

        if (!is_dir($dir = $root . '/view/adminhtml/ui_component')) {
            mkdir($dir, 0755, true);
        }
        $this->createListingUiComponent($route, $key, $dir);
        $this->createFormUiComponent($route, $key, $modelPath, $dir);

        $output->writeln('<info>UI related files created.</info>');
    }

    private function createListingUiComponent($route, $key, $dir)
    {
        $namespace = 'test_module_test_listing';
        $aclResource = 'Vendor_Module::test_module_test';
        $actionPath = 'test/test';

        $uiListingGenerator = new UiListingGenerator($namespace, $aclResource, $actionPath);
        $uiListingGenerator->addColumn('name', [
            'filter' => 'text',
            'label'  => ['@translate' => 'true', 'Name'],
            'editor' => [
                'editorType' => 'text',
                'validation' => [
                    'rule' => [
                        '@name' => 'required-entry',
                        '@xmlns:xsi:type' => 'boolean',
                        'true'
                    ]
                ]
            ]
        ]);
        $uiListingGenerator->write($dir . '/' . $namespace . '.xml');
    }

    private function createFormUiComponent($route, $key, $modelPath, $dir)
    {
        $namespace = 'test_module_test_form';
        $dataProviderClass = 'Vendor\Module\Model\Test\DataProvider';
        $submitUrl = 'test/test/save';

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

        $fieldsetNode = $uiFormGenerator->addFieldset('general');
        $uiFormGenerator->addField(
            $fieldsetNode,
            'id',
            'input',
            [
                'dataType'  => 'text',
                'visible'   => 'false',
                'dataScope' => 'data.id'
            ],
            [
                'data' => [
                    'config' => [
                        'source' => 'data'
                    ]
                ]
            ]
        );
        $uiFormGenerator->write($dir . '/' . $namespace . '.xml');
    }

    /**
     * @param string $key
     * @param string $dir
     * @throws LocalizedException
     */
    private function createIndexLayout($key, $dir)
    {
        $layoutGenerator = new LayoutGenerator();
        $container = $layoutGenerator->referenceContainer('content');
        $layoutGenerator->addUiComponent($container, $key . '_listing');
        $layoutGenerator->write($dir . '/' . $key . '_index.xml');
    }

    /**
     * @param string $key
     * @param string $dir
     */
    private function createNewLayout($key, $dir)
    {
        $layoutGenerator = new LayoutGenerator();
        $layoutGenerator->addUpdate($key . '_edit');
        $layoutGenerator->write($dir . '/' . $key . '_new.xml');
    }

    /**
     * @param string $key
     * @param string $dir
     * @throws LocalizedException
     */
    private function createEditLayout($key, $dir)
    {
        $layoutGenerator = new LayoutGenerator();
        $layoutGenerator->addUpdate('editor');
        $container = $layoutGenerator->referenceContainer('content');
        $layoutGenerator->addUiComponent($container, $key . '_form');
        $layoutGenerator->write($dir . '/' . $key . '_edit.xml');
    }

    /**
     * @param string $filename
     * @param string $class
     * @param array  $info
     * @return void
     */
    private function createIndexController($filename, $class, $info)
    {
        $this->generateFile($filename, function () use ($class, $info) {
            return (new ClassGenerator($class))
                ->setExtendedClass('CrazyCat\Base\Controller\Adminhtml\AbstractIndexAction')
                ->addUse('CrazyCat\Base\Controller\Adminhtml\AbstractIndexAction')
                ->addMethod(
                    'execute',
                    [],
                    MethodGenerator::FLAG_PUBLIC,
                    sprintf(
                        'return $this->render(\'%s\', \'%s\', \'%s\');',
                        $info['key'],
                        $info['active_menu'],
                        $info['page_title']
                    ),
                    (new DocBlockGenerator())->setTag((new GenericTag('inheritDoc')))
                );
        });
    }

    /**
     * @param string $filename
     * @param string $class
     * @return void
     */
    private function createNewController($filename, $class)
    {
        $this->generateFile($filename, function () use ($class) {
            return (new ClassGenerator($class))
                ->setExtendedClass('CrazyCat\Base\Controller\Adminhtml\AbstractNewAction')
                ->addUse('CrazyCat\Base\Controller\Adminhtml\AbstractNewAction');
        });
    }

    /**
     * @param string $filename
     * @param string $class
     * @param array  $info
     * @return void
     */
    private function createEditController($filename, $class, $info)
    {
        $this->generateFile($filename, function () use ($class, $info) {
            return (new ClassGenerator($class))
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
     * @param string $filename
     * @param string $class
     * @param array  $info
     * @return void
     */
    private function createDeleteController($filename, $class, $info)
    {
        $this->generateFile($filename, function () use ($class, $info) {
            return (new ClassGenerator($class))
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
     * @param string $filename
     * @param string $class
     * @param array  $info
     * @return void
     */
    private function createSaveController($filename, $class, $info)
    {
        $this->generateFile($filename, function () use ($class, $info) {
            return (new ClassGenerator($class))
                ->setExtendedClass('CrazyCat\Base\Controller\Adminhtml\AbstractSaveAction')
                ->addUse('CrazyCat\Base\Controller\Adminhtml\AbstractSaveAction')
                ->addUse($info['model_class'], 'Model')
                ->addMethod(
                    'execute',
                    [],
                    MethodGenerator::FLAG_PUBLIC,
                    sprintf(
                        'return $this->save(%s, \'%s\', \'%s\');',
                        'Model::class',
                        'Specified item does not exist.',
                        'Item saved successfully.',
                        $info['key']
                    ),
                    (new DocBlockGenerator())->setTag((new GenericTag('inheritDoc')))
                );
        });
    }

    /**
     * @param string $filename
     * @param string $class
     * @param array  $info
     * @return void
     */
    private function createMassSaveController($filename, $class, $info)
    {
        $this->generateFile($filename, function () use ($class, $info) {
            return (new ClassGenerator($class))
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
