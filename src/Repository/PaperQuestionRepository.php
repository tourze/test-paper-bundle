<?php

namespace Tourze\TestPaperBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TestPaperBundle\Entity\PaperQuestion;
use Tourze\TestPaperBundle\Entity\TestPaper;

/**
 * @extends ServiceEntityRepository<PaperQuestion>
 */
#[AsRepository(entityClass: PaperQuestion::class)]
class PaperQuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaperQuestion::class);
    }

    /**
     * @return PaperQuestion[]
     * @phpstan-return array<PaperQuestion>
     */
    public function findByPaper(TestPaper $paper): array
    {
        /** @var PaperQuestion[] */
        return $this->createQueryBuilder('pq')
            ->andWhere('pq.paper = :paper')
            ->setParameter('paper', $paper)
            ->orderBy('pq.sortOrder', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return PaperQuestion[]
     * @phpstan-return array<PaperQuestion>
     */
    public function findByPaperWithQuestions(TestPaper $paper): array
    {
        /** @var PaperQuestion[] */
        return $this->createQueryBuilder('pq')
            ->select('pq, q')
            ->join('pq.question', 'q')
            ->andWhere('pq.paper = :paper')
            ->setParameter('paper', $paper)
            ->orderBy('pq.sortOrder', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getTotalScore(TestPaper $paper): int
    {
        $result = $this->createQueryBuilder('pq')
            ->select('SUM(pq.score) as totalScore')
            ->andWhere('pq.paper = :paper')
            ->setParameter('paper', $paper)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) ($result ?? 0);
    }

    public function getQuestionCount(TestPaper $paper): int
    {
        $result = $this->createQueryBuilder('pq')
            ->select('COUNT(pq.id)')
            ->andWhere('pq.paper = :paper')
            ->setParameter('paper', $paper)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
    }

    public function getRequiredQuestionCount(TestPaper $paper): int
    {
        $result = $this->createQueryBuilder('pq')
            ->select('COUNT(pq.id)')
            ->andWhere('pq.paper = :paper')
            ->andWhere('pq.isRequired = true')
            ->setParameter('paper', $paper)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
    }

    /**
     * @param string[] $questionIds
     */
    public function reorderQuestions(TestPaper $paper, array $questionIds): void
    {
        foreach ($questionIds as $index => $questionId) {
            $this->createQueryBuilder('pq')
                ->update()
                ->set('pq.sortOrder', ':sortOrder')
                ->andWhere('pq.paper = :paper')
                ->andWhere('pq.id = :questionId')
                ->setParameter('sortOrder', $index + 1)
                ->setParameter('paper', $paper)
                ->setParameter('questionId', $questionId)
                ->getQuery()
                ->execute()
            ;
        }
    }

    /**
     * @return array<int|string, array{count: int, totalScore: int}>
     */
    public function getStatisticsByType(TestPaper $paper): array
    {
        /** @var array<array{type: mixed, count: mixed, totalScore: mixed}> $result */
        $result = $this->createQueryBuilder('pq')
            ->select('q.type, COUNT(pq.id) as count, SUM(pq.score) as totalScore')
            ->join('pq.question', 'q')
            ->andWhere('pq.paper = :paper')
            ->setParameter('paper', $paper)
            ->groupBy('q.type')
            ->getQuery()
            ->getResult()
        ;

        $statistics = [];
        foreach ($result as $row) {
            $typeValue = $row['type'] ?? null;
            $typeKey = '';
            if ($typeValue instanceof \BackedEnum) {
                $typeKey = (string) $typeValue->value;
            } elseif (is_string($typeValue)) {
                $typeKey = $typeValue;
            } elseif (null !== $typeValue) {
                /** @var string $typeKey */
                $typeKey = (string) $typeValue;
            }

            $count = $row['count'] ?? 0;
            $totalScore = $row['totalScore'] ?? 0;

            $statistics[$typeKey] = [
                'count' => is_numeric($count) ? (int) $count : 0,
                'totalScore' => is_numeric($totalScore) ? (int) $totalScore : 0,
            ];
        }

        return $statistics;
    }

    /**
     * @return array<string, array{count: int, totalScore: int}>
     */
    public function getStatisticsByDifficulty(TestPaper $paper): array
    {
        /** @var array<array{difficulty: mixed, count: mixed, totalScore: mixed}> $result */
        $result = $this->createQueryBuilder('pq')
            ->select('q.difficulty, COUNT(pq.id) as count, SUM(pq.score) as totalScore')
            ->join('pq.question', 'q')
            ->andWhere('pq.paper = :paper')
            ->setParameter('paper', $paper)
            ->groupBy('q.difficulty')
            ->getQuery()
            ->getResult()
        ;

        $statistics = [];
        foreach ($result as $row) {
            $difficultyValue = $row['difficulty'] ?? '';
            /** @var string $difficultyKey */
            $difficultyKey = is_string($difficultyValue) ? $difficultyValue : (string) $difficultyValue;

            $count = $row['count'] ?? 0;
            $totalScore = $row['totalScore'] ?? 0;

            $statistics[$difficultyKey] = [
                'count' => is_numeric($count) ? (int) $count : 0,
                'totalScore' => is_numeric($totalScore) ? (int) $totalScore : 0,
            ];
        }

        return $statistics;
    }

    public function save(PaperQuestion $entity, bool $flush = true): void
    {
        static::getEntityManager()->persist($entity);

        if ($flush) {
            static::getEntityManager()->flush();
        }
    }

    public function remove(PaperQuestion $entity, bool $flush = true): void
    {
        static::getEntityManager()->remove($entity);

        if ($flush) {
            static::getEntityManager()->flush();
        }
    }
}
