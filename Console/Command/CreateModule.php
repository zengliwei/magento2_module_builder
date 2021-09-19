<?php
/**
 * Copyright (c) Zengliwei. All rights reserved.
 * Each source file in this distribution is licensed under OSL 3.0, see LICENSE for details.
 */

namespace CrazyCat\ModuleBuilder\Console\Command;

use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\FileGenerator;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_module_builder
 */
class CreateModule extends AbstractCreateCommand
{
    private const ARG_PACKAGE_NAME = 'package-name';
    private const OPT_AUTHOR = 'author';
    private const OPT_PACKAGE_DESC = 'package-description';
    private const OPT_PACKAGE_LICENSE = 'license';
    private const OPT_PACKAGE_VERSION = 'package-version';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param Filesystem  $filesystem
     * @param Context     $context
     * @param string|null $name
     */
    public function __construct(
        Filesystem $filesystem,
        Context $context,
        string $name = null
    ) {
        $this->filesystem = $filesystem;
        parent::__construct($context, $name);
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('module-builder:create-module');
        $this->setDescription('Create a new module');
        $this->setDefinition(
            [
                new InputArgument(
                    self::ARG_MODULE_NAME,
                    InputArgument::REQUIRED,
                    'Module name, format is like Vendor_Module, uppercase every piece, case sensitive'
                ),
                new InputArgument(
                    self::ARG_PACKAGE_NAME,
                    InputArgument::REQUIRED,
                    'Package name'
                ),
                new InputOption(
                    self::OPT_AUTHOR,
                    'a',
                    InputOption::VALUE_REQUIRED,
                    'Author to show in copyright, composer.json etc.',
                    'Anonymous'
                ),
                new InputOption(
                    self::OPT_PACKAGE_DESC,
                    'd',
                    InputOption::VALUE_REQUIRED,
                    'Package description',
                    'A Magento 2 module.'
                ),
                new InputOption(
                    self::OPT_PACKAGE_VERSION,
                    'e',
                    InputOption::VALUE_REQUIRED,
                    'Package version',
                    '1.0.0'
                ),
                new InputOption(
                    self::OPT_PACKAGE_LICENSE,
                    'l',
                    InputOption::VALUE_REQUIRED,
                    'Package license'
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
        $packageName = $input->getArgument(self::ARG_PACKAGE_NAME);
        $author = $input->getOption(self::OPT_AUTHOR);
        $version = $input->getOption(self::OPT_PACKAGE_VERSION);

        if ($this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName)) {
            return $output->writeln('<error>Module already exists.</error>');
        }
        if (!preg_match('/[A-Z][a-z]+_[A-Z][a-z]+/', $moduleName)) {
            return $output->writeln('<error>Invalid module name format, it should be like Vendor_Module.</error>');
        }
        if (!preg_match('/[a-z]+\/[a-z0-9_\-]+[a-z]/', $packageName)) {
            return $output->writeln('<error>Invalid composer package name.</error>');
        }
        if (!preg_match('/\d+(\.\d+)*/', $version)) {
            return $output->writeln('<error>Invalid version.</error>');
        }

        [$vendor, $module] = explode('_', $moduleName);
        $dir = $this->filesystem->getDirectoryWrite(DirectoryList::APP)->getAbsolutePath()
            . 'code/' . $vendor . '/' . $module;
        if (!$this->filesystemDriver->isDirectory($dir)) {
            $this->filesystemDriver->createDirectory($dir, 0755);
        }

        $this->cache->addData(
            [
                'module_name' => $moduleName,
                'vendor'      => $vendor,
                'module'      => $module,
                'dir'         => $dir,
                'author'      => $author,
                'composer'    => [
                    'package_name' => $packageName,
                    'description'  => $input->getOption(self::OPT_PACKAGE_DESC),
                    'license'      => $input->getOption(self::OPT_PACKAGE_LICENSE),
                    'version'      => $version
                ]
            ]
        );

        $this->createRegistrationFile();
        $this->createModuleEtcFile();
        $this->createComposerFile();

        $output->writeln('<info>Module created</info>');
    }

    /**
     * Create registration file
     *
     * @return void
     */
    private function createRegistrationFile()
    {
        $author = $this->cache->getDataByKey('author');
        $moduleName = $this->cache->getDataByKey('module_name');

        $copyright = sprintf('Copyright (c) %s. All rights reserved.', $author)
            . FileGenerator::LINE_FEED
            . 'See COPYING.txt for license details.';

        (new FileGenerator())
            ->setFilename($this->cache->getDataByKey('dir') . '/registration.php')
            ->setDocBlock((new DocBlockGenerator())->setLongDescription($copyright))
            ->setUse(ComponentRegistrar::class)
            ->setBody("ComponentRegistrar::register(ComponentRegistrar::MODULE, '$moduleName', __DIR__);")
            ->write();
    }

    /**
     * Create etc/module.xml
     *
     * @return void
     */
    private function createModuleEtcFile()
    {
        $moduleName = $this->cache->getDataByKey('module_name');
        $xmlStr = <<<XML
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="$moduleName">
        <sequence/>
    </module>
</config>
XML;

        $dir = $this->cache->getDataByKey('dir') . '/etc';
        if (!$this->filesystemDriver->isDirectory($dir)) {
            $this->filesystemDriver->createDirectory($dir, 0755);
        }
        $this->filesystemDriver->filePutContents($dir . '/module.xml', $xmlStr);
    }

    /**
     * Create composer.json
     *
     * @return void
     */
    private function createComposerFile()
    {
        $data = [
            'name'        => $this->cache->getDataByPath('composer/package_name'),
            'description' => $this->cache->getDataByPath('composer/description'),
            'type'        => 'magento2-module',
            'version'     => $this->cache->getDataByPath('composer/version'),
            'license'     => [$this->cache->getDataByPath('composer/license')],
            'require'     => [
                'php'               => '~7.4.0',
                'magento/framework' => '103.0.*'
            ],
            'autoload'    => [
                'files' => 'registration.php',
                'psr-4' => [
                    $this->cache->getDataByKey('vendor') . '\\' . $this->cache->getDataByKey('module') => ''
                ]
            ]
        ];

        $this->filesystemDriver->filePutContents(
            $this->cache->getDataByKey('dir') . '/composer.json',
            json_encode($data, JSON_PRETTY_PRINT)
        );
    }
}
