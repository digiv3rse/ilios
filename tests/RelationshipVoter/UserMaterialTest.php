<?php

declare(strict_types=1);

namespace App\Tests\RelationshipVoter;

use App\Classes\UserMaterial;
use App\Classes\VoterPermissions;
use App\Entity\LearningMaterialStatusInterface;
use App\RelationshipVoter\UserMaterial as Voter;
use App\Service\SessionUserPermissionChecker;
use Mockery as m;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class UserMaterialTest extends AbstractBase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->permissionChecker = m::mock(SessionUserPermissionChecker::class);
        $this->voter = new Voter($this->permissionChecker);
    }

    public function testAllowsRootFullAccess(): void
    {
        $this->checkRootEntityAccess(m::mock(UserMaterial::class), [VoterPermissions::VIEW]);
    }

    public function testCanViewNonDraftMaterials(): void
    {
        $user = $this->createMockNonRootSessionUser();
        $token = $this->createMockTokenWithMockSessionUser($user);
        $entity = m::mock(UserMaterial::class);

        $entity->status = LearningMaterialStatusInterface::FINALIZED;
        $user->shouldReceive('performsNonLearnerFunction')->andReturn(false);

        $response = $this->voter->vote($token, $entity, [VoterPermissions::VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $response, "View allowed");
    }

    public function testCanViewDraftMaterialsIfUserPerformsNonStudentFunction(): void
    {
        $user = $this->createMockNonRootSessionUser();
        $token = $this->createMockTokenWithMockSessionUser($user);
        $entity = m::mock(UserMaterial::class);

        $entity->status = LearningMaterialStatusInterface::IN_DRAFT;
        $user->shouldReceive('performsNonLearnerFunction')->andReturn(true);

        $response = $this->voter->vote($token, $entity, [VoterPermissions::VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $response, "View allowed");
    }

    public function testCanNotViewDraftMaterials(): void
    {
        $user = $this->createMockNonRootSessionUser();
        $token = $this->createMockTokenWithMockSessionUser($user);
        $entity = m::mock(UserMaterial::class);

        $entity->status = LearningMaterialStatusInterface::IN_DRAFT;
        $user->shouldReceive('performsNonLearnerFunction')->andReturn(false);

        $response = $this->voter->vote($token, $entity, [VoterPermissions::VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $response, "View denied");
    }

    public static function supportsTypeProvider(): array
    {
        return [
            [UserMaterial::class, true],
            [self::class, false],
        ];
    }

    public static function supportsAttributesProvider(): array
    {
        return [
            [VoterPermissions::VIEW, true],
            [VoterPermissions::CREATE, false],
            [VoterPermissions::DELETE, false],
            [VoterPermissions::EDIT, false],
            [VoterPermissions::LOCK, false],
            [VoterPermissions::UNLOCK, false],
            [VoterPermissions::ROLLOVER, false],
            [VoterPermissions::CREATE_TEMPORARY_FILE, false],
            [VoterPermissions::VIEW_DRAFT_CONTENTS, false],
            [VoterPermissions::VIEW_VIRTUAL_LINK, false],
            [VoterPermissions::ARCHIVE, false],
        ];
    }
}
