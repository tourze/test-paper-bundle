<?php

declare(strict_types=1);

namespace Tourze\TestPaperBundle\Tests\Helper;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * 测试用的简单User实现，避免依赖具体的User Bundle
 */
class TestUser implements TestUserInterface
{
    public function __construct(
        private string $userIdentifier,
        private array $roles = ['ROLE_USER'],
        private ?string $password = null,
        private ?int $id = null
    ) {}

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
        // 不需要实现
    }

    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
    }

    public function setUserIdentifier(string $userIdentifier): void
    {
        $this->userIdentifier = $userIdentifier;
    }

    public function setUsername(string $username): void
    {
        $this->userIdentifier = $username;
    }

    public function getUsername(): string
    {
        return $this->userIdentifier;
    }

    public function setEmail(string $email): void
    {
        // Mock方法，不做实际存储
    }

    public function setPlainPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}