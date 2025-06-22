<?php

namespace Tourze\TestPaperBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Enum\PaperStatus;

/**
 * @extends ServiceEntityRepository<TestPaper>
 */
class TestPaperRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestPaper::class);
    }

    public function findPublished(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', PaperStatus::PUBLISHED)
            ->orderBy('p.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCreator(string $createdBy, ?PaperStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.createdBy = :createdBy')
            ->setParameter('createdBy', $createdBy);

        if ($status !== null) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->orderBy('p.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findWithStatistics(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p', 'COUNT(s.id) as sessionCount', 'AVG(s.score) as avgScore')
            ->leftJoin('p.sessions', 's')
            ->groupBy('p.id')
            ->orderBy('p.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function searchByKeyword(string $keyword): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.title LIKE :keyword OR p.description LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('p.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentlyCreated(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findPopular(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->select('p', 'COUNT(s.id) as sessionCount')
            ->leftJoin('p.sessions', 's')
            ->groupBy('p.id')
            ->orderBy('sessionCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getStatisticsByStatus(): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('p.status, COUNT(p.id) as count')
            ->groupBy('p.status')
            ->getQuery()
            ->getResult();

        $statistics = [];
        foreach ($result as $row) {
            $statistics[$row['status']] = (int) $row['count'];
        }

        return $statistics;
    }

    public function getStatisticsByGenerationType(): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('p.generationType, COUNT(p.id) as count')
            ->groupBy('p.generationType')
            ->getQuery()
            ->getResult();

        $statistics = [];
        foreach ($result as $row) {
            $statistics[$row['generationType']] = (int) $row['count'];
        }

        return $statistics;
    }
}