<?php

declare(strict_types=1);

namespace App\Tests\Endpoints;

use PHPUnit\Framework\Attributes\Group;
use App\Tests\Fixture\LoadMeshConceptData;
use App\Tests\Fixture\LoadMeshTermData;

/**
 * MeshConcept API endpoint Test.
 */
#[Group('api_5')]
class MeshConceptTest extends AbstractMeshEndpoint
{
    protected string $testName =  'meshConcepts';

    protected function getFixtures(): array
    {
        return [
            LoadMeshConceptData::class,
            LoadMeshTermData::class,
        ];
    }

    public static function filtersToTest(): array
    {
        return [
            'id' => [[0], ['id' => '1']],
            'ids' => [[0, 1], ['id' => ['1', '2']]],
            'missingId' => [[], ['id' => '99']],
            'missingIds' => [[], ['id' => ['99']]],
            'name' => [[1], ['name' => 'second concept']],
            'preferred' => [[0], ['preferred' => true]],
            'notPreferred' => [[1], ['preferred' => false]],
            'scopeNote' => [[0], ['scopeNote' => 'first scopeNote']],
            'casn1Name' => [[1], ['casn1Name' => 'second casn']],
            'terms' => [[0], ['terms' => [1]]],
            'descriptors' => [[0, 1], ['descriptors' => ['abc1']]],
        ];
    }

    public static function graphQLFiltersToTest(): array
    {
        $filters = self::filtersToTest();
        $filters['ids'] = [[0, 1], ['ids' => ['1', '2']]];
        $filters['missingIds'] = [[], ['ids' => ['99']]];

        return $filters;
    }

    protected function getTimeStampFields(): array
    {
        return ['updatedAt', 'createdAt'];
    }
}
