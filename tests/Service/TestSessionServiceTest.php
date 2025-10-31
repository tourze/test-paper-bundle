<?php

namespace Tourze\TestPaperBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Entity\TestSession;
use Tourze\TestPaperBundle\Enum\SessionStatus;
use Tourze\TestPaperBundle\Exception\SessionException;
use Tourze\TestPaperBundle\Repository\TestSessionRepository;
use Tourze\TestPaperBundle\Service\PaperScoringService;
use Tourze\TestPaperBundle\Service\TestSessionService;

/**
 * @internal
 */
#[CoversClass(TestSessionService::class)]
#[RunTestsInSeparateProcesses]
final class TestSessionServiceTest extends AbstractIntegrationTestCase
{
    private TestSessionService $service;

    private TestSessionRepository&MockObject $sessionRepository;

    private PaperScoringService&MockObject $scoringService;

    protected function onSetUp(): void
    {
        // Mock Repository - 需要使用具体类是因为：
        // 1. Repository包含复杂的Doctrine查询逻辑，直接测试会涉及数据库操作
        // 2. 当前测试重点在于Service的业务逻辑而非Repository的查询功能
        // 3. 使用Mock可以精确控制返回值，避免数据库状态影响测试结果
        $this->sessionRepository = $this->createMock(TestSessionRepository::class);

        // Mock PaperScoringService - 需要使用具体类是因为：
        // 1. PaperScoringService涉及复杂的评分算法，不是当前测试重点
        // 2. 使用Mock可以精确控制评分结果，专注测试SessionService的状态管理逻辑
        // 3. 评分服务的具体实现应在其专门的测试类中测试
        $this->scoringService = $this->createMock(PaperScoringService::class);

        // 将Mock设置到容器中
        self::getContainer()->set(TestSessionRepository::class, $this->sessionRepository);
        self::getContainer()->set(PaperScoringService::class, $this->scoringService);

        $this->service = self::getService(TestSessionService::class);
    }

    public function testStartSession(): void
    {
        // 创建真实的实体对象
        $paper = $this->createTestPaper(3600); // 1小时
        $user = self::createNormalUser();
        $session = $this->createTestSession($paper, $user, SessionStatus::PENDING);

        // 执行测试
        $result = $this->service->startSession($session);

        // 验证结果
        $this->assertSame($session, $result);
        $this->assertEquals(SessionStatus::IN_PROGRESS, $session->getStatus());
        $this->assertNotNull($session->getStartTime());
        $this->assertNotNull($session->getExpiresAt());
    }

    public function testStartSessionWithInvalidStatus(): void
    {
        $paper = $this->createTestPaper();
        $user = self::createNormalUser();
        $session = $this->createTestSession($paper, $user, SessionStatus::IN_PROGRESS);

        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('会话状态不正确，无法开始');

        $this->service->startSession($session);
    }

    public function testStartSessionWithoutTimeLimit(): void
    {
        $paper = $this->createTestPaper(null); // 无时间限制
        $user = self::createNormalUser();
        $session = $this->createTestSession($paper, $user, SessionStatus::PENDING);

        $result = $this->service->startSession($session);

        $this->assertSame($session, $result);
        $this->assertEquals(SessionStatus::IN_PROGRESS, $session->getStatus());
        $this->assertNotNull($session->getStartTime());
        $this->assertNull($session->getExpiresAt());
    }

    public function testSubmitAnswer(): void
    {
        $paper = $this->createTestPaper();
        $user = self::createNormalUser();
        $session = $this->createTestSession($paper, $user, SessionStatus::IN_PROGRESS);

        $questionId = 'question_1';
        $answer = 'A';

        $result = $this->service->submitAnswer($session, $questionId, $answer);

        $this->assertSame($session, $result);
        $this->assertEquals(['question_1' => 'A'], $session->getAnswers());
    }

    public function testSubmitAnswerWithInvalidStatus(): void
    {
        $paper = $this->createTestPaper();
        $user = self::createNormalUser();
        $session = $this->createTestSession($paper, $user, SessionStatus::COMPLETED);

        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('会话状态不正确，无法提交答案');

        $this->service->submitAnswer($session, 'question_1', 'A');
    }

