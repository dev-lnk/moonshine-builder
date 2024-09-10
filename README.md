![logo](https://github.com/moonshine-software/moonshine/raw/2.x/art/lego.png)

# Generate code using JSON schema, SQL tables, and console commands for [MoonShine](https://github.com/moonshine-software/moonshine).

[![Latest Stable Version](https://img.shields.io/packagist/v/dev-lnk/moonshine-builder)](https://packagist.org/packages/dev-lnk/moonshine-builder)
[![tests](https://raw.githubusercontent.com/dev-lnk/moonshine-builder/0c267c4601af644378e1d50acc4aa4ce6bac79d6/.github/tests/badge.svg)](https://github.com/dev-lnk/moonshine-builder/actions)
[![License](https://img.shields.io/packagist/l/dev-lnk/moonshine-builder)](https://packagist.org/packages/dev-lnk/moonshine-builder)

[![Laravel required](https://img.shields.io/badge/Laravel-10+-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP required](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php)](https://www.php.net/manual/)
[![MoonShine required](https://img.shields.io/badge/Moonshine-2.10+-1B253B?style=for-the-badge)](https://github.com/moonshine-software/moonshine)

## Table of contents
- [Description](#description)
- [Installation](#installation)
    - [Configuration](#configuration)
- [Generate code using JSON or MYSQL](#generate-code-using-json-or-mysql)
- [Creating a JSON schema](#creating-a-json-schema)
    - [Timestamps](#timestamps)
    - [Soft deletes](#soft-deletes)
    - [Flags for generating files](#flags-for-generating-files)
- [Creation from SQL table](#creation-from-sql-table)
    - [Bulk table import](#bulk-table-import)
- [Generating code using console command](#generating-code-using-console-command)
  - [Specifying fields in the command](#specifying-fields-in-the-command)
  - [List of field types](#list-of-field-types)
    

## Description
Hello, Laravel and MoonShine User!

This package allows you to describe the entire project structure using a [JSON](https://github.com/dev-lnk/moonshine-builder/blob/master/json_schema.json) table schema, `SQL` table, console command and generate the necessary files such as:

 - [Resource](https://github.com/dev-lnk/moonshine-builder/blob/master/.github/entities/resource.md)
 - [Model](https://github.com/dev-lnk/moonshine-builder/blob/master/.github/entities/model.md)
 - [Migration](https://github.com/dev-lnk/moonshine-builder/blob/master/.github/entities/migration.md)


## Installation:
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

## Generate code using JSON or MYSQL
Now you can run the command:

```shell
php artisan moonshine:build
```
You will be given options as to which scheme to use when generating the code, for example:

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
## Creating a JSON schema
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
A more detailed example with multiple resources and relationships can be found [here](https://github.com/dev-lnk/moonshine-builder/blob/master/examples/project.json). For hints in your IDE or for a more detailed description of the JSON structure, you can use this [file](https://github.com/dev-lnk/moonshine-builder/blob/master/json_schema.json).

### Timestamps
You can specify the `timestamps: true` flag
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
The `created_at` and `updated_at` fields will be added to your code. If you manually specified the `created_at` and `updated_at` fields, the `timestamps` flag will be automatically set to true.

### Soft deletes
Works similarly to the `timestamps` flag and the `deleted_at` field.

### Flags for generating files
Using flags `withResource`, `withModel`, `withMigration`, you can configure what exactly you want to generate for your resource:
```json
{
  "name": "ItemPropertyPivot",
  "withResource": false,
  "withModel": false
}
```

## Creation from SQL table
You can create a resource using a table schema. You must specify the table name and select <code>table</code> type. Example:
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

After generating the files, make sure to register all new Resources in your <code>MoonShineServiceProvider</code>.

#### Bulk table import
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

## Generating code using console command
To generate the resource, model, and migration, run the command:
```shell
php artisan ms-build:resource Post
```
Specify all required fields:
```shell
 ┌ Column: ─────────────────────────────────────────────────────┐
 │ id                                                           │
 └──────────────────────────────────────────────────────────────┘

 ┌ Column name: ────────────────────────────────────────────────┐
 │ Id                                                           │
 └──────────────────────────────────────────────────────────────┘

 ┌ Column type: ────────────────────────────────────────────────┐
 │ id                                                           │
 ├──────────────────────────────────────────────────────────────┤
 │ › id                                                       ┃ │
 │   string                                                   │ │
 │   text                                                     │ │
 │   boolean                                                  │ │
 │   bigInteger                                               │ │
 │   BelongsTo                                                │ │
 │   HasMany                                                  │ │
 │   HasOne                                                   │ │
 │   BelongsToMany                                            │ │
 └──────────────────────────────────────────────────────────────┘
 ┌ Add more fields? ────────────────────────────────────────────┐
 │ ● Yes / ○ No                                                 │
 └──────────────────────────────────────────────────────────────┘
```
Once all fields have been specified, generate the code
```shell
 ┌ Add timestamps? ─────────────────────────────────────────────┐
 │ Yes                                                          │
 └──────────────────────────────────────────────────────────────┘

 ┌ Add softDelete? ─────────────────────────────────────────────┐
 │ No                                                           │
 └──────────────────────────────────────────────────────────────┘

 ┌ Make migration? ─────────────────────────────────────────────┐
 │ Yes                                                          │
 └──────────────────────────────────────────────────────────────┘
 app/Models/Post.php was created successfully!
 app/MoonShine/Resources/PostResource.php was created successfully!
 migrations/2024_09_10_111121_create_posts.php was created successfully!
```
### Specifying fields in the command
You can also specify all fields at once in the command, ignoring the interactive mode:
```shell
php artisan ms-build:resource Post id:ID:id name:string status_id:Status:BelongsTo:statuses
```
The structure of the value has the following scheme: `<column>:<column name>:<type>`

If the field type is relation, the structure is as follows: `<column>(name of the relationship method in the model):<column name>:<type>:<table>`

### List of field types
The list of field types can be retrieved using the command:
```shell
php artisan ms-build:types
```
