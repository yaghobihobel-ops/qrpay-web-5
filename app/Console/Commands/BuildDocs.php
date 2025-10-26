<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BuildDocs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:build';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate the presence of internal documentation and relative links.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $docsRoot = resource_path('docs');
        $requiredFiles = [
            'system-guide.md',
            'guides/contracts.md',
            'guides/service-architecture.md',
            'guides/monitoring.md',
        ];

        $hasError = false;

        foreach ($requiredFiles as $relativePath) {
            $fullPath = $docsRoot . DIRECTORY_SEPARATOR . $relativePath;
            if (! File::exists($fullPath)) {
                $this->error("Missing documentation file: {$relativePath}");
                $hasError = true;
            }
        }

        if ($hasError) {
            return self::FAILURE;
        }

        $guidePath = $docsRoot . DIRECTORY_SEPARATOR . 'system-guide.md';
        $markdown = File::get($guidePath);

        $requiredAnchors = [
            'module-payment',
            'module-exchange',
            'module-withdrawal',
            'module-kyc',
            'module-card',
            'نسخه-۱۱',
            'نسخه-۱۲',
            'نسخه-۱۰',
        ];

        foreach ($requiredAnchors as $anchor) {
            if (! Str::contains($markdown, $anchor)) {
                $this->error("Required anchor #{$anchor} is missing from system-guide.md");
                $hasError = true;
            }
        }

        $relativeLinkPattern = '/\[[^\]]+\]\((?!https?:)(?!mailto:)(?!tel:)(?!#)([^)]+)\)/u';

        if (preg_match_all($relativeLinkPattern, $markdown, $matches)) {
            $docsRootReal = realpath($docsRoot);
            foreach ($matches[1] as $link) {
                $link = trim($link);
                $target = Str::before($link, '#');
                if ($target === '') {
                    continue;
                }

                $absolutePath = realpath($docsRoot . DIRECTORY_SEPARATOR . $target);
                if ($absolutePath === false || ! Str::startsWith($absolutePath, $docsRootReal)) {
                    $this->error("Broken documentation link detected: {$link}");
                    $hasError = true;
                }
            }
        }

        if ($hasError) {
            return self::FAILURE;
        }

        $this->info('Documentation assets validated successfully.');
        return self::SUCCESS;
    }
}
