<?php

declare(strict_types=1);

namespace App\Tests\ServiceTokenVoter;

use App\Classes\VoterPermissions;
use App\Entity\CourseInterface;
use App\Entity\CourseObjectiveInterface;
use App\Entity\SchoolInterface;
use App\ServiceTokenVoter\CourseObjective as Voter;
use Mockery as m;

class CourseObjectiveTest extends AbstractReadWriteBase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->voter = new Voter();
    }

    public static function supportsTypeProvider(): array
    {
        return [
            [CourseObjectiveInterface::class, true],
            [self::class, false],
        ];
    }

    public static function supportsAttributesProvider(): array
    {
        return [
            [VoterPermissions::VIEW, true],
            [VoterPermissions::CREATE, true],
            [VoterPermissions::DELETE, true],
            [VoterPermissions::EDIT, true],
            [VoterPermissions::LOCK, false],
            [VoterPermissions::UNLOCK, false],
            [VoterPermissions::ROLLOVER, false],
            [VoterPermissions::CREATE_TEMPORARY_FILE, false],
            [VoterPermissions::VIEW_DRAFT_CONTENTS, false],
            [VoterPermissions::VIEW_VIRTUAL_LINK, false],
            [VoterPermissions::ARCHIVE, false],
        ];
    }

    public static function writePermissionsProvider(): array
    {
        return [
            [VoterPermissions::CREATE],
            [VoterPermissions::DELETE],
            [VoterPermissions::EDIT],
        ];
    }

    protected function createMockSubjectWithSchoolContext(int $schoolId): m\MockInterface
    {
        $subject = $this->createMockSubject();
        $course = m::mock(CourseInterface::class);
        $school = m::mock(SchoolInterface::class);

        $subject->shouldReceive('getCourse')->andReturn($course);
        $course->shouldReceive('getSchool')->andReturn($school);
        $school->shouldReceive('getId')->andReturn($schoolId);

        return $subject;
    }

    protected function createMockSubject(): m\MockInterface
    {
        return m::mock(CourseObjectiveInterface::class);
    }
}
