<?php

declare(strict_types=1);

namespace Tourze\TestPaperBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Enum\PaperGenerationType;
use Tourze\TestPaperBundle\Enum\PaperStatus;

class TestPaperFixtures extends Fixture
{
    public const TEST_PAPER_1 = 'test-paper-1';

    public function load(ObjectManager $manager): void
    {
        $testPaper = new TestPaper();
        $testPaper->setTitle('测试试卷1');
        $testPaper->setDescription('这是一个用于测试的试卷');
        $testPaper->setStatus(PaperStatus::PUBLISHED);
        $testPaper->setGenerationType(PaperGenerationType::MANUAL);
        $testPaper->setTotalScore(100);
        $testPaper->setPassScore(60);
        $testPaper->setTimeLimit(120);
        $testPaper->setQuestionCount(10);
        $testPaper->setAllowRetake(true);
        $testPaper->setMaxAttempts(3);

        $manager->persist($testPaper);
        $manager->flush();

        $this->addReference(self::TEST_PAPER_1, $testPaper);
    }
}
