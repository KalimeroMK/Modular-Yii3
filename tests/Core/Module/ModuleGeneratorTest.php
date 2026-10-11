<?php

declare(strict_types=1);

namespace App\Tests\Core\Module;

use App\Core\Module\Definition\ModuleDefinition;
use App\Core\Module\ModuleGenerator;
use App\Core\Module\StubRenderer;
use App\Core\Module\YamlModuleParser;
use PHPUnit\Framework\TestCase;

use function dirname;
use function file_get_contents;
use function glob;
use function is_dir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

final class ModuleGeneratorTest extends TestCase
{
    private string $root;
    private ModuleGenerator $generator;

    public function testEveryGeneratedFileIsValidPhp(): void
    {
        $files = $this->generator->files($this->definition());

        self::assertNotEmpty($files);

        foreach ($files as $path => $content) {
            self::assertSame(0, $this->lint($content), $path . ' does not parse');
        }
    }

    public function testFilePlanCoversEveryLayer(): void
    {
        $paths = array_keys($this->generator->files($this->definition()));

        self::assertContains('src/Modules/Product/Domain/Model/Product.php', $paths);
        self::assertContains('src/Modules/Product/Domain/Repository/ProductRepositoryInterface.php', $paths);
        self::assertContains('src/Modules/Product/Domain/Exception/ProductNotFoundException.php', $paths);
        self::assertContains('src/Modules/Product/Domain/Enum/ProductStatus.php', $paths);
        self::assertContains('src/Modules/Product/Application/Service/ProductService.php', $paths);
        self::assertContains('src/Modules/Product/Application/Action/CreateAction.php', $paths);
        self::assertContains('src/Modules/Product/Config/di.php', $paths);
        self::assertContains('src/Modules/Product/Config/routes.php', $paths);
        self::assertContains('tests/Modules/Product/ProductServiceTest.php', $paths);
    }

    public function testMorphRelationIsNarrowedWithAnOnCondition(): void
    {
        $definitions = (new YamlModuleParser())->parse([
            'modules' => [
                'Product' => [
                    'fields' => ['name' => 'string'],
                    'relations' => ['comments' => 'morphMany:Comment:commentable'],
                ],
                'Comment' => ['fields' => ['body' => 'text']],
            ],
        ]);

        $model = $this->generator->files($definitions[0])['src/Modules/Product/Domain/Model/Product.php'];

        self::assertStringContainsString(
            "'comments' => \$this->hasMany(Comment::class, ['commentable_id' => 'id'])",
            $model,
        );
        self::assertStringContainsString("->on(['commentable_type' => 'product'])", $model);
    }

    public function testMorphTargetGetsAResolverAndACompositeIndex(): void
    {
        $definitions = (new YamlModuleParser())->parse([
            'modules' => [
                'Product' => [
                    'fields' => ['name' => 'string'],
                    'relations' => ['comments' => 'morphMany:Comment:commentable'],
                ],
                'Comment' => ['fields' => ['body' => 'text']],
            ],
        ]);

        $files = $this->generator->files($definitions[1]);
        $resolver = $files['src/Modules/Comment/Application/Service/CommentableResolver.php'];
        $migration = '';

        foreach ($files as $path => $content) {
            if (str_contains($path, '/Migration/')) {
                $migration = $content;
            }
        }

        self::assertNotSame('', $migration, 'the module has no migration');
        self::assertStringContainsString("'product' => Product::class,", $resolver);
        self::assertStringContainsString('public function resolve(Comment $model): ?ActiveRecordInterface', $resolver);
        self::assertStringContainsString(
            "\$b->createIndex('comments', 'idx_comments_commentable', ['commentable_type', 'commentable_id']);",
            $migration,
        );
        self::assertSame(0, $this->lint($resolver));
    }

    public function testApiAndTestsCanBeTurnedOff(): void
    {
        $definition = $this->definition(api: false, tests: false);
        $paths = array_keys($this->generator->files($definition));

        self::assertNotContains('src/Modules/Product/Config/routes.php', $paths);
        self::assertNotContains('src/Modules/Product/Application/Action/CreateAction.php', $paths);
        self::assertNotContains('tests/Modules/Product/ProductServiceTest.php', $paths);
    }

