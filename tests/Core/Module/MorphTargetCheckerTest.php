<?php

declare(strict_types=1);

namespace App\Tests\Core\Module;

use App\Core\Module\MorphTargetChecker;
use App\Core\Module\YamlModuleParser;
use PHPUnit\Framework\TestCase;

final class MorphTargetCheckerTest extends TestCase
{
    public function testTargetOutsideTheBuildIsReportedWithTheColumnsItNeeds(): void
    {
        $missing = MorphTargetChecker::missingTargets($this->parse([
            'Product' => [
                'fields' => ['name' => 'string'],
                'relations' => ['comments' => 'morphMany:Comment:commentable'],
            ],
        ]));

        self::assertSame(
            ['Comment' => ['commentable_type: string:32', 'commentable_id: integer']],
            $missing,
        );
    }

    public function testNothingIsReportedWhenTheTargetIsGeneratedToo(): void
    {
        $missing = MorphTargetChecker::missingTargets($this->parse([
            'Product' => [
                'fields' => ['name' => 'string'],
                'relations' => ['comments' => 'morphMany:Comment:commentable'],
            ],
            'Comment' => ['fields' => ['body' => 'text']],
        ]));

        self::assertSame([], $missing);
    }

    public function testOrdinaryRelationsAreNotReported(): void
    {
        $missing = MorphTargetChecker::missingTargets($this->parse([
            'Product' => [
                'fields' => ['name' => 'string'],
                'relations' => ['category' => 'belongsTo:Category'],
            ],
        ]));

        self::assertSame([], $missing);
    }

    /**
     * @param array<string, mixed> $modules
     *
     * @return list<\App\Core\Module\Definition\ModuleDefinition>
     */
    private function parse(array $modules): array
    {
        return (new YamlModuleParser())->parse(['modules' => $modules]);
    }
}
