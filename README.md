# Modular Yii3

A [Yii3](https://www.yiiframework.com/) application template with a modular, service-oriented layout and a
code generator: you describe a module in `modules.yaml` — fields, relations, flags — and one command writes the
model, repository, service, DTOs, HTTP actions, routes, DI wiring, migration and tests.

```yaml
modules:
  Product:
    fields:
      name: string:120
      price: decimal:10,2
      status: enum:draft,published,archived
      published_at: datetime?
    relations:
      category: belongsTo:Category
      tags: belongsToMany:Tag
```

```shell
./yii module:build
./yii migrate:up
```

That is a working REST resource at `/api/products` with a passing test suite.

It is the Yii3 counterpart of [Modular-Laravel](https://github.com/KalimeroMK/Modular-Laravel).

## Why

Yii3 is deliberately unopinionated: there is no scaffolding, no convention-based autoloading of modules, and
every service is wired explicitly. That is good for applications and tedious for the twentieth CRUD resource.
This template keeps the explicitness and removes the typing.

## Requirements

- PHP 8.2 – 8.5
- Composer
- A database: SQLite out of the box, MySQL or PostgreSQL by changing one line in `.env`

## Installation

```shell
composer create-project kalimeromk/modular-yii3 my-app
cd my-app
cp .env.example .env
./yii migrate:up
composer serve
```

## Describing a module

`modules.yaml` lives in the project root. Every key under `modules:` is a module.

```yaml
modules:
  Product:
    table: products          # optional, defaults to the pluralised module name
    api: true                # HTTP actions + routes            (default: true)
    tests: true              # generated tests                  (default: true)
    timestamps: true         # created_at / updated_at          (default: true)
    softDelete: false        # deleted_at instead of DELETE     (default: false)
    fields:
      name: string:120
      slug: { type: string, size: 150, unique: true }
      price: decimal:10,2
      is_active: boolean
      status: enum:draft,published,archived
      metadata: json?
      description: text?
    relations:
      category: belongsTo:Category
      tags: belongsToMany:Tag
```

### Field types

| Type | Column | PHP type |
|---|---|---|
| `string:120` | `varchar(120)` | `string` |
| `text` | `text` | `string` |
| `integer`, `bigint` | `integer`, `bigint` | `int` |
| `float` | `float` | `float` |
| `decimal:10,2` | `decimal(10,2)` | `string` (precision is never put through a float) |
| `boolean` | `boolean` | `bool` |
| `json` | `json` | `array` |
| `date`, `datetime`, `timestamp`, `time` | same | `DateTimeImmutable` |
| `uuid` | `uuid` | `string` |
| `enum:draft,published` | `varchar(32)` | `string` + a generated PHP enum |

Append `?` to make a column nullable (`description: text?`). The expanded form
`{ type: string, size: 150, unique: true, index: true, nullable: true, default: 'x' }` is accepted for
everything the short form cannot express.

Date and time columns are the one place where the model and the request differ: `yiisoft/db` hands back
`DateTimeImmutable`, while JSON carries strings, so the input DTO keeps a string and the service converts.

### Relations

| Declaration | Generated |
|---|---|
| `category: belongsTo:Category` | `category_id` column, index, foreign key, `hasOne` relation |
| `profile: hasOne:Profile` | `hasOne` relation keyed on the other table |
| `comments: hasMany:Comment` | `hasMany` relation keyed on the other table |
| `tags: belongsToMany:Tag` | junction table `product_tag` + `hasMany(...)->viaTable(...)` |

The foreign key can be overridden: `owner: belongsTo:User:owner_id`.

Polymorphic relations (`morphTo`, `morphMany`) are **not** supported — `yiisoft/active-record` has no
equivalent, and the parser rejects them with an explicit message rather than generating something that only
looks like one. Model the same thing with an explicit foreign key.

## What gets generated

```
src/Modules/Product/
├── Application/
│   ├── Action/           IndexAction, ViewAction, CreateAction, UpdateAction, DeleteAction
│   ├── Dto/              ProductInput (hydrated + validated), ProductResource (response shape)
│   └── Service/          ProductService — the only place business logic belongs
├── Config/
│   ├── di.php            repository interface → implementation
│   └── routes.php        /api/products route group
├── Domain/
│   ├── Enum/             ProductStatus
│   ├── Exception/        ProductNotFoundException
│   ├── Model/            Product (Active Record)
│   └── Repository/       ProductRepositoryInterface
└── Infrastructure/
    ├── Migration/        M…CreateProductsTable
    └── Persistence/      ActiveRecordProductRepository

tests/Modules/Product/
├── ProductRepositoryTest.php
└── ProductServiceTest.php
```

### Generated endpoints

| Method | Path | Response |
|---|---|---|
| `GET` | `/api/products` | `{ data: [...], meta: { total, limit, offset } }` |
| `GET` | `/api/products/{id}` | `{ data: {...} }` or `404` |
| `POST` | `/api/products` | `201` with `{ data: {...} }`, or `422` with per-field errors |
| `PUT` | `/api/products/{id}` | `{ data: {...} }`, `404`, or `422` |
| `DELETE` | `/api/products/{id}` | `204` or `404` |

### Generated tests

The tests run against an in-memory SQLite database with the module's own migration applied — no container, no
fixtures, no mocks. They assert every column round-trips, that pagination holds, and that a missing record
raises the module's own exception. If a column mapping breaks, the suite says so.

```shell
composer test-unit        # phpunit: generated module tests + generator tests
composer test             # the above plus the Codeception suites
```

## Commands

```shell
./yii module:build                   # generate everything in modules.yaml
./yii module:build --dry-run         # list the files without writing
./yii module:build --force           # overwrite existing files
./yii module:build path/to/other.yaml

./yii module:make Invoice \
    --field "number:string:40" \
    --field "total:decimal:12,2" \
    --field "issued_at:datetime" \
    --relation "customer:belongsTo:Customer" \
    --soft-delete
```

`module:make` takes the same options as the YAML flags: `--table`, `--no-api`, `--no-tests`,
`--no-timestamps`, `--soft-delete`, `--force`.

## Regeneration is safe

Generation is idempotent. An existing file is **skipped**, never silently rewritten — `--force` is required to
overwrite. The migration class name is reused when a module already has one, so re-running `module:build`
after adding a field never produces a second migration for the same table; add the new column in a migration
of your own.

So the normal workflow is: generate once, then edit. The generated code is yours.

## How modules are wired

There is no module registry to maintain. `config/configuration.php` globs the module config files:

```php
'di'     => ['common/di/*.php',  '../src/Modules/*/Config/di.php'],
'routes' => ['common/routes.php','../src/Modules/*/Config/routes.php'],
'params' => ['common/params.php','../src/Modules/*/Config/params.php'],
```

`yiisoft/config` resolves those globs at runtime, so a newly generated module is picked up without rebuilding
the merge plan. Module migrations are found the same way, through `sourcePaths` in `config/common/params.php`.

## Database

The driver is chosen from the DSN scheme in `.env`:

```dotenv
DB_DSN=sqlite:/absolute/path/app.sq3      # default: runtime/app.sq3
DB_DSN=mysql:host=127.0.0.1;dbname=app
DB_USERNAME=
DB_PASSWORD=
```

MySQL and PostgreSQL need their driver package (`composer require yiisoft/db-mysql`); SQLite ships with the
template because the test suite uses it.

Generated migrations add foreign keys only on engines that support `ALTER TABLE ... ADD CONSTRAINT`; SQLite
does not, which is why the condition is written out in the migration rather than hidden in a helper.

## CSRF and request bodies

CSRF protection is applied to the browser-facing route group in `config/common/routes.php`, not to the whole
application, so stateless API modules are not affected. `RequestBodyParser` is in the middleware stack, which
is what lets an `application/json` body hydrate a module input DTO.

## Static analysis

```shell
composer psalm      # Psalm, error level 1
composer cs-fix     # PHP CS Fixer, PER-CS
composer rector     # Rector
```

Generated code passes all three unchanged. If it ever does not, that is a bug in a stub.

## Credits

- Built on the [Yii3 application template](https://github.com/yiisoft/app).
- The YAML-driven module idea comes from [Modular-Laravel](https://github.com/KalimeroMK/Modular-Laravel).

## License

BSD-3-Clause, same as the Yii application template it builds on. See [LICENSE](LICENSE).
