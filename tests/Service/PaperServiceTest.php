<?php

namespace Tourze\TestPaperBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Tourze\QuestionBankBundle\Entity\Question;
use Tourze\TestPaperBundle\Entity\PaperQuestion;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Enum\PaperStatus;
use Tourze\TestPaperBundle\Service\PaperService;

class PaperServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private PaperService $paperService;

    public function testCreatePaper(): void
    {
        $title = '期末考试';
        $description = '2024年春季期末考试';
        $timeLimit = 7200;
        $passScore = 60;

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(TestPaper::class));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $paper = $this->paperService->createPaper($title, $description, $timeLimit, $passScore);

        $this->assertInstanceOf(TestPaper::class, $paper);
        $this->assertEquals($title, $paper->getTitle());
        $this->assertEquals($description, $paper->getDescription());
        $this->assertEquals($timeLimit, $paper->getTimeLimit());
        $this->assertEquals($passScore, $paper->getPassScore());
        $this->assertEquals(PaperStatus::DRAFT, $paper->getStatus());
    }

    public function testAddQuestion(): void
    {
        $paper = new TestPaper();
        $paper->setTitle('测试试卷');

        $question = $this->createMock(Question::class);
        $mockId = $this->createMock(\Symfony\Component\Uid\Uuid::class);
        $mockId->method('__toString')->willReturn('test-uuid');
        $question->method('getId')->willReturn($mockId);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(PaperQuestion::class));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $paperQuestion = $this->paperService->addQuestion($paper, $question, 5, 1);

        $this->assertInstanceOf(PaperQuestion::class, $paperQuestion);
        $this->assertEquals($paper, $paperQuestion->getPaper());
        $this->assertEquals($question, $paperQuestion->getQuestion());
        $this->assertEquals(5, $paperQuestion->getScore());
        $this->assertEquals(1, $paperQuestion->getSortOrder());
    }

    public function testPublishPaper(): void
    {
        $paper = new TestPaper();
        $paper->setTitle('测试试卷');
        $paper->setStatus(PaperStatus::DRAFT);
        $paper->setQuestionCount(5); // Set question count to avoid validation error

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->paperService->publishPaper($paper);

        $this->assertEquals(PaperStatus::PUBLISHED, $paper->getStatus());
    }

    public function testArchivePaper(): void
    {
        $paper = new TestPaper();
        $paper->setTitle('测试试卷');
        $paper->setStatus(PaperStatus::PUBLISHED);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->paperService->archivePaper($paper);

        $this->assertEquals(PaperStatus::ARCHIVED, $paper->getStatus());
    }

    public function testDuplicatePaper(): void
    {
        $originalPaper = new TestPaper();
        $originalPaper->setTitle('原始试卷');
        $originalPaper->setDescription('原始描述');
        $originalPaper->setTimeLimit(3600);
        $originalPaper->setPassScore(60);

        $this->entityManager
            ->expects($this->atLeastOnce())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $newTitle = '副本 - 原始试卷';
        $newPaper = $this->paperService->duplicatePaper($originalPaper, $newTitle);

        $this->assertInstanceOf(TestPaper::class, $newPaper);
        $this->assertEquals($newTitle, $newPaper->getTitle());
        $this->assertEquals($originalPaper->getDescription(), $newPaper->getDescription());
        $this->assertEquals($originalPaper->getTimeLimit(), $newPaper->getTimeLimit());
        $this->assertEquals($originalPaper->getPassScore(), $newPaper->getPassScore());
        $this->assertEquals(PaperStatus::DRAFT, $newPaper->getStatus());
    }

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->paperService = new PaperService($this->entityManager);
    }
}