<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Util;

use Symfony\Component\PropertyAccess\PropertyAccess;

class Arrays
{
    public static function sortByArray(array &$sortedArray, array $sortByArray, string $fieldName)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        usort(
            $sortedArray,
            function ($a, $b) use ($sortByArray, $fieldName, $accessor) {
                $posA = array_search($accessor->getValue($a, $fieldName), $sortByArray);
                $posB = array_search($accessor->getValue($b, $fieldName), $sortByArray);

                return $posA - $posB;
            }
        );
    }

    /**
     * @return mixed|null
     */
    public static function min(array $array)
    {
        $array = self::removeNullValues($array);

        return $array ? min($array) : null;
    }

    /**
     * @return mixed|null
     */
    public static function max(array $array)
    {
        $array = self::removeNullValues($array);

        return $array ? max($array) : null;
    }

    private static function removeNullValues(array $array): array
    {
        return array_filter(
            $array,
            function ($item) {
                return $item !== null;
            }
        );
    }

    /**
     * Adds items from the $relatedCollection to the corresponding items in $mainCollection.
     *
     * Example:
     * $mainCollection = [
     *    ['id' => 1],
     *    ['id' => 2],
     * ];
     * $relatedCollection = [
     *    ['relatedId' => 1, 'name' => 'a'],
     *    ['relatedId' => 1, 'name' => 'b'],
     *    ['relatedId' => 2, 'name' => 'c'],
     * ];
     * Arrays::addRelatedData($mainCollection, 'id', $relatedCollection, 'relatedId', 'related');
     *
     * Result:
     * $mainCollection = [
     *     ['id' => 1, 'related' => [['name' => 'a'], ['name' => 'b']]],
     *     ['id' => 2, 'related' => [['name' => 'c']],
     * ];
     */
    public static function addRelatedData(
        array &$mainCollection,
        string $key,
        array $relatedCollection,
        string $matchKey,
        string $collectionKey
    ): void {
        $indexedCollection = [];

        foreach ($mainCollection as &$item) {
            $indexedCollection[$item[$key]] = &$item;
            $item[$collectionKey] = [];
        }

        foreach ($relatedCollection as &$relatedItem) {
            $primaryKey = $relatedItem[$matchKey];
            unset($relatedItem[$matchKey]);
            $indexedCollection[$primaryKey][$collectionKey][] = $relatedItem;
        }
    }
}
