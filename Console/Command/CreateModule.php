<?php
/**
 * Copyright (c) 2021 Zengliwei
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

use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\FileGenerator;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package CrazyCat\ModuleBuilder
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
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
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
            ->setUse('Magento\Framework\Component\ComponentRegistrar')
            ->setBody("ComponentRegistrar::register(ComponentRegistrar::MODULE, '$moduleName', __DIR__);")
            ->write();
    }

    /**
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
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($dir . '/module.xml', $xmlStr);
    }

    /**
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

        file_put_contents(
            $this->cache->getDataByKey('dir') . '/composer.json',
            json_encode($data, JSON_PRETTY_PRINT)
        );
    }
}
