<?php

declare(strict_types=1);

namespace Tourze\TestPaperBundle\Tests\Helper;

use Symfony\Component\Security\Core\User\UserInterface;

interface TestUserInterface extends UserInterface
{
    public function getId(): ?int;

    public function getUsername(): string;
}