<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Commands;

use DevLnk\MoonShineBuilder\Enums\ParseType;
use Illuminate\Console\Command;

use function Laravel\Prompts\{select};

class MoonShineBuildCommand extends Command
{
    protected $signature = 'moonshine:build {target?} {--type=}';

    public function handle(): int
    {
        $target = $this->argument('target');

        $parseType = ParseType::from($this->getType($target));

        $command = $parseType->getCommandName();

        $arguments = [];
        if($target !== null) {
            $arguments = ['target' => $target];
        }

        $this->call($command, $arguments);

        return self::SUCCESS;
    }

    protected function getType(?string $target): string
    {
        if($this->option('type') !== null) {
            return $this->option('type');
        }

        if ($this->option('type') === null && ! is_null($target)) {
            $availableTypes = [
                ParseType::JSON->value,
            ];

            $fileSeparate = explode('.', $target);
            $type = $fileSeparate[count($fileSeparate) - 1];

            if (in_array($type, $availableTypes)) {
                return $type;
            }
        }

        $typeList = [];
        foreach (ParseType::cases() as $parseType) {
            $typeList[$parseType->value] = $parseType->toString();
        }

        return select(
            'Type',
            $typeList
        );
    }
}
