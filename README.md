![logo](https://github.com/moonshine-software/moonshine/raw/2.x/art/lego.png)

## Creating projects using schemas for the [MoonShine](https://github.com/moonshine-software/moonshine).

[![Latest Stable Version](https://img.shields.io/packagist/v/dev-lnk/moonshine-builder)](https://packagist.org/packages/dev-lnk/moonshine-builder)
[![Total Downloads](https://img.shields.io/packagist/dt/dev-lnk/moonshine-builder)](https://packagist.org/packages/dev-lnk/moonshine-builder)
[![tests](https://raw.githubusercontent.com/dev-lnk/moonshine-builder/0c267c4601af644378e1d50acc4aa4ce6bac79d6/.github/tests/badge.svg)](https://github.com/dev-lnk/moonshine-builder/actions)
[![License](https://img.shields.io/packagist/l/dev-lnk/moonshine-builder)](https://packagist.org/packages/dev-lnk/moonshine-builder)\
[![Laravel required](https://img.shields.io/badge/Laravel-10+-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP required](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php)](https://www.php.net/manual/)
[![MoonShine required](https://img.shields.io/badge/Moonshine-2.10+-1B253B?style=for-the-badge)](https://github.com/moonshine-software/moonshine)

#### Hello, Laravel and MoonShine User!

This package allows you to describe the entire project structure using a [JSON](https://github.com/dev-lnk/moonshine-builder/blob/master/json_schema.json) or `SQL` table schema and generate the necessary files, such as:

 - [Resource](https://github.com/dev-lnk/moonshine-builder/blob/master/.github/entities/resource.md)
 - [Model](https://github.com/dev-lnk/moonshine-builder/blob/master/.github/entities/model.md)
 - [Migration](https://github.com/dev-lnk/moonshine-builder/blob/master/.github/entities/migration.md)


### Installation:
```shell
composer require dev-lnk/moonshine-builder --dev
```
### Configuration:
Publish the package configuration file:
```shell
php artisan vendor:publish --tag=moonshine-builder
```
In the configuration file, specify the path to your JSON schemas:

```php
return [
    'builds_dir' => base_path('builds')
];
```

Now you can run the command:

```shell
php artisan moonshine:build
```
You will be given options as to which scheme to use when generating the code, form example:

```shell
 ┌ Type ────────────────────────────────────────────────────────┐
 │ › ● json                                                     │
 │   ○ table                                                    │
 └──────────────────────────────────────────────────────────────┘
```
```shell
 ┌ File ────────────────────────────────────────────────────────┐
 │ › ● category.json                                            │
 │   ○ project.json                                             │
 └──────────────────────────────────────────────────────────────┘
```
```shell
app/Models/Category.php was created successfully!
app/MoonShine/Resources/CategoryResource.php was created successfully!
database/migrations/2024_05_27_140239_create_categories.php was created successfully!

WARN  Don't forget to register new resources in the provider method:

 new CategoryResource(),

 ...or in the menu method:

 MenuItem::make(
     static fn() => 'CategoryResource',
     new CategoryResourceResource()
 ),

INFO  All done.

```
### Creating a Schema
In the <code>builds_dir</code> directory, create a schema file, for example, <code>category.json</code>:
```json
{
  "resources": [
    {
      "name": "Category",
      "fields": [
        {
          "column": "id",
          "type": "id",
          "methods": [
            "sortable"
          ]
        },
        {
          "column": "name",
          "type": "string",
          "name": "Name"
        }
      ]
    }
  ]
}
```
To generate project files, run the command:
```shell
 php artisan moonshine:build category.json
```
A more detailed example with multiple resources and relationships can be found [here](https://github.com/dev-lnk/moonshine-builder/blob/master/examples/project.json).
### Creation from sql table
You can create a resource using a table schema.You must specify the table name and select <code>table</code> type. Example:
```shell
php artisan moonshine:build users --type=table
```
Result:
```php
public function fields(): array
{
    return [
        Block::make([
            ID::make('id'),
            Text::make('Name', 'name'),
            Text::make('Email', 'email'),
            Date::make('EmailVerifiedAt', 'email_verified_at'),
            Text::make('Password', 'password'),
            Text::make('RememberToken', 'remember_token'),
        ]),
    ];
}
```

After generating the files, make sure to register all new Resources in your <code>MoonShineServiceProvider</code>

### Timestamps
You can specify the timestamp: true flag
```json
{
  "resources": [
    {
      "name": "Category",
      "timestamps": true,
      "fields": []
    }
  ]
}
```
The created_at and updated_at fields will be added to your code. If you manually specified the created_at and updated_at fields, the `timestamps` flag will be automatically set to true

### Soft deletes
Works similarly to the `timestamps` flag and the `deleted_at` field

### Flags for generating files
Using flags `withResource`, `withModel`, `withMigration`, you can configure what exactly you want to generate for your resource
```json
{
  "name": "ItemPropertyPivot",
  "withResource": false,
  "withModel": false
}
```
### JSON Schema
For hints in your IDE or for a more detailed description of the json structure, you can use this [file](https://github.com/dev-lnk/moonshine-builder/blob/master/json_schema.json)

### Bulk table import
If you already have a project with its own database and you don't want to build the resources one by one, you can use the following command:
```shell
php artisan moonshine:project-schema
```
First, select all your Pivot tables to correctly form the BelongsToMany relationship, then select all the necessary tables for which you want to generate resources.
```shell
 ┌ Select the pivot table to correctly generate BelongsToMany (Press enter to skip) ┐
 │ item_property                                                                    │
 └──────────────────────────────────────────────────────────────────────────────────┘

 ┌ Select tables ───────────────────────────────────────────────┐
 │ categories                                                   │
 │ comments                                                     │
 │ items                                                        │
 │ products                                                     │
 │ properties                                                   │
 │ users                                                        │
 └──────────────────────────────────────────────────────────────┘
```
A JSON schema will be generated, which you can edit and apply if desired.
```shell
project_20240613113014.json was created successfully! To generate resources, run: 
php artisan moonshine:build project_20240613113014.json
```