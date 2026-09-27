<?php

namespace Botble\Base\Commands;

use Botble\Media\Facades\RvMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Intervention\Image\Encoders\AutoEncoder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

#[AsCommand('cms:images:compress', 'Compress an image file or scan and compress all images in a folder recursively')]
class CompressImagesCommand extends Command
{
    public function handle(): void
    {
        $path = $this->argument('folder');

        if (! File::exists($path)) {
            $this->error("The path '{$path}' does not exist.");

            return;
        }

        $images = $this->getImages($path);

        if (empty($images)) {
            $this->info("No images found in '{$path}'.");

            return;
        }

        foreach ($images as $image) {
            $this->compressImage($image);
        }

        $this->components->info('✅ Image compression completed!');
    }

    protected function getImages(string $path): array
    {
        if (File::isFile($path)) {
            $this->info("Processing file '{$path}'...");

            return [new SplFileInfo($path, dirname($path), $path)];
        }

        $this->components->info("Scanning '{$path}' for images...");

        return File::allFiles($path, true);
    }

    protected function compressImage(SplFileInfo $file): void
    {
        $path = $file->getPathname();
        $extension = strtolower($file->getExtension());

        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
            return;
        }

        try {

            $this->info("Compressing... {$file->getFilename()}");

            $encoder = new AutoEncoder();

            $image = RvMedia::imageManager()->read($path);

            if ($this->option('width') || $this->option('height')) {
                $image->scaleDown($this->option('width'), $this->option('height'));
            }

            $image->encode($encoder)->save($path);

            $this->info("✔ Compressed: {$file->getFilename()}");
        } catch (Throwable $e) {
            $this->error("❌ Failed to compress: {$file->getFilename()} - " . $e->getMessage());
        }
    }

    protected function configure(): void
    {
        $this->addArgument('folder', InputArgument::REQUIRED, 'The folder or file path which you want to compress');
        $this->addOption('width', null, InputArgument::OPTIONAL, 'The width of the compressed image', 1000);
        $this->addOption('height', null, InputArgument::OPTIONAL, 'The height of the compressed image', 1000);
    }
}
