# Fixtures for Silverstripe

Fixtures for all mixtures 👋

## Requirements

* SilverStripe ^4.0
* PHP ^7.3

## Installation
```
composer require adrhumphreys/silverstripe-fixtures dev-master
```

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

class PageFixture extends AbstractFixture
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

class MyOtherPageFixture extends AbstractFixture implements DependentFixtureInterface
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

class PageFixture extends AbstractFixture implements OrderedFixtureInterface
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

class PageFixture extends AbstractFixture
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

class PageFixtureTwo extends AbstractFixture
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
You can create assets really easily like so:
```php
\AdrHumphreys\Fixtures\ReferenceManager::findOrMakeAsset('my-asset-id', 'file/path.jpg');
```

This will create the image, and store it with a reference of `my-asset-id`. You can also pass through as a third argument an array of params e.g. `['Title' => 'my asset title']` this will be translated to `$image->Title = 'my asset title'` so is case-sensitive. The function will also return the stored image.

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
