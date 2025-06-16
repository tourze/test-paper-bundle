<?php

namespace Tourze\TestPaperBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Entity\TestSession;
use Tourze\TestPaperBundle\Enum\SessionStatus;

/**
 * @extends ServiceEntityRepository<TestSession>
 */
class TestSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestSession::class);
    }

    public function findByUser(UserInterface $user, ?SessionStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('ts')
            ->andWhere('ts.user = :user')
            ->setParameter('user', $user);

        if ($status) {
            $qb->andWhere('ts.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->orderBy('ts.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByPaper(TestPaper $paper, ?SessionStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('ts')
            ->andWhere('ts.paper = :paper')
            ->setParameter('paper', $paper);

        if ($status) {
            $qb->andWhere('ts.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->orderBy('ts.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveSession(UserInterface $user, TestPaper $paper): ?TestSession
    {
        return $this->createQueryBuilder('ts')
            ->andWhere('ts.user = :user')
            ->andWhere('ts.paper = :paper')
            ->andWhere('ts.status IN (:activeStatuses)')
            ->setParameter('user', $user)
            ->setParameter('paper', $paper)
            ->setParameter('activeStatuses', [SessionStatus::PENDING, SessionStatus::IN_PROGRESS])
            ->orderBy('ts.createTime', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findExpiredSessions(): array
    {
        return $this->createQueryBuilder('ts')
            ->andWhere('ts.status = :status')
            ->andWhere('ts.expiresAt < :now')
            ->setParameter('status', SessionStatus::IN_PROGRESS)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    public function getUserAttemptCount(UserInterface $user, TestPaper $paper): int
    {
        return $this->createQueryBuilder('ts')
            ->select('COUNT(ts.id)')
            ->andWhere('ts.user = :user')
            ->andWhere('ts.paper = :paper')
            ->setParameter('user', $user)
            ->setParameter('paper', $paper)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getBestScore(UserInterface $user, TestPaper $paper): ?int
    {
        $result = $this->createQueryBuilder('ts')
            ->select('MAX(ts.score)')
            ->andWhere('ts.user = :user')
            ->andWhere('ts.paper = :paper')
            ->andWhere('ts.status = :status')
            ->setParameter('user', $user)
            ->setParameter('paper', $paper)
            ->setParameter('status', SessionStatus::COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : null;
    }

    public function getStatisticsByStatus(): array
    {
        $result = $this->createQueryBuilder('ts')
            ->select('ts.status, COUNT(ts.id) as count')
            ->groupBy('ts.status')
            ->getQuery()
            ->getResult();

        $statistics = [];
        foreach ($result as $row) {
            $statistics[$row['status']] = (int) $row['count'];
        }

        return $statistics;
    }

    public function getRecentSessions(int $limit = 10): array
    {
        return $this->createQueryBuilder('ts')
            ->orderBy('ts.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findCompletedByUser(UserInterface $user, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('ts')
            ->andWhere('ts.user = :user')
            ->andWhere('ts.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', SessionStatus::COMPLETED)
            ->orderBy('ts.endTime', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function getPaperStatistics(TestPaper $paper): array
    {
        $totalSessions = $this->count(['paper' => $paper]);

        $completedSessions = $this->count([
            'paper' => $paper,
            'status' => SessionStatus::COMPLETED
        ]);

        $avgScore = $this->getAverageScore($paper);
        $passRate = $this->getPassRate($paper);
        $scoreDistribution = $this->getScoreDistribution($paper);

        $highestScore = $this->createQueryBuilder('ts')
            ->select('MAX(ts.score)')
            ->andWhere('ts.paper = :paper')
            ->andWhere('ts.status = :status')
            ->setParameter('paper', $paper)
            ->setParameter('status', SessionStatus::COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();

        $lowestScore = $this->createQueryBuilder('ts')
            ->select('MIN(ts.score)')
            ->andWhere('ts.paper = :paper')
            ->andWhere('ts.status = :status')
            ->setParameter('paper', $paper)
            ->setParameter('status', SessionStatus::COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'totalSessions' => $totalSessions,
            'completedSessions' => $completedSessions,
            'averageScore' => $avgScore,
            'highestScore' => $highestScore ? (int) $highestScore : null,
            'lowestScore' => $lowestScore ? (int) $lowestScore : null,
            'passRate' => $passRate,
            'scoreDistribution' => $scoreDistribution,
        ];
    }

    public function getAverageScore(TestPaper $paper): ?float
    {
        $result = $this->createQueryBuilder('ts')
            ->select('AVG(ts.score)')
            ->andWhere('ts.paper = :paper')
            ->andWhere('ts.status = :status')
            ->andWhere('ts.score IS NOT NULL')
            ->setParameter('paper', $paper)
            ->setParameter('status', SessionStatus::COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? round((float) $result, 2) : null;
    }

    public function getPassRate(TestPaper $paper): ?float
    {
        $total = $this->createQueryBuilder('ts')
            ->select('COUNT(ts.id)')
            ->andWhere('ts.paper = :paper')
            ->andWhere('ts.status = :status')
            ->setParameter('paper', $paper)
            ->setParameter('status', SessionStatus::COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();

        if (!$total) {
            return null;
        }

        $passed = $this->createQueryBuilder('ts')
            ->select('COUNT(ts.id)')
            ->andWhere('ts.paper = :paper')
            ->andWhere('ts.status = :status')
            ->andWhere('ts.passed = true')
            ->setParameter('paper', $paper)
            ->setParameter('status', SessionStatus::COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();

        return round(($passed / $total) * 100, 2);
    }

    public function getScoreDistribution(TestPaper $paper): array
    {
        $result = $this->createQueryBuilder('ts')
            ->select('
                SUM(CASE WHEN ts.score >= :excellent THEN 1 ELSE 0 END) as excellent,
                SUM(CASE WHEN ts.score >= :good AND ts.score < :excellent THEN 1 ELSE 0 END) as good,
                SUM(CASE WHEN ts.score >= :pass AND ts.score < :good THEN 1 ELSE 0 END) as pass,
                SUM(CASE WHEN ts.score < :pass THEN 1 ELSE 0 END) as fail
            ')
            ->andWhere('ts.paper = :paper')
            ->andWhere('ts.status = :status')
            ->andWhere('ts.score IS NOT NULL')
            ->setParameter('paper', $paper)
            ->setParameter('status', SessionStatus::COMPLETED)
            ->setParameter('excellent', $paper->getTotalScore() * 0.9)
            ->setParameter('good', $paper->getTotalScore() * 0.8)
            ->setParameter('pass', $paper->getPassScore())
            ->getQuery()
            ->getSingleResult();

        return [
            'excellent' => (int) $result['excellent'],
            'good' => (int) $result['good'],
            'pass' => (int) $result['pass'],
            'fail' => (int) $result['fail'],
        ];
    }

    public function getUserTestHistory(UserInterface $user, $questionBank = null): array
    {
        $qb = $this->createQueryBuilder('ts')
            ->select('ts', 'p')
            ->join('ts.paper', 'p')
            ->andWhere('ts.user = :user')
            ->andWhere('ts.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', SessionStatus::COMPLETED);

        if ($questionBank) {
            $qb->andWhere('p.questionBank = :questionBank')
               ->setParameter('questionBank', $questionBank);
        }

        return $qb->orderBy('ts.endTime', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}