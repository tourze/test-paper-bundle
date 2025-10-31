<?php

namespace Tourze\TestPaperBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\QuestionBankBundle\Entity\Question;
use Tourze\QuestionBankBundle\Enum\QuestionType;
use Tourze\QuestionBankBundle\ValueObject\Difficulty;
use Tourze\TestPaperBundle\Entity\PaperQuestion;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Enum\PaperStatus;
use Tourze\TestPaperBundle\Service\PaperService;

/**
 * @internal
 */
#[CoversClass(PaperService::class)]
#[RunTestsInSeparateProcesses]
final class PaperServiceTest extends AbstractIntegrationTestCase
{
    private PaperService $paperService;

    protected function onSetUp(): void
    {
        $this->paperService = self::getService(PaperService::class);
    }

    public function testCreatePaper(): void
    {
        $title = '期末考试';
        $description = '2024年春季期末考试';
        $timeLimit = 7200;
        $passScore = 60;

        $paper = $this->paperService->createPaper($title, $description, $timeLimit, $passScore);

        $this->assertInstanceOf(TestPaper::class, $paper);
        $this->assertEquals($title, $paper->getTitle());
        $this->assertEquals($description, $paper->getDescription());
        $this->assertEquals($timeLimit, $paper->getTimeLimit());
        $this->assertEquals($passScore, $paper->getPassScore());
        $this->assertEquals(PaperStatus::DRAFT, $paper->getStatus());

        // 验证数据已持久化
        self::assertEntityPersisted($paper);
    }

    public function testAddQuestion(): void
    {
        // 创建并持久化试卷
        $paper = new TestPaper();
        $paper->setTitle('测试试卷');
        $paper->setStatus(PaperStatus::DRAFT);
        $paper->setCreateTime(new \DateTimeImmutable());
        $paper->setUpdateTime(new \DateTimeImmutable());
        $paper->setCreatedBy('test-user');
        $paper->setUpdatedBy('test-user');
        self::getEntityManager()->persist($paper);
        self::getEntityManager()->flush();

        // 创建并持久化真实的 Question 实体
        $question = $this->createQuestion('测试题目', '这是一个测试题目', QuestionType::SINGLE_CHOICE);

        $paperQuestion = $this->paperService->addQuestion($paper, $question, 5, 1);

        // 验证返回的 PaperQuestion
        $this->assertInstanceOf(PaperQuestion::class, $paperQuestion);
        $this->assertEquals($paper, $paperQuestion->getPaper());
        $this->assertEquals($question, $paperQuestion->getQuestion());
        $this->assertEquals(5, $paperQuestion->getScore());
        $this->assertEquals(1, $paperQuestion->getSortOrder());

        // 验证数据已持久化
        self::assertEntityPersisted($paperQuestion);

        // 验证试卷统计信息已更新
        $this->assertEquals(1, $paper->getQuestionCount());
        $this->assertEquals(5, $paper->getTotalScore());
    }

    public function testPublishPaper(): void
    {
        // 创建有题目的试卷才能发布
        $paper = new TestPaper();
        $paper->setTitle('测试试卷');
        $paper->setStatus(PaperStatus::DRAFT);
        $paper->setQuestionCount(5);
        $paper->setCreateTime(new \DateTimeImmutable());
        $paper->setUpdateTime(new \DateTimeImmutable());
        $paper->setCreatedBy('test-user');
        $paper->setUpdatedBy('test-user');
        self::getEntityManager()->persist($paper);
        self::getEntityManager()->flush();

        $this->paperService->publishPaper($paper);

        $this->assertEquals(PaperStatus::PUBLISHED, $paper->getStatus());

        // 验证状态变更已持久化
        self::getEntityManager()->refresh($paper);
        $this->assertEquals(PaperStatus::PUBLISHED, $paper->getStatus());
    }

    public function testArchivePaper(): void
    {
        $paper = new TestPaper();
        $paper->setTitle('测试试卷');
        $paper->setStatus(PaperStatus::PUBLISHED);
        $paper->setCreateTime(new \DateTimeImmutable());
        $paper->setUpdateTime(new \DateTimeImmutable());
        $paper->setCreatedBy('test-user');
        $paper->setUpdatedBy('test-user');
        self::getEntityManager()->persist($paper);
        self::getEntityManager()->flush();

        $this->paperService->archivePaper($paper);

        $this->assertEquals(PaperStatus::ARCHIVED, $paper->getStatus());

        // 验证状态变更已持久化
        self::getEntityManager()->refresh($paper);
        $this->assertEquals(PaperStatus::ARCHIVED, $paper->getStatus());
    }

