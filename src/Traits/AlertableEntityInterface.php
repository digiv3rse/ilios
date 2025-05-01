<?php

declare(strict_types=1);

namespace App\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\AlertInterface;

/**
 * Interface AlertableEntityInterface
 */
interface AlertableEntityInterface
{
    public function setAlerts(?Collection $alerts = null): void;

    public function addAlert(AlertInterface $alert): void;

    public function removeAlert(AlertInterface $alert): void;

    public function getAlerts(): Collection;
}