    public function testSubmitAnswerWhenExpired(): void
    {
        $paper = $this->createTestPaper(3600);
        $user = self::createNormalUser();
        $session = $this->createTestSession($paper, $user, SessionStatus::IN_PROGRESS);

        // 设置过期时间为过去时间
        $session->setExpiresAt(new \DateTimeImmutable('-1 hour'));
        $session->setAnswers(['question_1' => 'A']);

        // 设置评分服务的期望 - 当会话过期时会调用
        $this->scoringService->expects($this->once())
            ->method('calculateScore')
            ->with($session)
            ->willReturn(80)
        ;

        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('会话已过期');

        $this->service->submitAnswer($session, 'question_1', 'A');

        // 验证会话状态已更新为过期
        $this->assertEquals(SessionStatus::EXPIRED, $session->getStatus());
        $this->assertNotNull($session->getEndTime());
    }

    public function testExpireSession(): void
    {
        $paper = $this->createTestPaper();
        $user = self::createNormalUser();
        $session = $this->createTestSession($paper, $user, SessionStatus::IN_PROGRESS);
        $session->setAnswers(['question_1' => 'A']);

        $this->scoringService->expects($this->once())
            ->method('calculateScore')
            ->with($session)
            ->willReturn(80)
        ;

        $result = $this->service->expireSession($session);

        $this->assertSame($session, $result);
        $this->assertEquals(SessionStatus::EXPIRED, $session->getStatus());
        $this->assertNotNull($session->getEndTime());
        $this->assertEquals(80, $session->getScore());
        $this->assertTrue($session->isPassed());
    }

    public function testExpireSessionWithoutAnswers(): void
    {
        $paper = $this->createTestPaper();
        $user = self::createNormalUser();
        $session = $this->createTestSession($paper, $user, SessionStatus::IN_PROGRESS);
        $session->setAnswers(null);

        $this->scoringService->expects($this->never())
            ->method('calculateScore')
        ;

        $result = $this->service->expireSession($session);

        $this->assertSame($session, $result);
        $this->assertEquals(SessionStatus::EXPIRED, $session->getStatus());
        $this->assertNotNull($session->getEndTime());
        $this->assertNull($session->getScore());
    }

    public function testExpireSessionAlreadyFinished(): void
    {
        $paper = $this->createTestPaper();
        $user = self::createNormalUser();
        $session = $this->createTestSession($paper, $user, SessionStatus::COMPLETED);

        $result = $this->service->expireSession($session);

        $this->assertSame($session, $result);
        $this->assertEquals(SessionStatus::COMPLETED, $session->getStatus());
    }

    public function testCompleteSession(): void
    {
        $paper = $this->createTestPaper();
        $user = self::createNormalUser();
        $session = $this->createTestSession($paper, $user, SessionStatus::IN_PROGRESS);
        $session->setStartTime(new \DateTimeImmutable('-30 minutes'));

        $this->scoringService->expects($this->once())
            ->method('calculateScore')
            ->with($session)
            ->willReturn(75)
        ;

        $result = $this->service->completeSession($session);

        $this->assertSame($session, $result);
        $this->assertEquals(SessionStatus::COMPLETED, $session->getStatus());
        $this->assertNotNull($session->getEndTime());
        $this->assertEquals(75, $session->getScore());
        $this->assertTrue($session->isPassed());
        $this->assertNotNull($session->getDuration());
    }

    public function testCompleteSessionWithInvalidStatus(): void
    {
        $paper = $this->createTestPaper();
        $user = self::createNormalUser();
        $session = $this->createTestSession($paper, $user, SessionStatus::COMPLETED);

        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('会话状态不正确，无法完成');

        $this->service->completeSession($session);
    }

