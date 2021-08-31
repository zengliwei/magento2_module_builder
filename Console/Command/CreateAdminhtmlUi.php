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
            'key' => $key,
            'active_menu' => $moduleName . '::' . $key,
            'page_title' => str_replace('\\', ' ', $controllerPath),
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
        $uiComponentKey = $route . '_' . $key;
        $xmlStr = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<listing xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Ui:etc/ui_configuration.xsd">
    <argument name="data" xsi:type="array">
        <item name="js_config" xsi:type="array">
            <item name="provider" xsi:type="string">{$uiComponentKey}_listing.listing_data_source</item>
        </item>
    </argument>
    <settings>
        <buttons>
            <button name="add">
                <label translate="true">Add New Brand</label>
                <class>primary</class>
                <url path="*/*/new"/>
            </button>
        </buttons>
        <spinner>{$uiComponentKey}_columns</spinner>
        <deps>
            <dep>{$uiComponentKey}_listing.{$uiComponentKey}_listing_data_provider</dep>
        </deps>
    </settings>
    <dataSource name="listing_data_source" component="Magento_Ui/js/grid/provider">
        <settings>
            <storageConfig>
                <param name="indexField" xsi:type="string">id</param>
            </storageConfig>
            <updateUrl path="mui/index/render"/>
        </settings>
        <aclResource>CrazyCat_Brand::{$uiComponentKey}</aclResource>
        <dataProvider class="Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider"
                      name="{$uiComponentKey}_listing_data_provider">
            <settings>
                <primaryFieldName>id</primaryFieldName>
                <requestFieldName>id</requestFieldName>
            </settings>
        </dataProvider>
    </dataSource>
    <listingToolbar name="listing_top">
        <settings>
            <sticky>true</sticky>
        </settings>
        <bookmark name="bookmarks"/>
        <columnsControls name="columns_controls"/>
        <filterSearch name="fulltext"/>
        <filters name="listing_filters">
            <settings>
                <templates>
                    <filters>
                        <select>
                            <param name="template" xsi:type="string">ui/grid/filters/elements/ui-select</param>
                            <param name="component" xsi:type="string">Magento_Ui/js/form/element/ui-select</param>
                        </select>
                    </filters>
                </templates>
            </settings>
        </filters>
        <paging name="listing_paging"/>
        <massaction name="listing_actions">
            <action name="edit">
                <settings>
                    <callback>
                        <target>editSelected</target>
                        <provider>{$uiComponentKey}_listing.{$uiComponentKey}_listing.{$uiComponentKey}_columns_editor</provider>
                    </callback>
                    <type>edit</type>
                    <label translate="true">Edit</label>
                </settings>
            </action>
        </massaction>
    </listingToolbar>
    <columns name="{$uiComponentKey}_columns">
        <settings>
            <editorConfig>
                <param name="clientConfig" xsi:type="array">
                    <item name="saveUrl" xsi:type="url" path="brand/brand/massSave"/>
                    <item name="validateBeforeSave" xsi:type="boolean">false</item>
                </param>
                <param name="indexField" xsi:type="string">id</param>
                <param name="enabled" xsi:type="boolean">true</param>
                <param name="selectProvider" xsi:type="string">
                    {$uiComponentKey}_listing.{$uiComponentKey}_listing.{$uiComponentKey}_columns.ids
                </param>
            </editorConfig>
            <childDefaults>
                <param name="fieldAction" xsi:type="array">
                    <item name="provider" xsi:type="string">
                        {$uiComponentKey}_listing.{$uiComponentKey}_listing.{$uiComponentKey}_columns_editor
                    </item>
                    <item name="target" xsi:type="string">startEdit</item>
                    <item name="params" xsi:type="array">
                        <item name="0" xsi:type="string">\${ \$.\$data.rowIndex }</item>
                        <item name="1" xsi:type="boolean">true</item>
                    </item>
                </param>
            </childDefaults>
        </settings>
        <selectionsColumn name="ids">
            <settings>
                <indexField>id</indexField>
            </settings>
        </selectionsColumn>
        <column name="id">
            <settings>
                <filter>textRange</filter>
                <label translate="true">ID</label>
                <sorting>asc</sorting>
            </settings>
        </column>
        <column name="name">
            <settings>
                <filter>text</filter>
                <label translate="true">Name</label>
                <editor>
                    <validation>
                        <rule name="required-entry" xsi:type="boolean">true</rule>
                    </validation>
                    <editorType>text</editorType>
                </editor>
            </settings>
        </column>
        <actionsColumn name="actions" class="CrazyCat\Base\Ui\Component\Listing\Column\Actions">
            <settings>
                <fieldAction>
                    <params>
                        <param name="route" xsi:type="string">{$route}/{$key}</param>
                    </params>
                </fieldAction>
            </settings>
        </actionsColumn>
    </columns>
