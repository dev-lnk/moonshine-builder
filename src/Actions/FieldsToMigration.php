<?php

declare(strict_types=1);

namespace MoonShine\ProjectBuilder\Actions;

use MoonShine\ProjectBuilder\Traits\Makeable;

final class FieldsToMigration
{
    use Makeable;

    public function handle(array $fields): string
    {
        $result = "";

        foreach ($fields as $key => $type) {
            if(is_string($type)) {
                $result .= str('$table->')
                    ->when($key === 'id' && $type === 'id',
                        fn($str) => $str->append($type."()"),
                        fn($str) => $str->append($type."('$key')")
                    )
                    ->append(';')
                    ->newLine()
                    ->append('    ')
                    ->append('    ')
                    ->append('    ')
                    ->value()
                ;
                continue;
            }

            $column = str('$table->')
                ->when($key === 'id' && $type['type'] === 'id',
                    fn($str) => $str->append($type['type']."()"),
                    fn($str) => $str->append($type['type']."('$key')")
                );

            if(! empty($type['migration'])) {
                foreach ($type['migration'] as $migrationOption) {
                    $column = $column->append("->$migrationOption");
                }
            }

            $column = $column
                ->append(';')
                ->newLine()
                ->append('    ')
                ->append('    ')
                ->append('    ')
            ;

            $result .= $column->value();
        }

        return $result;
    }
}