    public function testProcessExpiredSessions(): void
    {
        $paper = $this->createTestPaper(3600);
        $user1 = self::createNormalUser('user1');
        $user2 = self::createNormalUser('user2');

        $expiredSession1 = $this->createTestSession($paper, $user1, SessionStatus::IN_PROGRESS);
        $expiredSession1->setExpiresAt(new \DateTimeImmutable('-1 hour'));

        $expiredSession2 = $this->createTestSession($paper, $user2, SessionStatus::IN_PROGRESS);
        $expiredSession2->setExpiresAt(new \DateTimeImmutable('-30 minutes'));

        $this->sessionRepository->expects($this->once())
            ->method('findExpiredSessions')
            ->willReturn([$expiredSession1, $expiredSession2])
        ;

        $result = $this->service->processExpiredSessions();

        $this->assertEquals(2, $result);
        $this->assertEquals(SessionStatus::EXPIRED, $expiredSession1->getStatus());
        $this->assertEquals(SessionStatus::EXPIRED, $expiredSession2->getStatus());
    }

    public function testCancelSession(): void
    {
        $paper = $this->createTestPaper();
        $user = self::createNormalUser();
        $session = $this->createTestSession($paper, $user, SessionStatus::IN_PROGRESS);

        $result = $this->service->cancelSession($session);

        $this->assertSame($session, $result);
        $this->assertEquals(SessionStatus::CANCELLED, $session->getStatus());
        $this->assertNotNull($session->getEndTime());
    }

    public function testCancelSessionAlreadyFinished(): void
    {
        $paper = $this->createTestPaper();
        $user = self::createNormalUser();
        $session = $this->createTestSession($paper, $user, SessionStatus::COMPLETED);

        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('会话已结束，无法取消');

        $this->service->cancelSession($session);
    }

    public function testCreateSessionNotAllowRetakeWithExisting(): void
    {
        $paper = $this->createTestPaper();
        $paper->setAllowRetake(false);
        $user = self::createNormalUser();

        $existingSession = $this->createTestSession($paper, $user, SessionStatus::COMPLETED);

        $this->sessionRepository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'paper' => $paper,
                'user' => $user,
                'status' => SessionStatus::COMPLETED,
            ])
            ->willReturn($existingSession)
        ;

        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('该试卷不允许重做');

        $this->service->createSession($paper, $user);
    }

    public function testCreateSessionExceedsMaxAttempts(): void
    {
        $paper = $this->createTestPaper();
        $paper->setAllowRetake(true);
        $paper->setMaxAttempts(3);
        $user = self::createNormalUser();

        $this->sessionRepository->expects($this->once())
            ->method('getUserAttemptCount')
            ->with($user, $paper)
            ->willReturn(3)
        ;

        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('已达到最大尝试次数');

        $this->service->createSession($paper, $user);
    }

    public function testCreateSessionWithActiveSession(): void
    {
        $paper = $this->createTestPaper();
        $user = self::createNormalUser();
        $activeSession = $this->createTestSession($paper, $user, SessionStatus::IN_PROGRESS);

        $this->sessionRepository->expects($this->once())
            ->method('findActiveSession')
            ->with($user, $paper)
            ->willReturn($activeSession)
        ;

        $result = $this->service->createSession($paper, $user);

        $this->assertSame($activeSession, $result);
    }

    public function testRetakeSessionNotAllowed(): void
    {
        $paper = $this->createTestPaper();
        $paper->setAllowRetake(false);
        $user = self::createNormalUser();

        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('该试卷不允许重做');

        $this->service->retakeSession($paper, $user);
    }

    private function createTestSession(TestPaper $paper, UserInterface $user, SessionStatus $status): TestSession
    {
        $session = new TestSession();
        $session->setPaper($paper);
        $session->setUser($user);
        $session->setStatus($status);
        $session->setTotalScore($paper->getTotalScore());
        $session->setAttemptNumber(1);

        return $session;
    }

    private function createTestPaper(?int $timeLimit = null): TestPaper
    {
        $paper = new TestPaper();
        $paper->setTitle('测试试卷');
        $paper->setTotalScore(100);
        $paper->setPassScore(60);
        $paper->setTimeLimit($timeLimit);
        $paper->setAllowRetake(true);
        $paper->setMaxAttempts(null);
        $paper->setQuestionCount(10);

        return $paper;
    }
}
