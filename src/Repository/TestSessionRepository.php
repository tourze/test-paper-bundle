<?php

namespace Tourze\TestPaperBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Entity\TestSession;
use Tourze\TestPaperBundle\Enum\SessionStatus;

/**
 * @extends ServiceEntityRepository<TestSession>
 */
#[AsRepository(entityClass: TestSession::class)]
class TestSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestSession::class);
    }

    /**
     * @return TestSession[]
     * @phpstan-return array<TestSession>
     */
    public function findByUser(UserInterface $user, ?SessionStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('ts')
            ->andWhere('ts.user = :user')
            ->setParameter('user', $user)
        ;

        if (null !== $status) {
            $qb->andWhere('ts.status = :status')
                ->setParameter('status', $status)
            ;
        }

        /** @var TestSession[] */
        return $qb->orderBy('ts.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return TestSession[]
     * @phpstan-return array<TestSession>
     */
    public function findByPaper(TestPaper $paper, ?SessionStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('ts')
            ->andWhere('ts.paper = :paper')
            ->setParameter('paper', $paper)
        ;

        if (null !== $status) {
            $qb->andWhere('ts.status = :status')
                ->setParameter('status', $status)
            ;
        }

        /** @var TestSession[] */
        return $qb->orderBy('ts.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findActiveSession(UserInterface $user, TestPaper $paper): ?TestSession
    {
        /** @var TestSession|null */
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
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return TestSession[]
     * @phpstan-return array<TestSession>
     */
    public function findExpiredSessions(): array
    {
        /** @var TestSession[] */
        return $this->createQueryBuilder('ts')
            ->andWhere('ts.status = :status')
            ->andWhere('ts.expiresAt < :now')
            ->setParameter('status', SessionStatus::IN_PROGRESS)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult()
        ;
    }

    public function getUserAttemptCount(UserInterface $user, TestPaper $paper): int
    {
        $result = $this->createQueryBuilder('ts')
            ->select('COUNT(ts.id)')
            ->andWhere('ts.user = :user')
            ->andWhere('ts.paper = :paper')
            ->setParameter('user', $user)
            ->setParameter('paper', $paper)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
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
            ->getSingleScalarResult()
        ;

        return null !== $result ? (int) $result : null;
    }

    /**
     * @return array<string, int>
     */
    public function getStatisticsByStatus(): array
    {
        $conn = static::getEntityManager()->getConnection();
        $sql = 'SELECT status, COUNT(id) as count FROM test_session GROUP BY status';
        $stmt = $conn->executeQuery($sql);
        $result = $stmt->fetchAllAssociative();

        $statistics = [];
        foreach ($result as $row) {
            $status = $row['status'] ?? '';
            /** @var string $statusKey */
            $statusKey = is_string($status) ? $status : (string) $status;
            $count = $row['count'] ?? 0;

            $statistics[$statusKey] = is_numeric($count) ? (int) $count : 0;
        }

        return $statistics;
    }

    /**
     * @return TestSession[]
     * @phpstan-return array<TestSession>
     */
    public function getRecentSessions(int $limit = 10): array
    {
        /** @var TestSession[] */
        return $this->createQueryBuilder('ts')
            ->orderBy('ts.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return TestSession[]
     * @phpstan-return array<TestSession>
     */
    public function findCompletedByUser(UserInterface $user, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('ts')
            ->andWhere('ts.user = :user')
            ->andWhere('ts.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', SessionStatus::COMPLETED)
            ->orderBy('ts.endTime', 'DESC')
        ;

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        /** @var TestSession[] */
        return $qb->getQuery()->getResult();
    }

    /**
     * @return array{totalSessions: int, completedSessions: int, averageScore: float|null, highestScore: int|null, lowestScore: int|null, passRate: float|null, scoreDistribution: array{excellent: int, good: int, pass: int, fail: int}}
     */
    public function getPaperStatistics(TestPaper $paper): array
    {
        $totalSessions = $this->count(['paper' => $paper]);

        $completedSessions = $this->count([
            'paper' => $paper,
            'status' => SessionStatus::COMPLETED,
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
            ->getSingleScalarResult()
        ;

        $lowestScore = $this->createQueryBuilder('ts')
            ->select('MIN(ts.score)')
            ->andWhere('ts.paper = :paper')
            ->andWhere('ts.status = :status')
            ->setParameter('paper', $paper)
            ->setParameter('status', SessionStatus::COMPLETED)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return [
            'totalSessions' => $totalSessions,
            'completedSessions' => $completedSessions,
            'averageScore' => $avgScore,
            'highestScore' => null !== $highestScore ? (int) $highestScore : null,
            'lowestScore' => null !== $lowestScore ? (int) $lowestScore : null,
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
            ->getSingleScalarResult()
        ;

        return null !== $result ? round((float) $result, 2) : null;
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
            ->getSingleScalarResult()
        ;

        if (0 === (int) $total) {
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
            ->getSingleScalarResult()
        ;

        return round(((int) $passed / (int) $total) * 100, 2);
    }

    /**
     * @return array{excellent: int, good: int, pass: int, fail: int}
     */
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
            ->getSingleResult()
        ;

        if (!is_array($result)) {
            return [
                'excellent' => 0,
                'good' => 0,
                'pass' => 0,
                'fail' => 0,
            ];
        }

        return [
            'excellent' => is_numeric($result['excellent'] ?? 0) ? (int) ($result['excellent'] ?? 0) : 0,
            'good' => is_numeric($result['good'] ?? 0) ? (int) ($result['good'] ?? 0) : 0,
            'pass' => is_numeric($result['pass'] ?? 0) ? (int) ($result['pass'] ?? 0) : 0,
            'fail' => is_numeric($result['fail'] ?? 0) ? (int) ($result['fail'] ?? 0) : 0,
        ];
    }

    /**
     * @param mixed|null $questionBank
     * @return TestSession[]
     * @phpstan-return array<TestSession>
     */
    public function getUserTestHistory(UserInterface $user, $questionBank = null): array
    {
        $qb = $this->createQueryBuilder('ts')
            ->select('ts', 'p')
            ->join('ts.paper', 'p')
            ->andWhere('ts.user = :user')
            ->andWhere('ts.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', SessionStatus::COMPLETED)
        ;

        if (null !== $questionBank) {
            $qb->andWhere('p.questionBank = :questionBank')
                ->setParameter('questionBank', $questionBank)
            ;
        }

        /** @var TestSession[] */
        return $qb->orderBy('ts.endTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(TestSession $entity, bool $flush = true): void
    {
        static::getEntityManager()->persist($entity);

        if ($flush) {
            static::getEntityManager()->flush();
        }
    }

    public function remove(TestSession $entity, bool $flush = true): void
    {
        static::getEntityManager()->remove($entity);

        if ($flush) {
            static::getEntityManager()->flush();
        }
    }
}
