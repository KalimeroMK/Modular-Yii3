<?php

declare(strict_types=1);

namespace App\Core\Module;

use RuntimeException;

use function file_get_contents;
use function is_file;
use function preg_match_all;
use function sprintf;
use function strtr;

/**
 * Renders a stub file by replacing `{{ placeholder }}` markers.
 *
 * Replacement is a single pass, so generated values containing `{{ ... }}` (table names such as `{{%products}}`)
 * are never re-scanned.
 */
final readonly class StubRenderer
{
    public function __construct(private string $stubDirectory) {}

    /**
     * @param array<string, string> $placeholders
     */
    public function render(string $stub, array $placeholders): string
    {
        $path = $this->stubDirectory . '/' . $stub . '.stub';

        if (!is_file($path)) {
            throw new RuntimeException(sprintf('Stub "%s" not found at "%s".', $stub, $path));
        }

        $content = (string) file_get_contents($path);
        $this->assertAllPlaceholdersKnown($stub, $content, $placeholders);

        $map = [];

        foreach ($placeholders as $key => $value) {
            $map['{{ ' . $key . ' }}'] = $value;
        }

        return strtr($content, $map);
    }

    /**
     * @param array<string, string> $placeholders
     */
    private function assertAllPlaceholdersKnown(string $stub, string $content, array $placeholders): void
    {
        preg_match_all('/\{\{ ([a-zA-Z][a-zA-Z0-9_]*) }}/', $content, $matches);

        foreach ($matches[1] as $name) {
            if (!array_key_exists($name, $placeholders)) {
                throw new RuntimeException(
                    sprintf('Stub "%s" uses placeholder "{{ %s }}" which the generator does not provide.', $stub, $name),
                );
            }
        }
    }
}
