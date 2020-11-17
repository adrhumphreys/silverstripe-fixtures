<?php

declare(strict_types=1);

namespace AdrHumphreys\Fixtures;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;

class ReferenceManager
{
    use Injectable;

    /**
     * @var array
     */
    private $references = [];

    public static function addReference(string $identifier, object $reference): void
    {
        self::singleton()->references[$identifier] = $reference;
    }

    public static function getByReference(string $identifier): ?object
    {
        return self::singleton()->references[$identifier] ?? null;
    }

    /*
     * Helper to find or make an asset easily
     * This will load a local file into an asset, write and publish it
     * defaults to Image
     *
     * Expected usage:
     * ReferenceManager::findOrMakeAsset(
     *    'my-cool-asset',
     *    './app/mycoolfile.pdf',
     *    ['title' => 'My cool asset'],
     *    File::class
     * );
     */
    public static function findOrMakeAsset(
        string $identifier,
        string $path,
        array $params = [],
        string $className = Image::class
    ): File {
        $existingAsset = self::getByReference($identifier);

        if ($existingAsset instanceof $className) {
            return $existingAsset;
        }

        $asset = Injector::inst()->create($className);
        $asset->setFromLocalFile($path);

        foreach ($params as $param => $value) {
            $asset->$param = $value;
        }

        $asset->write();
        $asset->publishRecursive();

        self::addReference($identifier, $asset);

        return $asset;
    }

    /*
     * Helper to find or make an objects easily
     * This will write the object but not publish it
     *
     * Expected usage:
     * ReferenceManager::findOrMakeDataObject(
     *    'my-cool-asset',
     *    SilverStripe\Assets\Image::class,
     *    ['title' => 'My cool asset']
     * );
     */
    public static function findOrMakeDataObject(
        string $identifier,
        string $className,
        array $params
    ): ?object {
        $currentRef = self::getByReference($identifier);

        if ($currentRef !== null) {
            return $currentRef;
        }

        $object = Injector::inst()->create($className);

        foreach ($params as $key => $param) {
            $object->$key = $param;
        }

        $object->write();

        self::addReference($identifier, $object);

        return $object;
    }
}
