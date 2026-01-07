![logo](https://github.com/moonshine-software/moonshine/raw/2.x/art/lego.png)

## Creating projects using schemas for the [MoonShine](https://github.com/moonshine-software/moonshine).

[![Latest Stable Version](https://img.shields.io/packagist/v/dev-lnk/moonshine-builder)](https://packagist.org/packages/dev-lnk/moonshine-builder)
[![Total Downloads](https://img.shields.io/packagist/dt/dev-lnk/moonshine-builder)](https://packagist.org/packages/dev-lnk/moonshine-builder)
[![tests](https://raw.githubusercontent.com/dev-lnk/moonshine-builder/0c267c4601af644378e1d50acc4aa4ce6bac79d6/.github/tests/badge.svg)](https://github.com/dev-lnk/moonshine-builder/actions)
[![License](https://img.shields.io/packagist/l/dev-lnk/moonshine-builder)](https://packagist.org/packages/dev-lnk/moonshine-builder)\
[![Laravel required](https://img.shields.io/badge/Laravel-10+-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP required](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php)](https://www.php.net/manual/)
[![MoonShine required](https://img.shields.io/badge/Moonshine-4.0+-1B253B?style=for-the-badge)](https://github.com/moonshine-software/moonshine)

- [Description](#about)
- [Installation](#install)
- [Configuration](#config)
- [Quick start](#start)
- [Code generation methods](#code-generate)
    - [Creation from SQL table](#sql)
    - [Creation from JSON schema](#json)
        - [Timestamps](#timestamps)
        - [Soft delete](#soft-delete)
        - [Flags for generating files](#flags)
    - [Console generation](#console)
    - [Generation from existing model](#model)
- [Bulk table import](#mass-sql)
- [Use in other projects](#cases)

---

<a name="about"></a>
## Description

This package allows you to create Resource, Model and Migration with all fields using generation methods from:

 - [SQL table](#sql),
 - [JSON schema](#json),
 - [Code generation for a new resource from the console](#console),
 - [Existing Laravel model](#model).

The package generates the following files:

 - [Resource](https://github.com/dev-lnk/moonshine-builder/blob/3.x/.github/entities/resource.md)
 - [IndexPage](https://github.com/dev-lnk/moonshine-builder/blob/3.x/.github/entities/index-page.md)
 - [FormPage](https://github.com/dev-lnk/moonshine-builder/blob/3.x/.github/entities/form-page.md)
 - [DetailPage](https://github.com/dev-lnk/moonshine-builder/blob/3.x/.github/entities/detail-page.md)
 - [Model](https://github.com/dev-lnk/moonshine-builder/blob/3.x/.github/entities/model.md)
 - [Migration](https://github.com/dev-lnk/moonshine-builder/blob/3.x/.github/entities/migration.md)



<a name="install"></a>
## Installation

```shell
composer require dev-lnk/moonshine-builder --dev
```

<a name="config"></a>
## Configuration
Publish the package configuration file:
```shell
php artisan vendor:publish --tag=moonshine-builder
```
In the configuration file, specify the path to your JSON schemas:

```php
return [
    // Directory where schematic files in json, yaml, etc. are stored.
    'builds_dir' => base_path('builds'),

    // Base path for models directory.
    'base_model_path' => 'app/Models',

    // Notification of duplicate files of models and resources with a new generation.
    'is_confirm_replace_files' => true,

    // Ask about adding a new resource to the provider.
    'is_confirm_change_provider' => false,

    // Ask about adding a new resource to the menu.
    'is_confirm_change_menu' => false,
];
```

<a name="start"></a>
## Quick start
Run the command:

```
php artisan moonshine:build
```
You will be given options as to which scheme to use when generating the code, for example:

```shell
 ┌ Type ────────────────────────────────────────────────────────┐
 │   ○ table                                                    │
 │ › ● json                                                     │
 │   ○ console                                                  │
 │   ○ model                                                    │
 └──────────────────────────────────────────────────────────────┘
```
When selecting the `json` option:
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

INFO  All done.
```

The command has the following signature `moonshine:build {target?} {--type=}`, where:
 - `target` - entity for which generation will be performed,
 - `type` - type or generation method, available `table`, `json`, `console`, `model`.

Individual commands are also available for each generation type:
 - `php artisan moonshine:build-json {target?}` - generation from JSON schema
 - `php artisan moonshine:build-table {target?}` - generation from SQL table
 - `php artisan moonshine:build-resource {entity?} {fields?*}` - console generation
 - `php artisan moonshine:build-model {entity?} {--all}` - generation from existing model

<a name="code-generate"></a>
## Code generation methods

<a name="sql"></a>
### Creation from SQL table

You can create a resource using a table schema. To do this, run the command `php artisan moonshine:build` and select the `table` option:
```shell
 ┌ Type ────────────────────────────────────────────────────────┐
 │ › ● table                                                    │
 │   ○ json                                                     │
 │   ○ console                                                  │
 └──────────────────────────────────────────────────────────────┘
```

Select the required table:
```shell
 ┌ Table ───────────────────────────────────────────────────────┐
 │   ○ password_reset_tokens                                  │ │
 │   ○ sessions                                               │ │
 │   ○ statuses                                               │ │
 │   ○ tasks                                                  │ │
 │ › ● users                                                  ┃ │
 └──────────────────────────────────────────────────────────────┘
```

You can immediately specify the table name and generation type. Example:
```shell
php artisan moonshine:build users --type=table
```

Or use a specialized command:
```shell
php artisan moonshine:build-table users
```

Result:
```php
/**
 * @return list<ComponentContract|FieldContract>
 */
protected function fields(): iterable
{
    return [
        ID::make('id'),
        Text::make('name', 'name'),
        Text::make('email', 'email'),
        Date::make('email_verified_at', 'email_verified_at'),
        Text::make('password', 'password'),
        Text::make('remember_token', 'remember_token'),
    ];
}
```

<a name="json"></a>
### Creation from JSON schema

JSON structure [JSON](https://github.com/dev-lnk/moonshine-builder/blob/master/json_schema.json). In the `builds_dir` directory, create a schema file, for example, `category.json`:
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

Or use a specialized command:
```shell
php artisan moonshine:build-json category.json
```

A more detailed example with multiple resources and relationships can be found [here](https://github.com/dev-lnk/moonshine-builder/blob/master/examples/project.json).

<a name="timestamps"></a>
#### Timestamps

You can specify the `timestamps: true` flag:
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
The `created_at` and `updated_at` fields will be added to the generated code. If you manually specify the `created_at` and `updated_at` fields, the `timestamps` flag will be automatically set to `true`.

<a name="soft-delete"></a>
#### Soft delete

Works similarly to the `timestamps` flag and the `deleted_at` field.

<a name="flags"></a>
#### Flags for generating files

Using the `withResource`, `withModel`, `withMigration` flags, you can configure what exactly needs to be generated for your resource:
```json
{
  "name": "ItemPropertyPivot",
  "withResource": false,
  "withModel": false
}
```

<a name="console"></a>
### Console generation
Run the command `php artisan moonshine:build` and select the `console` option, or run the `moonshine:build-resource` command. Next, you need to specify the resource name and describe all fields:

```shell
 ┌ Type ────────────────────────────────────────────────────────┐
 │ console                                                      │
 └──────────────────────────────────────────────────────────────┘

 ┌ Resource name: ──────────────────────────────────────────────┐
 │ Status                                                       │
 └──────────────────────────────────────────────────────────────┘

 ┌ Column: ─────────────────────────────────────────────────────┐
 │ id                                                           │
 └──────────────────────────────────────────────────────────────┘

 ┌ Column name: ────────────────────────────────────────────────┐
 │ Id                                                           │
 └──────────────────────────────────────────────────────────────┘

 ┌ Column type: ────────────────────────────────────────────────┐
 │ id                                                           │
 └──────────────────────────────────────────────────────────────┘

 ┌ Add more fields? ────────────────────────────────────────────┐
 │ ● Yes / ○ No                                                 │
 └──────────────────────────────────────────────────────────────┘
```

You can immediately create a resource with fields by running the following command:
```shell
php artisan moonshine:build-resource Status id:Id:id name:Name:string
```

Result:
```php
/**
 * @return list<ComponentContract|FieldContract>
 */
protected function fields(): iterable
{
    return [
        ID::make('id'),
        Text::make('Name', 'name'),
    ];
}
```

Command signature `moonshine:build-resource {entity?} {fields?*}`, where:
 - entity - resource name,
 - fields - fields for generation like name:Name:string or {column}:{columnName}:{type}

All available {type} can be viewed by running the command `php artisan moonshine:build-types`

<a name="model"></a>

### Generation from existing model

If you already have a ready-made Laravel model with defined fields, relationships, and settings, you can generate a MoonShine Resource based on that model. Run the command `php artisan moonshine:build` and select the `model` option:

```shell
 ┌ Type ────────────────────────────────────────────────────────┐
 │   ○ table                                                    │
 │   ○ json                                                     │
 │   ○ console                                                  │
 │ › ● model                                                    │
 └──────────────────────────────────────────────────────────────┘
```

Then select the required model from the list of available models:

```shell
 ┌ Select models (use Space to select, Enter to confirm): ──────┐
 │   ◻ app/Models/Category.php                                │ │
 │   ◻ app/Models/Comment.php                                 │ │
 │   ◻ app/Models/Product.php                                 │ │
 │   ◻ app/Models/Rating.php                                  │ │
 │   ◻ app/Models/Review.php                                  │ │
 │   ◻ app/Models/Tag.php                                     │ │
 └────────────────────────────────────────────────── 0 selected ┘
```

You can also directly specify the model for generation:

```shell
php artisan moonshine:build-model Product
```

or with the full class name:

```shell
php artisan moonshine:build-model "App\Models\Product"
```

**Generating resources for all models**

If you need to create resources for all models in the directory, use the `--all` flag:

```shell
php artisan moonshine:build-model --all
```

This command will automatically scan the models directory and create resources for each model found.

The package will automatically analyze the model and create:
- Resource with fields based on the table structure
- Relationships (HasMany, BelongsTo, BelongsToMany, HasOne) based on model methods
- Correct field types based on column types in the database
- Timestamps and soft deletes settings if they are used in the model

**Configuring the models directory**

By default, the package looks for models in the `app/Models` directory. You can change this in the configuration file:

```php
'base_model_path' => 'app/Models',
```

<a name="mass-sql"></a>
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
A JSON schema will be created, which you can edit and use if desired:
```
project_20240613113014.json was created successfully! To generate resources, run: 
php artisan moonshine:build project_20240613113014.json
```

<a name="cases"></a>
### Use in other projects
- [MoonVibe](https://github.com/moonshine-software/moon-vibe) - admin panel generation using AI