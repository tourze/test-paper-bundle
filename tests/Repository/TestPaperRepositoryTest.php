<?php

namespace Tourze\TestPaperBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Enum\PaperGenerationType;
use Tourze\TestPaperBundle\Enum\PaperStatus;
use Tourze\TestPaperBundle\Repository\TestPaperRepository;

/**
 * @internal
 */
#[CoversClass(TestPaperRepository::class)]
#[RunTestsInSeparateProcesses]
final class TestPaperRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        $testPaper = new TestPaper();
        $testPaper->setTitle('测试试卷_' . uniqid());
        $testPaper->setStatus(PaperStatus::DRAFT);
        $testPaper->setGenerationType(PaperGenerationType::MANUAL);
        $testPaper->setTotalScore(100);
        $testPaper->setPassScore(60);
        $testPaper->setQuestionCount(0);
        $testPaper->setAllowRetake(true);
        $testPaper->setCreateTime(new \DateTimeImmutable());
        $testPaper->setUpdateTime(new \DateTimeImmutable());
        $testPaper->setCreatedBy('test-user');
        $testPaper->setUpdatedBy('test-user');

        return $testPaper;
    }

    protected function onSetUp(): void
    {
        // Repository测试不需要额外的设置
    }

    protected function getRepository(): TestPaperRepository
    {
        return self::getService(TestPaperRepository::class);
    }

    public function testFindPublished(): void
    {
        $results = $this->getRepository()->findPublished();
        $this->assertIsArray($results);
    }

    public function testFindByCreator(): void
    {
        $results = $this->getRepository()->findByCreator('test-user');
        $this->assertIsArray($results);
    }

    public function testSearchByKeyword(): void
    {
        $results = $this->getRepository()->searchByKeyword('test');
        $this->assertIsArray($results);
    }

    public function testFindRecentlyCreated(): void
    {
        $results = $this->getRepository()->findRecentlyCreated(5);
        $this->assertIsArray($results);
        $this->assertLessThanOrEqual(5, count($results));
    }

    public function testFindWithStatistics(): void
    {
        $results = $this->getRepository()->findWithStatistics();
        $this->assertIsArray($results);
    }

    public function testFindPopular(): void
    {
        $results = $this->getRepository()->findPopular(5);
        $this->assertIsArray($results);
        $this->assertLessThanOrEqual(5, count($results));
    }

    public function testGetStatisticsByStatus(): void
    {
        $results = $this->getRepository()->getStatisticsByStatus();
        $this->assertIsArray($results);
    }

    public function testGetStatisticsByGenerationType(): void
    {
        $results = $this->getRepository()->getStatisticsByGenerationType();
        $this->assertIsArray($results);
    }
}
