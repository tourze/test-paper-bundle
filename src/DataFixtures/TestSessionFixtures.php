<?php

declare(strict_types=1);

namespace Tourze\TestPaperBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Entity\TestSession;
use Tourze\TestPaperBundle\Enum\SessionStatus;
use Tourze\UserServiceContracts\UserManagerInterface;

class TestSessionFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private UserManagerInterface $userManager
    ) {}

    public function load(ObjectManager $manager): void
    {
        $testPaper = $this->getReference(TestPaperFixtures::TEST_PAPER_1, TestPaper::class);

        // 使用UserManagerInterface创建测试用户
        $user = $this->userManager->createUser('test-session-user', null, null, 'password');
        $this->userManager->saveUser($user);

        $session = new TestSession();
        $session->setPaper($testPaper);
        $session->setUser($user);
        $session->setStatus(SessionStatus::IN_PROGRESS);
        $session->setScore(0);

        $manager->persist($session);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            TestPaperFixtures::class,
        ];
    }
}
