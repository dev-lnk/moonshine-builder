![logo](https://github.com/moonshine-software/moonshine/raw/2.x/art/lego.png)

## Создание проектов по схеме для админ-панели [MoonShine](https://github.com/moonshine-software/moonshine).

#### Привет пользователь Laravel и MoonShine!

Данный пакет позволяет с помощью json схемы описать всю структуру проекта и сгенерировать необходимые файлы, такие как:
<ul>
    <li>Модели</li>
    <li>Миграции</li>
    <li>Ресурсы</li>
</ul>

### Установка:
```php
composer require moonshine/moonshine-builder
```
### Настройка:
Опубликуйте файл конфигурации пакета:
```php
php artisan vendor:publish --tag=moonshine-builder
```
В файле конфигурации укажите путь до ваших json схем:
```php
return [
    'builds_dir' => base_path('builds')
];
```
### Создание схемы
В директории builds_dir создайте файл со схемой, например category.json:
```
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
Для создания файлов проекта выполните команду:
```php
 php artisan moonshine:build <filename>
```
Где filename - это созданный файл, без расширения json, например
```php
 php artisan moonshine:build category
```
Более детальный пример файла с несколькими ресурсами и отношениями находится здесь.

После генерации файлов, необходимо зарегистрировать все новые ресурсы в вашем MoonShineServiceProvider