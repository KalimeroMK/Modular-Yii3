<?php

declare(strict_types=1);

namespace App\Tests\Core\Module;

use App\Modules\Category\Domain\Model\Category;
use App\Modules\Comment\Application\Service\CommentableResolver;
use App\Modules\Comment\Domain\Model\Comment;
use App\Modules\Product\Domain\Model\Product;
use App\Tests\Support\ModuleTestCase;
use DateTimeImmutable;
use Yiisoft\Db\Migration\MigrationInterface;

use function array_map;
use function class_exists;
use function glob;
use function sort;

/**
 * Proves that the polymorphic relation the generator writes behaves like one.
 *
 * Active Record has no `morphMany`, so the generated relation narrows a plain `hasMany` with an ON
 * condition. This test runs against the example modules and is skipped when they have been removed.
 */
final class MorphRelationTest extends ModuleTestCase
{
    public function testLazyLoadingReturnsOnlyTheOwnersComments(): void
    {
        [$product, $category] = $this->seed();

        self::assertSame(['on the product', 'also on the product'], $this->bodies($product->relation('comments')));
        self::assertSame(['on the category'], $this->bodies($category->relation('comments')));
    }

    public function testEagerLoadingKeepsTheOwnersApart(): void
    {
        $this->seed();

        $products = Product::query()->with('comments')->all();
        $categories = Category::query()->with('comments')->all();

        self::assertCount(2, $products[0]->relation('comments'));
        self::assertCount(1, $categories[0]->relation('comments'));
    }

    public function testJoiningOnlyMatchesOwnersThatHaveComments(): void
    {
        $this->seed();

        self::assertCount(1, Product::query()->innerJoinWith('comments')->all());
    }

    public function testResolverWalksTheRelationBack(): void
    {
        [$product] = $this->seed();
        $resolver = new CommentableResolver();

        $comment = Comment::query()->andWhere(['body' => 'on the product'])->one();
        self::assertInstanceOf(Comment::class, $comment);

        $owner = $resolver->resolve($comment);

        self::assertInstanceOf(Product::class, $owner);
        self::assertSame($product->id, $owner->id);
    }

    public function testResolverReturnsNullForAnUnknownType(): void
    {
        $this->seed();

        $comment = new Comment();
        $comment->commentable_type = 'spaceship';
        $comment->commentable_id = 1;

        self::assertNull((new CommentableResolver())->resolve($comment));
    }

    public function testResolverListsTheTypesItAccepts(): void
    {
        $types = CommentableResolver::types();
        sort($types);

        self::assertSame(['category', 'product'], $types);
    }

    protected function setUp(): void
    {
        if (!class_exists(Comment::class) || !class_exists(CommentableResolver::class)) {
            self::markTestSkipped('The example modules have been removed from this project.');
        }

        parent::setUp();
    }

    /**
     * Every example module's migration, so the fixtures below have their tables.
     *
     * @return list<MigrationInterface>
     */
    protected function migrations(): array
    {
        $migrations = [];

        foreach (['Category', 'Product', 'Tag', 'Comment'] as $module) {
            foreach ((array) glob(dirname(__DIR__, 3) . '/src/Modules/' . $module . '/Infrastructure/Migration/*.php') as $file) {
                $class = 'App\\Modules\\' . $module . '\\Infrastructure\\Migration\\' . basename((string) $file, '.php');
                $migration = new $class();
                self::assertInstanceOf(MigrationInterface::class, $migration);
                $migrations[] = $migration;
            }
        }

        return $migrations;
    }

    /**
     * @return array{Product, Category}
     */
    private function seed(): array
    {
        $category = new Category();
        $category->name = 'Books';
        $category->slug = 'books';
        $category->save();

        $product = new Product();
        $product->name = 'Dune';
        $product->slug = 'dune';
        $product->price = '19.99';
        $product->is_active = true;
        $product->status = 'published';
        $product->category_id = $category->id;
        $product->save();

        foreach ([
            ['product', $product->id, 'on the product'],
            ['product', $product->id, 'also on the product'],
            ['category', $category->id, 'on the category'],
        ] as [$type, $id, $body]) {
            $comment = new Comment();
            $comment->author = 'tester';
            $comment->body = $body;
            $comment->commentable_type = $type;
            $comment->commentable_id = $id;
            $comment->created_at = new DateTimeImmutable();
            $comment->save();
        }

        return [$product, $category];
    }

    /**
     * @param array<array-key, mixed> $comments
     *
     * @return list<string>
     */
    private function bodies(mixed $comments): array
    {
        return array_map(
            static fn(mixed $comment): string => $comment instanceof Comment ? $comment->body : '',
            (array) $comments,
        );
    }
}
