<?php

namespace Tourze\TestPaperBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Enum\PaperGenerationType;
use Tourze\TestPaperBundle\Enum\PaperStatus;

/**
 * @extends ServiceEntityRepository<TestPaper>
 */
#[AsRepository(entityClass: TestPaper::class)]
class TestPaperRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestPaper::class);
    }

    /**
     * @return TestPaper[]
     * @phpstan-return array<TestPaper>
     */
    public function findPublished(): array
    {
        /** @var TestPaper[] */
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', PaperStatus::PUBLISHED)
            ->orderBy('p.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return TestPaper[]
     * @phpstan-return array<TestPaper>
     */
    public function findByCreator(string $createdBy, ?PaperStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.createdBy = :createdBy')
            ->setParameter('createdBy', $createdBy)
        ;

        if (null !== $status) {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $status)
            ;
        }

        /** @var TestPaper[] */
        return $qb->orderBy('p.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<int, array{0: TestPaper, sessionCount: string, avgScore: string|null}>
     * @phpstan-return array<int, array{0: TestPaper, sessionCount: string, avgScore: string|null}>
     */
    public function findWithStatistics(): array
    {
        /** @var array<int, array{0: TestPaper, sessionCount: string, avgScore: string|null}> */
        return $this->createQueryBuilder('p')
            ->select('p', 'COUNT(s.id) as sessionCount', 'AVG(s.score) as avgScore')
            ->leftJoin('p.sessions', 's')
            ->groupBy('p.id')
            ->orderBy('p.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return TestPaper[]
     * @phpstan-return array<TestPaper>
     */
    public function searchByKeyword(string $keyword): array
    {
        /** @var TestPaper[] */
        return $this->createQueryBuilder('p')
            ->andWhere('p.title LIKE :keyword OR p.description LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('p.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return TestPaper[]
     * @phpstan-return array<TestPaper>
     */
    public function findRecentlyCreated(int $limit = 10): array
    {
        /** @var TestPaper[] */
        return $this->createQueryBuilder('p')
            ->orderBy('p.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<int, array{0: TestPaper, sessionCount: string}>
     * @phpstan-return array<int, array{0: TestPaper, sessionCount: string}>
     */
    public function findPopular(int $limit = 10): array
    {
        /** @var array<int, array{0: TestPaper, sessionCount: string}> */
        return $this->createQueryBuilder('p')
            ->select('p', 'COUNT(s.id) as sessionCount')
            ->leftJoin('p.sessions', 's')
            ->groupBy('p.id')
            ->orderBy('sessionCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<string, int>
     */
    public function getStatisticsByStatus(): array
    {
        /** @var array<array{status: mixed, count: mixed}> $result */
        $result = $this->createQueryBuilder('p')
            ->select('p.status, COUNT(p.id) as count')
            ->groupBy('p.status')
            ->getQuery()
            ->getResult()
        ;

        $statistics = [];
        foreach ($result as $row) {
            $status = $row['status'] ?? null;
            /** @var string $statusValue */
            $statusValue = $status instanceof PaperStatus ? $status->value : (string) $status;
            $count = is_numeric($row['count'] ?? 0) ? (int) ($row['count'] ?? 0) : 0;
            $statistics[$statusValue] = $count;
        }

        return $statistics;
    }

    /**
     * @return array<string, int>
     */
    public function getStatisticsByGenerationType(): array
    {
        /** @var array<array{generationType: mixed, count: mixed}> $result */
        $result = $this->createQueryBuilder('p')
            ->select('p.generationType, COUNT(p.id) as count')
            ->groupBy('p.generationType')
            ->getQuery()
            ->getResult()
        ;

        $statistics = [];
        foreach ($result as $row) {
            $generationType = $row['generationType'] ?? null;
            /** @var string $typeValue */
            $typeValue = $generationType instanceof PaperGenerationType ? $generationType->value : (string) $generationType;
            $count = is_numeric($row['count'] ?? 0) ? (int) ($row['count'] ?? 0) : 0;
            $statistics[$typeValue] = $count;
        }

        return $statistics;
    }

    public function save(TestPaper $entity, bool $flush = true): void
    {
        static::getEntityManager()->persist($entity);

        if ($flush) {
            static::getEntityManager()->flush();
        }
    }

    public function remove(TestPaper $entity, bool $flush = true): void
    {
        static::getEntityManager()->remove($entity);

        if ($flush) {
            static::getEntityManager()->flush();
        }
    }
}
