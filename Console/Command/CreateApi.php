<?php
/*
 * Copyright (c) Zengliwei. All rights reserved.
 * Each source file in this distribution is licensed under OSL 3.0, see LICENSE for details.
 */

namespace CrazyCat\ModuleBuilder\Console\Command;

use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\FileGenerator;
use Laminas\Code\Generator\InterfaceGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Code\Generator\ClassGenerator;
use Magento\Framework\DataObject;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DriverInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author  Zengliwei <zengliwei@163.com>
 * @url https://github.com/zengliwei/magento2_banner
 */
class CreateApi extends Command
{
    private const ARG_MODULE = 'module';
    private const ARG_PATH = 'path';
    private const ARG_FIELDS = 'fields';

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param DriverInterface $driver
     * @param Filesystem      $filesystem
     * @param string|null     $name
     */
    public function __construct(
        DriverInterface $driver,
        Filesystem $filesystem,
        string $name = null
    ) {
        $this->driver = $driver;
        $this->filesystem = $filesystem;
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('module-builder:create-api');
        $this->setDescription('Generate API data interface and related model files');
        $this->setDefinition(
            [
                new InputArgument(
                    self::ARG_MODULE,
                    InputArgument::REQUIRED,
                    'Module name, format is like Vendor_Module, uppercase every piece, case sensitive'
                ),
                new InputArgument(
                    self::ARG_PATH,
                    InputArgument::REQUIRED,
                    'Path, for example given Menu\Item, create Vendor\Module\Api\Data\Menu\ItemInterface'
                ),
                new InputArgument(
                    self::ARG_FIELDS,
                    InputArgument::REQUIRED,
                    'Fields, separated by comma'
                )
            ]
        );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getArgument(self::ARG_MODULE);
        $path = $input->getArgument(self::ARG_PATH);
        $fields = explode(',', $input->getArgument(self::ARG_FIELDS));

        if (!preg_match('/^[A-Z][a-zA-Z]*(\\\\[A-Z][a-zA-Z]*)*$/', $path)) {
            $output->writeln("<error>Invalid path `{$path}`.</error>");
            return;
        }
        foreach ($fields as $field) {
            if (!preg_match('/^[a-z][a-z\d]*(_[a-z][a-z\d]*+)*$/', $field)) {
                $output->writeln("<error>Invalid field name `{$field}`.</error>");
                return;
            }
        }

        $dirApp = $this->filesystem->getDirectoryRead(DirectoryList::APP)->getAbsolutePath();
        $dirModule = $dirApp . 'code/' . str_replace('_', '/', $module);
        $fileInterface = $dirModule . '/Api/Data/' . str_replace('\\', '/', $path) . 'Interface.php';
        $fileModel = $dirModule . '/Model/' . str_replace('\\', '/', $path) . '.php';

        if (!$this->driver->isDirectory($dirModule)) {
            $output->writeln('<error>Specified module doesn\'t exist or is not a local module.</error>');
            return;
        }
        if ($this->driver->isFile($fileInterface)) {
            $output->writeln('<error>Interface file already exist.</error>');
            return;
        }
        if ($this->driver->isFile($fileModel)) {
            $output->writeln('<error>Model file already exist.</error>');
            return;
        }

        $namespace = str_replace('_', '\\', $module);

        $interfaceName = $namespace . '\Api\Data\\' . $path . 'Interface';
        $pos = strrpos($interfaceName, '\\');
        $interfaceShortName = substr($interfaceName, $pos + 1);
        $interface = new InterfaceGenerator($interfaceName);
        $interface->setNamespaceName(substr($interfaceName, 0, $pos))
            ->setDocBlock(new DocBlockGenerator('@api'));

        $modelName = $namespace . '\Model\\' . $path;
        $model = new ClassGenerator($modelName);
        $model->setExtendedClass(DataObject::class)
            ->setNamespaceName(substr($modelName, 0, strrpos($modelName, '\\')))
            ->setImplementedInterfaces([$interfaceName])
            ->addUse(DataObject::class)
            ->addUse($interfaceName);

        foreach ($fields as $field) {
            $tmp = str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
            $param = strtolower(substr($tmp, 0, 1)) . substr($tmp, 1);

            $interface->addMethod(
                'get' . $tmp,
                [],
                MethodGenerator::FLAG_PUBLIC,
                null,
                new DocBlockGenerator('Get ' . $field, null, [
                    ['name' => 'return', 'content' => 'string']
                ])
            );

            $interface->addMethod(
                'set' . $tmp,
                [$param],
                MethodGenerator::FLAG_PUBLIC,
                null,
                new DocBlockGenerator('Set ' . $field, null, [
                    ['name' => 'param', 'content' => 'string $' . $param],
                    ['name' => 'return', 'content' => $interfaceShortName]
                ])
            );

            $model->addMethod(
                'get' . $tmp,
                [],
                MethodGenerator::FLAG_PUBLIC,
                'return $this->getDataByKey(\'' . $field . '\');',
                '@inheritDoc'
            );

            $model->addMethod(
                'set' . $tmp,
                [$param],
                MethodGenerator::FLAG_PUBLIC,
                'return $this->setData(\'' . $field . '\', $' . $param . ');',
                '@inheritDoc'
            );
        }

        $dirInterface = $this->driver->getParentDirectory($fileInterface);
        if (!$this->driver->isDirectory($dirInterface)) {
            $this->driver->createDirectory($dirInterface);
        }
        (new FileGenerator())->setClass($interface)->setFilename($fileInterface)->write();

        $dirModel = $this->driver->getParentDirectory($fileModel);
        if (!$this->driver->isDirectory($dirModel)) {
            $this->driver->createDirectory($dirModel);
        }
        (new FileGenerator())->setClass($model)->setFilename($fileModel)->write();

        $output->writeln('<info>Interface and model files created:</info>');
        $output->writeln($fileInterface);
        $output->writeln($fileModel);
        $output->writeln('');
    }
}
