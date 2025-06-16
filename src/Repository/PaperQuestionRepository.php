<?php

namespace Tourze\TestPaperBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\TestPaperBundle\Entity\PaperQuestion;
use Tourze\TestPaperBundle\Entity\TestPaper;

/**
 * @extends ServiceEntityRepository<PaperQuestion>
 */
class PaperQuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaperQuestion::class);
    }

    public function findByPaper(TestPaper $paper): array
    {
        return $this->createQueryBuilder('pq')
            ->andWhere('pq.paper = :paper')
            ->setParameter('paper', $paper)
            ->orderBy('pq.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByPaperWithQuestions(TestPaper $paper): array
    {
        return $this->createQueryBuilder('pq')
            ->select('pq, q')
            ->join('pq.question', 'q')
            ->andWhere('pq.paper = :paper')
            ->setParameter('paper', $paper)
            ->orderBy('pq.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalScore(TestPaper $paper): int
    {
        $result = $this->createQueryBuilder('pq')
            ->select('SUM(pq.score) as totalScore')
            ->andWhere('pq.paper = :paper')
            ->setParameter('paper', $paper)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    public function getQuestionCount(TestPaper $paper): int
    {
        return $this->createQueryBuilder('pq')
            ->select('COUNT(pq.id)')
            ->andWhere('pq.paper = :paper')
            ->setParameter('paper', $paper)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getRequiredQuestionCount(TestPaper $paper): int
    {
        return $this->createQueryBuilder('pq')
            ->select('COUNT(pq.id)')
            ->andWhere('pq.paper = :paper')
            ->andWhere('pq.isRequired = true')
            ->setParameter('paper', $paper)
            ->getQuery()
            ->getSingleScalarResult();
    }

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
                ->execute();
        }
    }

    public function getStatisticsByType(TestPaper $paper): array
    {
        $result = $this->createQueryBuilder('pq')
            ->select('q.type, COUNT(pq.id) as count, SUM(pq.score) as totalScore')
            ->join('pq.question', 'q')
            ->andWhere('pq.paper = :paper')
            ->setParameter('paper', $paper)
            ->groupBy('q.type')
            ->getQuery()
            ->getResult();

        $statistics = [];
        foreach ($result as $row) {
            $statistics[$row['type']] = [
                'count' => (int) $row['count'],
                'totalScore' => (int) $row['totalScore'],
            ];
        }

        return $statistics;
    }

    public function getStatisticsByDifficulty(TestPaper $paper): array
    {
        $result = $this->createQueryBuilder('pq')
            ->select('q.difficulty, COUNT(pq.id) as count, SUM(pq.score) as totalScore')
            ->join('pq.question', 'q')
            ->andWhere('pq.paper = :paper')
            ->setParameter('paper', $paper)
            ->groupBy('q.difficulty')
            ->getQuery()
            ->getResult();

        $statistics = [];
        foreach ($result as $row) {
            $statistics[$row['difficulty']] = [
                'count' => (int) $row['count'],
                'totalScore' => (int) $row['totalScore'],
            ];
        }

        return $statistics;
    }
}