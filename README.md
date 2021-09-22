# Module builder for Magento 2

A Magento 2 module which used to build new modules

## Install

1. Execute this command to download the `CrazyCat_Base` module through composer:<br>
   `composer require CrazyCat_Base`

2. Download and decompress this packet into this folder `[root]/app/code/CrazyCat`.

3. Execute these commands to enable the modules:<br>
   `php bin/magento module:enable CrazyCat_Base`<br>
   `php bin/magento module:enable CrazyCat_ModuleBuilder`

4. Execute these commands to recompile and flush cache:<br>
   `php bin/magento setup:di:compile`<br>
   `php bin/magento cache:flush`

5. Execute this command to check whether the module is installed:<br>
   `php bin/magento`

Three console commands are added:

|Command|Description|
|---|---|
|`module-builder:create-module`|Create a new module|
|`module-builder:create-model`|Create a new model|
|`module-builder:create-adminhtml-ui`|Create list and edit pages of admin panel|

## How to use

### Create a new module

```sh
php bin/magento module-builder:create-module [options] <module-name> <package-name>
```

This command is used to create a new module with these files:

- etc/module.xml
- composer.json
- registration.php

Options list below:

|Option|Short|Description|
|---|---|---|
|`--author`|`-a`|Author to show in copyright, composer.json etc. [default: "Anonymous"]|
|`--package-description`|`-p`|Package description [default: "A Magento 2 module."]|
|`--package-version`|`-e`|Package version [default: "1.0.0"]|
|`--license`|`-l`|Package license|

Arguments list below:

|Argument|Description|
|---|---|
|`<module-name>`|Module name, format is like Vendor_Module, uppercase every piece, case sensitive|
|`<package-name>`|Name of composer package|

### Craete a new model

```sh
php bin/magento module-builder:create-model <model-path> <main-table> [<module-name>]
```

This command is used to create related files of model:

- etc/db_schema.xml
- model
- resource model
- collection

Arguments list below:

|Argument|Description|
|---|---|
|`<main-table>`|Main table of the model|
|`<model-path>`|Model path related to the Model folder, use backslash as separator.<br>For example, input `Menu\Item` for the Vendor_Module module is going to create `\Vendor\Module\Model\Menu\Item` class.|
|`<module-name>`|Module name, format is like Vendor_Module, uppercase every piece, case sensitive.<br>This is optional, the last module name used with create-module command is as default.|

### Create list and edit pages of admin panel

```sh
php bin/magento module-builder:create-adminhtml-ui [options] <controller-path> <model-path> [<module-name>]
```

This command is used to create files related to backend listing and edit pages:

- Controller files of these actions: index, new, edit, save, massSave, delete
- Layout files of these actions: index, new, edit
- UI component files of listing and edit form
- Data provider models used by the UI components
- Modification of di.xml for appending the data provider model

Options list below:

|Option|Short|Description|
|---|---|---|
|`--route-name`|`-r`|Route name|

Arguments list below:

|Argument|Description|
|---|---|
|`<controller-path>`|Controller path related to the Controller folder, use backslash as separator.<br>For example, input `Menu\Item` for the Vendor_Module module is going to create files under `\Vendor\Module\Controller\Menu\Item` namespace.|
|`<model-path>`|Model path related to the Model folder, use backslash as separator|
|`<module-name>`|Module name, format is like Vendor_Module, uppercase every piece, case sensitive.<br>This is optional, the last module name used with create-module command is as default.|

### Create Data API

```sh
php bin/magento module-builder:create-api <module-name> <path> <fields>
```

This command is used to create files related to a data API which has many get/set methods:

- An API interface file under `[module_root]/Api/Data`
- A file of model which implements the API interface

Arguments list below:

|Argument|Description|
|---|---|
|`<module-name>`|Module name, format is like Vendor_Module, uppercase every piece, case sensitive.|
|`<path>`|Path to the `[module_root]/Api/Data`|
|`<fields>`|Field names, separated by comma|
