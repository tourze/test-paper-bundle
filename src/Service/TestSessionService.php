<?php

namespace Tourze\TestPaperBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Entity\TestSession;
use Tourze\TestPaperBundle\Enum\SessionStatus;
use Tourze\TestPaperBundle\Exception\SessionException;
use Tourze\TestPaperBundle\Repository\TestSessionRepository;

class TestSessionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TestSessionRepository $sessionRepository,
        private readonly PaperScoringService $scoringService
    ) {
    }

    public function startSession(TestSession $session): TestSession
    {
        if ($session->getStatus() !== SessionStatus::PENDING) {
            throw new SessionException('会话状态不正确，无法开始');
        }

        $session->setStatus(SessionStatus::IN_PROGRESS);
        $session->setStartTime(new \DateTimeImmutable());

        // 设置到期时间
        if ($session->getPaper()->getTimeLimit() !== null) {
            $expiresAt = (new \DateTimeImmutable())->add(new \DateInterval('PT' . $session->getPaper()->getTimeLimit() . 'S'));
            $session->setExpiresAt($expiresAt);
        }

        $this->entityManager->flush();

        return $session;
    }

    public function submitAnswer(TestSession $session, string $questionId, $answer): TestSession
    {
        if ($session->getStatus() !== SessionStatus::IN_PROGRESS) {
            throw new SessionException('会话状态不正确，无法提交答案');
        }

        if ($session->isExpired()) {
            $this->expireSession($session);
            throw new SessionException('会话已过期');
        }

        $answers = $session->getAnswers() ?? [];
        $answers[$questionId] = $answer;
        $session->setAnswers($answers);

        $this->entityManager->flush();

        return $session;
    }

    public function expireSession(TestSession $session): TestSession
    {
        if ($session->getStatus() !== SessionStatus::IN_PROGRESS) {
            return $session;
        }

        $session->setStatus(SessionStatus::EXPIRED);
        $session->setEndTime(new \DateTimeImmutable());

        // 如果有答案，计算分数
        if ($session->getAnswers() !== null) {
            $score = $this->scoringService->calculateScore($session);
            $session->setScore($score);

            $passed = $score >= $session->getPaper()->getPassScore();
            $session->setPassed($passed);
        }

        $this->entityManager->flush();

        return $session;
    }

    public function completeSession(TestSession $session): TestSession
    {
        if ($session->getStatus() !== SessionStatus::IN_PROGRESS) {
            throw new SessionException('会话状态不正确，无法完成');
        }

        $session->setStatus(SessionStatus::COMPLETED);
        $session->setEndTime(new \DateTimeImmutable());

        // 计算用时
        if ($session->getStartTime() !== null) {
            $duration = $session->getEndTime()->getTimestamp() - $session->getStartTime()->getTimestamp();
            $session->setDuration($duration);
        }

        // 计算分数
        $score = $this->scoringService->calculateScore($session);
        $session->setScore($score);

        // 判断是否通过
        $passed = $score >= $session->getPaper()->getPassScore();
        $session->setPassed($passed);

        $this->entityManager->flush();

        return $session;
    }

    public function processExpiredSessions(): int
    {
        $expiredSessions = $this->sessionRepository->findExpiredSessions();
        $count = 0;

        foreach ($expiredSessions as $session) {
            $this->expireSession($session);
            $count++;
        }

        return $count;
    }

    public function getSessionProgress(TestSession $session): array
    {
        $paper = $session->getPaper();
        $answers = $session->getAnswers() ?? [];
        $totalQuestions = $paper->getQuestionCount();
        $answeredQuestions = count($answers);

        $progress = $totalQuestions > 0 ? round(($answeredQuestions / $totalQuestions) * 100, 2) : 0;

        return [
            'totalQuestions' => $totalQuestions,
            'answeredQuestions' => $answeredQuestions,
            'unansweredQuestions' => $totalQuestions - $answeredQuestions,
            'progressPercentage' => $progress,
            'timeRemaining' => $session->getRemainingTime(),
            'isExpired' => $session->isExpired(),
        ];
    }

    public function getSessionStatistics(TestSession $session): array
    {
        if ($session->getStatus() !== SessionStatus::COMPLETED) {
            return [];
        }

        $paper = $session->getPaper();
        $score = $session->getScore();
        $totalScore = $session->getTotalScore();

        $statistics = [
            'score' => $score,
            'totalScore' => $totalScore,
            'percentage' => $session->getScorePercentage(),
            'passed' => $session->isPassed(),
            'duration' => $session->getDuration(),
            'attemptNumber' => $session->getAttemptNumber(),
        ];

        // 计算题型统计
        $typeStats = $this->scoringService->getScoreByType($session);
        $statistics['byType'] = $typeStats;

        // 计算难度统计 - 方法不存在，暂时跳过
        // $difficultyStats = $this->scoringService->getScoreByDifficulty($session);
        // $statistics['byDifficulty'] = $difficultyStats;

        // 计算知识点统计 - 方法不存在，暂时跳过
        // $knowledgePointStats = $this->scoringService->getScoreByKnowledgePoint($session);
        // $statistics['byKnowledgePoint'] = $knowledgePointStats;

        return $statistics;
    }

    public function getUserHistory(UserInterface $user, ?TestPaper $paper = null): array
    {
        if ($paper !== null) {
            return $this->sessionRepository->findBy(['user' => $user, 'paper' => $paper]);
        }

        return $this->sessionRepository->findByUser($user);
    }

    public function getBestScores(UserInterface $user): array
    {
        $sessions = $this->sessionRepository->findByUser($user, SessionStatus::COMPLETED);
        $bestScores = [];

        foreach ($sessions as $session) {
            $paperId = $session->getPaper()->getId();

            if (!isset($bestScores[$paperId]) || $session->getScore() > $bestScores[$paperId]['score']) {
                $bestScores[$paperId] = [
                    'paper' => $session->getPaper(),
                    'session' => $session,
                    'score' => $session->getScore(),
                    'percentage' => $session->getScorePercentage(),
                    'passed' => $session->isPassed(),
                ];
            }
        }

        return array_values($bestScores);
    }

    public function retakeSession(TestPaper $paper, UserInterface $user): TestSession
    {
        if (!$paper->isAllowRetake()) {
            throw new SessionException('该试卷不允许重做');
        }

        // 取消当前活跃的会话
        $activeSession = $this->sessionRepository->findActiveSession($user, $paper);
        if ($activeSession !== null) {
            $this->cancelSession($activeSession);
        }

        return $this->createSession($paper, $user);
    }

    public function cancelSession(TestSession $session): TestSession
    {
        if ($session->getStatus()->isFinished()) {
            throw new SessionException('会话已结束，无法取消');
        }

        $session->setStatus(SessionStatus::CANCELLED);
        $session->setEndTime(new \DateTimeImmutable());

        $this->entityManager->flush();

        return $session;
    }

    public function createSession(TestPaper $paper, UserInterface $user): TestSession
    {
        // 检查是否允许重做
        if (!$paper->isAllowRetake()) {
            $existingSession = $this->sessionRepository->findOneBy([
                'paper' => $paper,
                'user' => $user,
                'status' => SessionStatus::COMPLETED
            ]);

            if ($existingSession !== null) {
                throw new SessionException('该试卷不允许重做');
            }
        }

        // 检查最大尝试次数
        if ($paper->getMaxAttempts() !== null) {
            $attemptCount = $this->sessionRepository->getUserAttemptCount($user, $paper);
            if ($attemptCount >= $paper->getMaxAttempts()) {
                throw new SessionException('已达到最大尝试次数');
            }
        }

        // 检查是否有进行中的会话
        $activeSession = $this->sessionRepository->findActiveSession($user, $paper);
        if ($activeSession !== null) {
            return $activeSession;
        }

        $session = new TestSession();
        $session->setPaper($paper);
        $session->setUser($user);
        $session->setStatus(SessionStatus::PENDING);
        $session->setTotalScore($paper->getTotalScore());

        $attemptNumber = $this->sessionRepository->getUserAttemptCount($user, $paper) + 1;
        $session->setAttemptNumber($attemptNumber);

        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return $session;
    }
}