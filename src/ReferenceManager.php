<?php

declare(strict_types=1);

namespace AdrHumphreys\Fixtures;

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

    public static function findOrMakeAsset(
        string $identifier,
        string $path,
        array $params = []
    ): Image {
        $existingImage = self::getByReference($identifier);

        if ($existingImage instanceof Image) {
            return $existingImage;
        }

        $image = Image::create();
        $image->setFromLocalFile($path);

        foreach ($params as $param => $value) {
            $image->$param = $value;
        }

        $image->write();
        $image->publishRecursive();

        self::addReference($identifier, $image);

        return $image;
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

        $object = Injector::inst()->createWithArgs($className, []);

        foreach ($params as $key => $param) {
            $object->$key = $param;
        }

        $object->write();

        self::addReference($identifier, $object);

        return $object;
    }
}