</listing>
XML;
        file_put_contents($dir . '/' . $uiComponentKey . '_listing.xml', $xmlStr);
    }

    private function createFormUiComponent($route, $key, $modelPath, $dir)
    {
        $namespace = 'test_module_test';
        $dataProviderClass = 'vendor\Module\Model\Test\DataProvider';
        $submitUrl = 'test/test/save';
        $uiFormGenerator = new UiFormGenerator($namespace, $dataProviderClass, $submitUrl);
        $uiFormGenerator->addButton('back', 'Back', 'back', '*/*/index');
        $uiFormGenerator->write($dir . '/' . $namespace . '_form.xml');

        return;
        $uiComponentKey = $route . '_' . $key;
        $vendor = $this->cache->getDataByKey('vendor');
        $module = $this->cache->getDataByKey('vendor');
        $dataProvider = "{$vendor}\{$module}\Model\{$modelPath}\DataProvider";
        $xmlStr = <<<XML
<form xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Ui:etc/ui_configuration.xsd">
    <argument name="data" xsi:type="array">
        <item name="js_config" xsi:type="array">
            <item name="provider" xsi:type="string">{$uiComponentKey}_form.form_data_source</item>
        </item>
        <item name="label" xsi:type="string" translate="true">General Information</item>
        <item name="template" xsi:type="string">templates/form/collapsible</item>
    </argument>
    <settings>
        <buttons>
            <button name="back">
                <label translate="true">Back</label>
                <class>back</class>
                <url path="*/*/index"/>
            </button>
            <button name="reset">
                <label translate="true">Reset</label>
                <class>reset</class>
            </button>
            <button name="save">
                <label translate="true">Save</label>
                <class>save primary</class>
                <param name="data_attribute" xsi:type="array">
                    <item name="mage-init" xsi:type="array">
                        <item name="buttonAdapter" xsi:type="array">
                            <item name="actions" xsi:type="array">
                                <item name="0" xsi:type="array">
                                    <item name="targetName" xsi:type="string">{$uiComponentKey}_form.{$uiComponentKey}_form</item>
                                    <item name="actionName" xsi:type="string">save</item>
                                    <item name="params" xsi:type="array">
                                        <item name="0" xsi:type="boolean">true</item>
                                        <item name="1" xsi:type="array">
                                            <item name="back" xsi:type="string">continue</item>
                                        </item>
                                    </item>
                                </item>
                            </item>
                        </item>
                    </item>
                </param>
                <param name="class_name" xsi:type="string">Magento\Ui\Component\Control\SplitButton</param>
                <param name="options" xsi:type="array">
                    <item name="0" xsi:type="array">
                        <item name="id_hard" xsi:type="string">save_and_close</item>
                        <item name="label" xsi:type="string">Save and Close</item>
                        <item name="data_attribute" xsi:type="array">
                            <item name="mage-init" xsi:type="array">
                                <item name="buttonAdapter" xsi:type="array">
                                    <item name="actions" xsi:type="array">
                                        <item name="0" xsi:type="array">
                                            <item name="targetName" xsi:type="string">
                                                {$uiComponentKey}_form.{$uiComponentKey}_form
                                            </item>
                                            <item name="actionName" xsi:type="string">save</item>
                                            <item name="params" xsi:type="array">
                                                <item name="0" xsi:type="boolean">true</item>
                                                <item name="1" xsi:type="array">
                                                    <item name="back" xsi:type="string">close</item>
                                                </item>
                                            </item>
                                        </item>
                                    </item>
                                </item>
                            </item>
                        </item>
                    </item>
                </param>
            </button>
        </buttons>
        <namespace>{$uiComponentKey}_form</namespace>
        <dataScope>data</dataScope>
        <deps>
            <dep>{$uiComponentKey}_form.{$uiComponentKey}_form_data_provider</dep>
        </deps>
    </settings>
    <dataSource name="form_data_source">
        <argument name="data" xsi:type="array">
            <item name="js_config" xsi:type="array">
                <item name="component" xsi:type="string">Magento_Ui/js/form/provider</item>
            </item>
        </argument>
        <settings>
            <submitUrl path="brand/brand/save"/>
        </settings>
        <dataProvider class="{$dataProvider}"
                      name="{$uiComponentKey}_form_data_provider">
            <settings>
                <requestFieldName>id</requestFieldName>
                <primaryFieldName>id</primaryFieldName>
            </settings>
        </dataProvider>
    </dataSource>
    <fieldset name="general">
        <settings>
            <label/>
        </settings>
        <field name="id" formElement="input">
            <argument name="data" xsi:type="array">
                <item name="config" xsi:type="array">
                    <item name="source" xsi:type="string">data</item>
                </item>
            </argument>
            <settings>
                <dataType>text</dataType>
                <visible>false</visible>
                <dataScope>data.id</dataScope>
            </settings>
        </field>
        <field name="name" formElement="input">
            <argument name="data" xsi:type="array">
                <item name="config" xsi:type="array">
                    <item name="source" xsi:type="string">data</item>
                </item>
            </argument>
            <settings>
                <dataType>text</dataType>
                <dataScope>data.name</dataScope>
                <label translate="true">Name</label>
                <notice translate="true">[store view]</notice>
                <validation>
                    <rule name="required-entry" xsi:type="boolean">true</rule>
                </validation>
            </settings>
        </field>
    </fieldset>
</form>
XML;
        file_put_contents($dir . '/' . $uiComponentKey . '_form.xml', $xmlStr);
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
