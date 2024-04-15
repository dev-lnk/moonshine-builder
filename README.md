![logo](https://github.com/moonshine-software/moonshine/raw/2.x/art/lego.png)

## Creating projects using schemas for the [MoonShine](https://github.com/moonshine-software/moonshine).

#### Hello, Laravel and MoonShine User!

This package allows you to describe the entire project structure using a JSON schema and generate necessary files such as:
<ul>
    <li>Models</li>
    <li>Migrations</li>
    <li>Resources</li>
</ul>

### Installation:
```shell
composer require dev-lnk/moonshine-builder
```
### Configuration:
Publish the package configuration file:
```shell
php artisan vendor:publish --tag=moonshine-builder
```
n the configuration file, specify the path to your JSON schemas:
```php
return [
    'builds_dir' => base_path('builds')
];
```
### Creating a Schema
In the <code>builds_dir</code> directory, create a schema file, for example, <code>category.json</code>:
```json
{
  "resources": [
    {
      "CategoryResource": {
        "fields": {
          "id": {
            "type": "id",
            "methods": [
              "sortable"
            ]
          },
          "title": {
            "type": "string",
            "name": "Название"
          }
        }
      }
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
You can create a resource using a table schema.You must specify the table name and <code>.table</code> extension. Example:
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
            Date::make('CreatedAt', 'created_at'),
            Date::make('UpdatedAt', 'updated_at'),
        ]),
    ];
}
```

After generating the files, make sure to register all new Resources in your <code>MoonShineServiceProvider</code>

### Be careful, at the moment all resource and model files are overwritten during generation!
