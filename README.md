# Fixtures for Silverstripe

Fixtures for all mixtures 👋

## Requirements

* SilverStripe ^4.0 || ^5.0
* PHP ^7.4 || ^8.0

## Installation
```
composer require adrhumphreys/silverstripe-fixtures dev-master
```

### Installing as a dev only module:
When running `dev/build` Silverstripe framework will try to load all classes into it's ClassManifest to cache them and allow for functionality such as dependency injection. During that process it will try to load your Fixture class which will then try to load `AdrHumphreys\Fixtures\AbstractFixture` which doesn't exist. This will throw and exception and stop the `dev/build` process.

You have some options to remedy this, ranked from best to worst:

**Option 1: Place your fixtures in the `tests` directory for your project**:
These are designed to be run on a test/dev environment only and the code is more reference than implementation specific. It therefore makes sense to move these files into this directory. Why? It's explicitly ignored when finding files via `ManifestFileFinder`

**Option 2: Add `_manifest_exclude` to the fixture directory**:
This will ensure that `ManifestFileFinder` will ignore files in the directory. This is option 2 because it makes it easier for code that is test only to end up being relied upon by production code which should never be the case

**Option 3: Add `implements TestOnly` to all fixtures**
If you are installing this as a dev dependency then **all** your fixtures will need to implement `\SilverStripe\Dev\TestOnly` this is specifically excluded from Silverstripes class manifest loader

## How to use
The default setup is to run this as a task like so:
```
vendor/bin/sake dev/tasks/load-fixtures directory=app/src/fixtures
```

You'll need to create your fixtures in the directory specified. Or you can implement your own task, look at the task `LoadFixtures` as an example. You can change `->loadFromDirectory` to multiple calls of `->loadFixture($fixtureClassName)`

A basic fixture looks like the following:
```php
<?php

namespace App\Fixtures;

use AdrHumphreys\Fixtures\AbstractFixture;
use SilverStripe\Dev\TestOnly;

class PageFixture extends AbstractFixture implements TestOnly
{
    public function load(): void
    {
        $page = \Page::create();
        $page->Title = 'Example title';
        $page->URLSegment = 'example-page';
        $page->write();
        $page->publishRecursive();
    }

    public function getClassesToClear(): ?array
    {
        return [\Page::class];
    }
}
```

`load` is called when creating the fixture and then `getClassesToClear` is called when purging the fixture. You can also implement `unload` which is function in which you can choose what to do and `getTablesToClear` which is a function similar to `getClassesToClear` but just tables.

Load order is first dependencies with no order requirement and no dependencies. Then ordered fixtures followed lastly by fixtures with dependencies.

### Dependant fixtures
If a fixture depends on another fixture you can implement `DependentFixtureInterface` and the function `getDependencies` returns an array of classes that the fixture depends on.

An example from would be:
```php
<?php

namespace App\Fixtures;

use AdrHumphreys\Fixtures\AbstractFixture;
use AdrHumphreys\Fixtures\DependentFixtureInterface;
use SilverStripe\Dev\TestOnly;

class MyOtherPageFixture extends AbstractFixture implements DependentFixtureInterface, TestOnly
{
    public function load(): void
    {
        // Example
    }

    public function getDependencies(): array
    {
        return [PageFixture::class];
    }
}
```

### Ordered fixtures
You can also specify the order fixtures are loaded in by implementing the interface `OrderedFixtureInterface` the method `getOrder` returns an number which represents the order.
```php
<?php

namespace App\Fixtures;

use AdrHumphreys\Fixtures\AbstractFixture;
use AdrHumphreys\Fixtures\OrderedFixtureInterface;
use SilverStripe\Dev\TestOnly;

class PageFixture extends AbstractFixture implements OrderedFixtureInterface, TestOnly
{
    public function load(): void
    {
        // Example
    }

    public function getOrder(): int
    {
        return 2;
    }
}
```

### Referencing other fixtures
During the load you can store a reference to a fixture by adding it to the internal reference storage with `$this->addReference(string $identifier, object $reference);`. An example is:
```php
<?php

namespace App\Fixtures;

use AdrHumphreys\Fixtures\AbstractFixture;
use SilverStripe\Dev\TestOnly;

class PageFixture extends AbstractFixture implements TestOnly
{
    public const PAGE_REF = 'my-page-ref';

    public function load(): void
    {
        $page = \Page::create();
        $page->Title = 'Example title';
        $page->URLSegment = 'example-page';
        $page->write();
        $page->publishRecursive();

        $this->addReference(self::PAGE_REF, $page);
    }
}
```

You'd then use it by calling `$this->getByReference(string $identifier)`. Example:
```php
<?php

namespace App\Fixtures;

use AdrHumphreys\Fixtures\AbstractFixture;
use SilverStripe\Dev\TestOnly;

class PageFixtureTwo extends AbstractFixture implements TestOnly
{
    public function load(): void
    {
        $refPage = $this->getByReference(PageFixture::PAGE_REF);

        $page = \Page::create();
        $page->Title = 'Example title';
        $page->URLSegment = 'example-page';
        $page->Body = 'PageID: ' . $refPage->ID;
        $page->write();
        $page->publishRecursive();
    }
}
```

### Only running creation/purging
Run the task without purging:
```
vendor/bin/sake dev/tasks/load-fixtures directory=app/src/fixtures append=true
```

Purge the data:
```
vendor/bin/sake dev/tasks/load-fixtures directory=app/src/fixtures purgeOnly=true
```

Do literally nothing:
```
vendor/bin/sake dev/tasks/load-fixtures directory=app/src/fixtures purgeOnly=true append=true
```

### Quality of life functionality:

#### Filtering

You can selectively run fixtures using the filter param:

```bash
vendor/bin/sake dev/tasks/load-fixtures directory=app/src/fixtures filter=/PageFixtureTwo/
```

This will run any fixture matching the filter pattern *and any [dependencies](#dependant-fixtures)*. Note that you may filter out [ordered fixtures](#ordered-fixtures) and these won't be automatically resolved like dependencies.

The filter pattern must be a valid pattern for [preg_match](https://www.php.net/manual/en/function.preg-match.php) including a delimiter. The pattern is matched against the fully qualified class name (eg `App\My\Fixture`).

#### Creating assets

You can create assets really easily like so:
```php
\AdrHumphreys\Fixtures\ReferenceManager::findOrMakeAsset('my-asset-id', 'file/path.jpg');
```

This will create the image, and store it with a reference of `my-asset-id`. You can also pass through as a third argument an array of params e.g. `['Title' => 'my asset title']` this will be translated to `$image->Title = 'my asset title'` so is case-sensitive. The function will also return the stored image. The fourth argument allows you to specify another Asset type eg SilverStripe\Assets\File.

You can then access the asset through `$this->getByReference('my-asset-id')`

Creating other `DataObject`'s can be done like so (these are only written through `->write`):
```php
$ref = ReferenceManager::findOrMakeDataObject(
    'my-cool-page', // reference
    \Page::class, // object to create
    [
        'Title' => 'My cool page', // params
        'URLSegment' => 'my-cool-page'
    ]
);
```

## Maintainers
 * Adrian <adrhumphreys@gmail.com>

## Development and contribution
Smash that pull request button 🥰