    public function testDuplicatePaper(): void
    {
        // 创建原始试卷
        $originalPaper = new TestPaper();
        $originalPaper->setTitle('原始试卷');
        $originalPaper->setDescription('原始描述');
        $originalPaper->setTimeLimit(3600);
        $originalPaper->setPassScore(60);
        $originalPaper->setCreateTime(new \DateTimeImmutable());
        $originalPaper->setUpdateTime(new \DateTimeImmutable());
        $originalPaper->setCreatedBy('test-user');
        $originalPaper->setUpdatedBy('test-user');
        self::getEntityManager()->persist($originalPaper);
        self::getEntityManager()->flush();

        // 为原始试卷添加题目
        $question1 = $this->createQuestion('题目1', '这是题目1', QuestionType::SINGLE_CHOICE);
        $question2 = $this->createQuestion('题目2', '这是题目2', QuestionType::MULTIPLE_CHOICE);

        $this->paperService->addQuestion($originalPaper, $question1, 5, 1);
        $this->paperService->addQuestion($originalPaper, $question2, 10, 2);

        $newTitle = '副本 - 原始试卷';
        $newPaper = $this->paperService->duplicatePaper($originalPaper, $newTitle);

        // 验证新试卷属性
        $this->assertInstanceOf(TestPaper::class, $newPaper);
        $this->assertEquals($newTitle, $newPaper->getTitle());
        $this->assertEquals($originalPaper->getDescription(), $newPaper->getDescription());
        $this->assertEquals($originalPaper->getTimeLimit(), $newPaper->getTimeLimit());
        $this->assertEquals($originalPaper->getPassScore(), $newPaper->getPassScore());
        $this->assertEquals(PaperStatus::DRAFT, $newPaper->getStatus());

        // 验证题目也被复制
        $this->assertEquals(2, $newPaper->getQuestionCount());
        $this->assertEquals(15, $newPaper->getTotalScore());

        // 验证数据已持久化
        self::assertEntityPersisted($newPaper);
    }

    public function testAddQuestions(): void
    {
        $paper = new TestPaper();
        $paper->setTitle('测试试卷');
        $paper->setCreateTime(new \DateTimeImmutable());
        $paper->setUpdateTime(new \DateTimeImmutable());
        $paper->setCreatedBy('test-user');
        $paper->setUpdatedBy('test-user');
        self::getEntityManager()->persist($paper);
        self::getEntityManager()->flush();

        // 创建真实的题目实体
        $question1 = $this->createQuestion('题目1', '这是题目1', QuestionType::SINGLE_CHOICE);
        $question2 = $this->createQuestion('题目2', '这是题目2', QuestionType::MULTIPLE_CHOICE);

        $questions = [
            ['question' => $question1, 'score' => 5],
            ['question' => $question2, 'score' => 10],
        ];

        $this->paperService->addQuestions($paper, $questions);

        // 验证题目被正确添加
        $this->assertEquals(2, $paper->getPaperQuestions()->count());
        $this->assertEquals(15, $paper->getTotalScore());
        $this->assertEquals(2, $paper->getQuestionCount());

        // 验证 PaperQuestion 已持久化
        foreach ($paper->getPaperQuestions() as $paperQuestion) {
            self::assertEntityPersisted($paperQuestion);
        }
    }

    public function testRemoveQuestion(): void
    {
        $paper = new TestPaper();
        $paper->setTitle('测试试卷');
        $paper->setCreateTime(new \DateTimeImmutable());
        $paper->setUpdateTime(new \DateTimeImmutable());
        $paper->setCreatedBy('test-user');
        $paper->setUpdatedBy('test-user');
        self::getEntityManager()->persist($paper);
        self::getEntityManager()->flush();

        $question = $this->createQuestion('测试题目', '这是测试题目', QuestionType::SINGLE_CHOICE);

        // 先添加题目
        $paperQuestion = $this->paperService->addQuestion($paper, $question, 5, 1);

        // 验证题目已添加
        $this->assertEquals(1, $paper->getPaperQuestions()->count());
        $this->assertEquals(5, $paper->getTotalScore());
        $this->assertEquals(1, $paper->getQuestionCount());

        // 移除题目
        $this->paperService->removeQuestion($paper, $paperQuestion);

        // 验证题目已移除
        $this->assertEquals(0, $paper->getPaperQuestions()->count());
        $this->assertEquals(0, $paper->getTotalScore());
        $this->assertEquals(0, $paper->getQuestionCount());

        // 验证 PaperQuestion 已从数据库删除
        $em = self::getEntityManager();
        $em->clear();
        $paperId = $paperQuestion->getId();
        if (null !== $paperId) {
            $deletedPaperQuestion = $em->find(PaperQuestion::class, $paperId);
            $this->assertNull($deletedPaperQuestion);
        }
    }