    public function testModelDeclaresRelationsAndColumns(): void
    {
        $model = $this->generator->files($this->definition())['src/Modules/Product/Domain/Model/Product.php'];

        self::assertStringContainsString("return '{{%products}}';", $model);
        self::assertStringContainsString('public string $name', $model);
        self::assertStringContainsString('public int $category_id', $model);
        self::assertStringContainsString("'category' => \$this->hasOne(Category::class, ['id' => 'category_id'])", $model);
        self::assertStringContainsString("->viaTable('{{%product_tag}}', ['product_id' => 'id'])", $model);
    }

    public function testSoftDeleteChangesTheRepositoryInsteadOfDeletingRows(): void
    {
        $files = $this->generator->files($this->definition(softDelete: true));
        $repository = $files['src/Modules/Product/Infrastructure/Persistence/ActiveRecordProductRepository.php'];

        self::assertStringContainsString("->andWhere(['deleted_at' => null])", $repository);
        self::assertStringContainsString('$product->deleted_at = new DateTimeImmutable();', $repository);
        self::assertStringNotContainsString('$product->delete();', $repository);
    }

    public function testExistingFilesAreKeptUnlessForced(): void
    {
        $definition = $this->definition();
        $modelPath = $this->root . '/src/Modules/Product/Domain/Model/Product.php';

        $this->generator->generate($definition);
        file_put_contents($modelPath, '<?php // hand written');

        $statuses = $this->generator->generate($definition);

        self::assertSame(ModuleGenerator::STATUS_SKIPPED, $statuses['src/Modules/Product/Domain/Model/Product.php']);
        self::assertStringContainsString('hand written', (string) file_get_contents($modelPath));

        $forced = $this->generator->generate($definition, true);

        self::assertSame(ModuleGenerator::STATUS_OVERWRITTEN, $forced['src/Modules/Product/Domain/Model/Product.php']);
        self::assertStringNotContainsString('hand written', (string) file_get_contents($modelPath));
    }

    public function testRegeneratingReusesTheExistingMigrationInsteadOfAddingASecondOne(): void
    {
        $definition = $this->definition();

        $this->generator->generate($definition);
        $first = glob($this->root . '/src/Modules/Product/Infrastructure/Migration/*.php');

        $this->generator->generate($definition, true);
        $second = glob($this->root . '/src/Modules/Product/Infrastructure/Migration/*.php');

        self::assertCount(1, (array) $first);
        self::assertSame($first, $second);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir() . '/module-generator-' . uniqid();
        mkdir($this->root, 0o755, true);
        $this->generator = new ModuleGenerator(
            $this->root,
            new StubRenderer(dirname(__DIR__, 3) . '/stubs/module'),
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->root);

        parent::tearDown();
    }

    private function definition(bool $api = true, bool $tests = true, bool $softDelete = false): ModuleDefinition
    {
        $modules = (new YamlModuleParser())->parse([
            'modules' => [
                'Product' => [
                    'api' => $api,
                    'tests' => $tests,
                    'softDelete' => $softDelete,
                    'fields' => [
                        'name' => 'string:120',
                        'price' => 'decimal:10,2',
                        'status' => 'enum:draft,published',
                        'published_at' => 'datetime?',
                    ],
                    'relations' => [
                        'category' => 'belongsTo:Category',
                        'tags' => 'belongsToMany:Tag',
                    ],
                ],
            ],
        ]);

        return $modules[0];
    }

    private function lint(string $content): int
    {
        $file = tempnam(sys_get_temp_dir(), 'lint') . '.php';
        file_put_contents($file, $content);
        exec(PHP_BINARY . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $code);
        unlink($file);

        return $code;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach ((array) glob($directory . '/*') as $entry) {
            is_dir((string) $entry) ? $this->removeDirectory((string) $entry) : unlink((string) $entry);
        }

        rmdir($directory);
    }
}
