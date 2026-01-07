<?php

namespace DevLnk\MoonShineBuilder\Commands;

use DevLnk\MoonShineBuilder\Exceptions\ProjectBuilderException;
use DevLnk\MoonShineBuilder\Services\CodeStructure\Factories\StructureFromJson;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use SplFileInfo;
use function Laravel\Prompts\{select};

class JsonBuildCommand extends AbstractBuildCommand
{
    protected $signature = 'moonshine:build-json {target?}';

    public function handle(): int
    {
        $this->init();

        $target = $this->argument('target') ?? $this->getFileList('json');

        $codeStructures = StructureFromJson::make($this->getPath($target))
            ->makeStructures()
            ->codeStructures();

        foreach ($codeStructures as $codeStructure) {
            $this->make($codeStructure, $this->generationPath);
        }

        $this->resourceInfo();

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