    public function testShuffleOptions(): void
    {
        $paper = new TestPaper();
        $paper->setTitle('测试试卷');
        $paper->setCreateTime(new \DateTimeImmutable());
        $paper->setUpdateTime(new \DateTimeImmutable());
        $paper->setCreatedBy('test-user');
        $paper->setUpdatedBy('test-user');
        self::getEntityManager()->persist($paper);
        self::getEntityManager()->flush();

        $this->paperService->shuffleOptions($paper);

        // 验证选项随机化标志已设置
        $this->assertTrue($paper->isRandomizeOptions());

        // 验证状态已持久化
        self::getEntityManager()->refresh($paper);
        $this->assertTrue($paper->isRandomizeOptions());
    }

    public function testShuffleQuestions(): void
    {
        $paper = new TestPaper();
        $paper->setTitle('测试试卷');
        $paper->setCreateTime(new \DateTimeImmutable());
        $paper->setUpdateTime(new \DateTimeImmutable());
        $paper->setCreatedBy('test-user');
        $paper->setUpdatedBy('test-user');
        self::getEntityManager()->persist($paper);
        self::getEntityManager()->flush();

        // 创建多个题目
        $question1 = $this->createQuestion('题目1', '这是题目1', QuestionType::SINGLE_CHOICE);
        $question2 = $this->createQuestion('题目2', '这是题目2', QuestionType::MULTIPLE_CHOICE);

        $question3 = $this->createQuestion('题目3', '这是题目3', QuestionType::TRUE_FALSE);

        // 添加题目到试卷
        $paperQuestion1 = $this->paperService->addQuestion($paper, $question1, 5, 1);
        $paperQuestion2 = $this->paperService->addQuestion($paper, $question2, 10, 2);
        $paperQuestion3 = $this->paperService->addQuestion($paper, $question3, 5, 3);

        // 记录原始顺序
        $originalOrders = [
            $paperQuestion1->getSortOrder(),
            $paperQuestion2->getSortOrder(),
            $paperQuestion3->getSortOrder(),
        ];

        $this->paperService->shuffleQuestions($paper);

        // 验证题目随机化标志已设置
        $this->assertTrue($paper->isRandomizeQuestions());

        // 验证所有题目都有有效的排序号（1, 2, 3）
        $newOrders = [
            $paperQuestion1->getSortOrder(),
            $paperQuestion2->getSortOrder(),
            $paperQuestion3->getSortOrder(),
        ];

        sort($newOrders);
        $this->assertEquals([1, 2, 3], $newOrders);

        // 验证状态已持久化
        self::getEntityManager()->refresh($paper);
        $this->assertTrue($paper->isRandomizeQuestions());
    }

    public function testUpdateQuestionOrder(): void
    {
        $paper = new TestPaper();
        $paper->setTitle('测试试卷');
        $paper->setCreateTime(new \DateTimeImmutable());
        $paper->setUpdateTime(new \DateTimeImmutable());
        $paper->setCreatedBy('test-user');
        $paper->setUpdatedBy('test-user');
        self::getEntityManager()->persist($paper);
        self::getEntityManager()->flush();

        $question1 = $this->createQuestion('题目1', '这是题目1', QuestionType::SINGLE_CHOICE);
        $question2 = $this->createQuestion('题目2', '这是题目2', QuestionType::MULTIPLE_CHOICE);

        // 添加题目到试卷
        $paperQuestion1 = $this->paperService->addQuestion($paper, $question1, 5, 1);
        $paperQuestion2 = $this->paperService->addQuestion($paper, $question2, 10, 2);

        // 获取 PaperQuestion 的 ID
        $pq1Id = $paperQuestion1->getId();
        $pq2Id = $paperQuestion2->getId();

        $orderMapping = [
            $pq1Id => 3,
            $pq2Id => 1,
        ];

        $this->paperService->updateQuestionOrder($paper, $orderMapping);

        // 验证排序已更新
        $this->assertEquals(3, $paperQuestion1->getSortOrder());
        $this->assertEquals(1, $paperQuestion2->getSortOrder());

        // 验证数据已持久化
        self::getEntityManager()->refresh($paperQuestion1);
        self::getEntityManager()->refresh($paperQuestion2);
        $this->assertEquals(3, $paperQuestion1->getSortOrder());
        $this->assertEquals(1, $paperQuestion2->getSortOrder());
    }

    /**
     * 创建 Question 实体的 helper 方法
     */
    private function createQuestion(
        string $title,
        string $content,
        QuestionType $type,
        ?Difficulty $difficulty = null,
    ): Question {
        $difficulty ??= Difficulty::medium();
        $question = new Question();
        $question->setTitle($title);
        $question->setContent($content);
        $question->setType($type);
        $question->setDifficulty($difficulty);

        self::getEntityManager()->persist($question);
        self::getEntityManager()->flush();

        return $question;
    }
}
