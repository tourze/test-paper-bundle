<?php

declare(strict_types=1);

namespace Tourze\TestPaperBundle\DataFixtures;

use BizUserBundle\Entity\BizUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Entity\TestSession;
use Tourze\TestPaperBundle\Enum\SessionStatus;

class TestSessionFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $testPaper = $this->getReference(TestPaperFixtures::TEST_PAPER_1, TestPaper::class);

        // 创建测试用户
        $user = new BizUser();
        $user->setUsername('test-session-user');
        $user->setPlainPassword('password');

        $manager->persist($user);

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
