<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\DTO\LearningMaterialDTO;
use App\Message\LearningMaterialIndexRequest;
use App\MessageHandler\LearningMaterialIndexHandler;
use App\Repository\LearningMaterialRepository;
use App\Service\Index\LearningMaterials;
use App\Service\NonCachingIliosFileSystem;
use App\Tests\TestCase;
use Mockery as m;

class LearningMaterialIndexHandlerTest extends TestCase
{
    protected m\MockInterface|LearningMaterials $index;
    protected m\MockInterface|LearningMaterialRepository $repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->index = m::mock(LearningMaterials::class);
        $this->repository = m::mock(LearningMaterialRepository::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->index);
        unset($this->repository);
    }

    public function testInvoke(): void
    {
        $dto1 = m::mock(LearningMaterialDTO::class);
        $dto1->relativePath = 'one';
        $dto2 = m::mock(LearningMaterialDTO::class);
        $dto2->relativePath = 'two';
        $handler = new LearningMaterialIndexHandler($this->index, $this->repository);
        $request = new LearningMaterialIndexRequest([6, 24]);

        $this->repository->shouldReceive(('findDTOsBy'))
            ->once()
            ->with(['id' => [6, 24]])
            ->andReturn([
                $dto1,
                $dto2,
            ]);

        $this->index
            ->shouldReceive('index')
            ->once()
            ->withArgs(function (array $materials) use ($dto1, $dto2) {
                $this->assertCount(2, $materials);
                $this->assertContains($dto1, $materials);
                $this->assertContains($dto2, $materials);

                $this->assertEquals([$dto1, $dto2], $materials);

                return true;
            });

        $handler->__invoke($request);
    }
}
