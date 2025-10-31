<?php

namespace Tourze\TestPaperBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\QuestionBankBundle\Entity\Question;
use Tourze\QuestionBankBundle\Enum\QuestionType;
use Tourze\QuestionBankBundle\ValueObject\Difficulty;
use Tourze\TestPaperBundle\Entity\PaperQuestion;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Enum\PaperGenerationType;
use Tourze\TestPaperBundle\Enum\PaperStatus;
use Tourze\TestPaperBundle\Repository\PaperQuestionRepository;

/**
 * @internal
 */
#[CoversClass(PaperQuestionRepository::class)]
#[RunTestsInSeparateProcesses]
final class PaperQuestionRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): PaperQuestion
    {
        $paperQuestion = new PaperQuestion();

        // 需要创建关联的 TestPaper
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

        // 创建一个真实的 Question 实体用于测试
        $question = new Question();
        $question->setTitle('测试题目_' . uniqid());
        $question->setContent('这是一个测试题目的内容');
        $question->setType(QuestionType::SINGLE_CHOICE);
        $question->setDifficulty(Difficulty::medium());
        $question->setScore(10.00);
        $question->setCreatedBy('test-user');
        $question->setUpdatedBy('test-user');

        $paperQuestion->setPaper($testPaper);
        $paperQuestion->setQuestion($question);
        $paperQuestion->setSortOrder(1);
        $paperQuestion->setScore(5);
        $paperQuestion->setIsRequired(true);

        return $paperQuestion;
    }

    protected function onSetUp(): void
    {
        // Repository测试不需要额外的设置
    }

    protected function getRepository(): PaperQuestionRepository
    {
        return self::getService(PaperQuestionRepository::class);
    }

    public function testFindByPaper(): void
    {
        $entity = $this->createNewEntity();
        $em = self::getEntityManager();
        $em->persist($entity->getQuestion());
        $em->persist($entity->getPaper());
        $em->persist($entity);
        $em->flush();

        $results = $this->getRepository()->findByPaper($entity->getPaper());
        $this->assertIsArray($results);
    }

    public function testFindByPaperWithQuestions(): void
    {
        $entity = $this->createNewEntity();
        $em = self::getEntityManager();
        $em->persist($entity->getQuestion());
        $em->persist($entity->getPaper());
        $em->persist($entity);
        $em->flush();

        $results = $this->getRepository()->findByPaperWithQuestions($entity->getPaper());
        $this->assertIsArray($results);
    }

    public function testReorderQuestions(): void
    {
        $entity = $this->createNewEntity();
        $em = self::getEntityManager();
        $em->persist($entity->getQuestion());
        $em->persist($entity->getPaper());
        $em->persist($entity);
        $em->flush();

        // 测试重新排序不会抛出异常
        $this->getRepository()->reorderQuestions($entity->getPaper(), ['non-existent-id']);
        $this->assertTrue(true); // 如果没有异常就算成功
    }

    public function testGetTotalScore(): void
    {
        $entity = $this->createNewEntity();
        $em = self::getEntityManager();
        $em->persist($entity->getQuestion());
        $em->persist($entity->getPaper());
        $em->persist($entity);
        $em->flush();

        $totalScore = $this->getRepository()->getTotalScore($entity->getPaper());
        $this->assertIsInt($totalScore);
    }

    public function testGetQuestionCount(): void
    {
        $entity = $this->createNewEntity();
        $em = self::getEntityManager();
        $em->persist($entity->getQuestion());
        $em->persist($entity->getPaper());
        $em->persist($entity);
        $em->flush();

        $count = $this->getRepository()->getQuestionCount($entity->getPaper());
        $this->assertIsInt($count);
    }

    public function testGetRequiredQuestionCount(): void
    {
        $entity = $this->createNewEntity();
        $em = self::getEntityManager();
        $em->persist($entity->getQuestion());
        $em->persist($entity->getPaper());
        $em->persist($entity);
        $em->flush();

        $count = $this->getRepository()->getRequiredQuestionCount($entity->getPaper());
        $this->assertIsInt($count);
    }

    public function testGetStatisticsByType(): void
    {
        $entity = $this->createNewEntity();
        $em = self::getEntityManager();
        $em->persist($entity->getQuestion());
        $em->persist($entity->getPaper());
        $em->persist($entity);
        $em->flush();

        $stats = $this->getRepository()->getStatisticsByType($entity->getPaper());
        $this->assertIsArray($stats);
    }

    public function testGetStatisticsByDifficulty(): void
    {
        $entity = $this->createNewEntity();
        $em = self::getEntityManager();
        $em->persist($entity->getQuestion());
        $em->persist($entity->getPaper());
        $em->persist($entity);
        $em->flush();

        $stats = $this->getRepository()->getStatisticsByDifficulty($entity->getPaper());
        $this->assertIsArray($stats);
    }
}
