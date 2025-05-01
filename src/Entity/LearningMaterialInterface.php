<?php

declare(strict_types=1);

namespace App\Entity;

use App\Traits\IndexableCoursesEntityInterface;
use DateTime;
use Doctrine\Common\Collections\Collection;
use App\Traits\DescribableNullableEntityInterface;
use App\Traits\IdentifiableEntityInterface;
use App\Traits\TitledEntityInterface;

interface LearningMaterialInterface extends
    IdentifiableEntityInterface,
    TitledEntityInterface,
    DescribableNullableEntityInterface,
    LoggableEntityInterface,
    SessionStampableInterface,
    IndexableCoursesEntityInterface
{
    public function setOriginalAuthor(?string $originalAuthor): void;
    public function getOriginalAuthor(): ?string;

    public function getToken(): string;

    /**
     * Generate a random token for use in downloading
     */
    public function generateToken(): void;

    public function setStatus(LearningMaterialStatusInterface $status): void;
    public function getStatus(): LearningMaterialStatusInterface;

    public function setUserRole(LearningMaterialUserRoleInterface $userRole): void;
    public function getUserRole(): LearningMaterialUserRoleInterface;

    public function setOwningUser(UserInterface $user): void;
    public function getOwningUser(): UserInterface;

    public function setCitation(?string $citation): void;
    public function getCitation(): ?string;

    public function setLink(?string $link): void;
    public function getLink(): ?string;

    public function setRelativePath(?string $path): void;
    public function getRelativePath(): ?string;

    public function setCopyrightPermission(?bool $copyrightPermission): void;

    public function hasCopyrightPermission(): ?bool;

    public function setCopyrightRationale(?string $copyrightRationale): void;
    public function getCopyrightRationale(): ?string;

    public function getUploadDate(): DateTime;

    public function setMimetype(?string $mimetype): void;
    public function getMimetype(): ?string;

    public function setFilesize(?int $filesize): void;
    public function getFilesize(): ?int;


    public function setFilename(?string $filename): void;
    public function getFilename(): ?string;

    public function setCourseLearningMaterials(?Collection $courseLearningMaterials = null): void;
    public function addCourseLearningMaterial(CourseLearningMaterialInterface $courseLearningMaterial): void;
    public function removeCourseLearningMaterial(CourseLearningMaterialInterface $courseLearningMaterial): void;
    public function getCourseLearningMaterials(): Collection;

    public function setSessionLearningMaterials(?Collection $sessionLearningMaterials = null): void;
    public function addSessionLearningMaterial(SessionLearningMaterialInterface $sessionLearningMaterial): void;
    public function removeSessionLearningMaterial(SessionLearningMaterialInterface $sessionLearningMaterial): void;
    public function getSessionLearningMaterials(): Collection;

    /**
     * Gets the primary school of the LM's owning user.
     */
    public function getOwningSchool(): SchoolInterface;

    /**
     * Use the data in the object to determine which validation
     * groups should be applied
     */
    public function getValidationGroups(): array;
}
