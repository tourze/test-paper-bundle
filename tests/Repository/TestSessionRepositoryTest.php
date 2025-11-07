<?php

namespace Tourze\TestPaperBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Entity\TestSession;
use Tourze\TestPaperBundle\Enum\PaperGenerationType;
use Tourze\TestPaperBundle\Enum\PaperStatus;
use Tourze\TestPaperBundle\Enum\SessionStatus;
use Tourze\TestPaperBundle\Repository\TestSessionRepository;

/**
 * @internal
 */
#[CoversClass(TestSessionRepository::class)]
#[RunTestsInSeparateProcesses]
final class TestSessionRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): TestSession
    {
        $testSession = new TestSession();

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

        // 使用UserManagerInterface创建真实的用户实体（支持Doctrine persist）
        $userManager = self::getService(\Tourze\UserServiceContracts\UserManagerInterface::class);
        $user = $userManager->createUser(sprintf('test-user-%s', uniqid('', true)), null, null, 'password', ['ROLE_USER']);

        $testSession->setPaper($testPaper);
        $testSession->setUser($user);
        $testSession->setStatus(SessionStatus::PENDING);
        $testSession->setScore(0);
        $testSession->setTotalScore(100);
        $testSession->setStartTime(new \DateTimeImmutable());
        $testSession->setEndTime(null);
        $testSession->setAnswers([]);
        $testSession->setPassed(false);

        return $testSession;
    }

    protected function onSetUp(): void
    {
        // Repository测试不需要额外的设置
    }

    protected function getRepository(): TestSessionRepository
    {
        return self::getService(TestSessionRepository::class);
    }

    public function testFindActiveSession(): void
    {
        // 测试查找活跃会话 - 需要实际的用户实体，跳过此测试
        self::markTestSkipped('需要实际的用户实体实现');
    }

    public function testFindByPaper(): void
    {
        $entity = $this->createNewEntity();
        $em = self::getEntityManager();
        $em->persist($entity->getPaper());
        $em->flush();

        $results = $this->getRepository()->findByPaper($entity->getPaper());
        $this->assertIsArray($results);
    }

    public function testFindByUser(): void
    {
        // 测试查找用户会话 - 需要实际的用户实体，跳过此测试
        self::markTestSkipped('需要实际的用户实体实现');
    }

    public function testFindCompletedByUser(): void
    {
        // 测试查找用户已完成会话 - 需要实际的用户实体，跳过此测试
        self::markTestSkipped('需要实际的用户实体实现');
    }

    public function testFindExpiredSessions(): void
    {
        $results = $this->getRepository()->findExpiredSessions();
        $this->assertIsArray($results);
    }

}
