<?php

namespace DevLnk\MoonShineBuilder\Commands;

use DevLnk\MoonShineBuilder\Exceptions\CodeGenerateCommandException;
use DevLnk\MoonShineBuilder\Exceptions\NotFoundBuilderException;
use DevLnk\MoonShineBuilder\Exceptions\ProjectBuilderException;
use DevLnk\MoonShineBuilder\Services\CodeStructure\Factories\StructureFromJson;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use MoonShine\Laravel\Commands\MoonShineCommand;
use DevLnk\MoonShineBuilder\Services\CodeGenerator;
use SplFileInfo;
use function Laravel\Prompts\{select};

class JsonBuildCommand extends MoonShineCommand
{
    protected $signature = 'moonshine:build-json {target?}';

    /**
     * @throws CodeGenerateCommandException
     * @throws ProjectBuilderException
     * @throws NotFoundBuilderException
     * @throws FileNotFoundException
     */
    public function handle(CodeGenerator $codeGenerator): int
    {
        $codeGenerator->setCommand($this);

        $target = $this->argument('target') ?? $this->getFileList('json');

        $codeStructures = StructureFromJson::make($this->getPath($target))
            ->makeStructures()
            ->codeStructures();

        foreach ($codeStructures as $codeStructure) {
            $codeGenerator->make($codeStructure);
        }

        $codeGenerator->resourceInfo();

        $this->components->info('All done');

        return self::SUCCESS;
    }

    protected function getFileList(string $extension): int|string
    {
        /** @var Collection<array-key, string> $files */
        $files = collect(File::files(config('moonshine_builder.builds_dir')))->mapWithKeys(
            static function (SplFileInfo $file) use ($extension): array {
                if(! str_contains($file->getFilename(), '.' . $extension)) {
                    return [];
                }
                return [
                    $file->getFilename() => $file->getFilename(),
                ];
            }
        );

        return select(
            'File',
            $files,
        );
    }

    /**
     * @throws ProjectBuilderException
     */
    private function getPath(string $target): string
    {
        $result = config('moonshine_builder.builds_dir') . '/' . $target;
        if(! file_exists($result)) {
            throw new ProjectBuilderException("File $result not found");
        }
        return $result;
    }
}
