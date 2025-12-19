<?php

namespace Tourze\TestPaperBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TestPaperBundle\Entity\PaperTemplate;
use Tourze\TestPaperBundle\Entity\TemplateRule;

/**
 * @extends ServiceEntityRepository<TemplateRule>
 */
#[AsRepository(entityClass: TemplateRule::class)]
class TemplateRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TemplateRule::class);
    }

    /**
     * @return TemplateRule[]
     * @phpstan-return array<TemplateRule>
     */
    public function findByTemplate(PaperTemplate $template): array
    {
        /** @var TemplateRule[] */
        return $this->createQueryBuilder('tr')
            ->andWhere('tr.template = :template')
            ->setParameter('template', $template)
            ->orderBy('tr.sort', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getTotalQuestions(PaperTemplate $template): int
    {
        $result = $this->createQueryBuilder('tr')
            ->select('SUM(tr.questionCount) as totalQuestions')
            ->andWhere('tr.template = :template')
            ->setParameter('template', $template)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) ($result ?? 0);
    }

    public function getTotalScore(PaperTemplate $template): int
    {
        $result = $this->createQueryBuilder('tr')
            ->select('SUM(tr.questionCount * tr.scorePerQuestion) as totalScore')
            ->andWhere('tr.template = :template')
            ->setParameter('template', $template)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) ($result ?? 0);
    }

    /**
     * @param string[] $ruleIds
     */
    public function reorderRules(PaperTemplate $template, array $ruleIds): void
    {
        foreach ($ruleIds as $index => $ruleId) {
            $this->createQueryBuilder('tr')
                ->update()
                ->set('tr.sort', ':sort')
                ->andWhere('tr.template = :template')
                ->andWhere('tr.id = :ruleId')
                ->setParameter('sort', $index + 1)
                ->setParameter('template', $template)
                ->setParameter('ruleId', $ruleId)
                ->getQuery()
                ->execute()
            ;
        }
    }

    /**
     * @return array<string, array{count: int, totalScore: int}>
     */
    public function getStatisticsByType(PaperTemplate $template): array
    {
        /** @var array<array{questionType: mixed, count: mixed, totalScore: mixed}> $result */
        $result = $this->createQueryBuilder('tr')
            ->select('tr.questionType, SUM(tr.questionCount) as count, SUM(tr.questionCount * tr.scorePerQuestion) as totalScore')
            ->andWhere('tr.template = :template')
            ->andWhere('tr.questionType IS NOT NULL')
            ->setParameter('template', $template)
            ->groupBy('tr.questionType')
            ->getQuery()
            ->getResult()
        ;

        $statistics = [];
        foreach ($result as $row) {
            $questionTypeValue = $row['questionType'] ?? '';
            /** @var string $questionType */
            $questionType = is_string($questionTypeValue) ? $questionTypeValue : (string) $questionTypeValue;
            $count = is_numeric($row['count'] ?? 0) ? (int) ($row['count'] ?? 0) : 0;
            $totalScore = is_numeric($row['totalScore'] ?? 0) ? (int) ($row['totalScore'] ?? 0) : 0;

            $statistics[$questionType] = [
                'count' => $count,
                'totalScore' => $totalScore,
            ];
        }

        return $statistics;
    }

    /**
     * @return array<string, array{count: int, totalScore: int}>
     */
    public function getStatisticsByDifficulty(PaperTemplate $template): array
    {
        /** @var array<array{difficulty: mixed, count: mixed, totalScore: mixed}> $result */
        $result = $this->createQueryBuilder('tr')
            ->select('tr.difficulty, SUM(tr.questionCount) as count, SUM(tr.questionCount * tr.scorePerQuestion) as totalScore')
            ->andWhere('tr.template = :template')
            ->andWhere('tr.difficulty IS NOT NULL')
            ->setParameter('template', $template)
            ->groupBy('tr.difficulty')
            ->getQuery()
            ->getResult()
        ;

        $statistics = [];
        foreach ($result as $row) {
            $difficultyValue = $row['difficulty'] ?? '';
            /** @var string $difficulty */
            $difficulty = is_string($difficultyValue) ? $difficultyValue : (string) $difficultyValue;
            $count = is_numeric($row['count'] ?? 0) ? (int) ($row['count'] ?? 0) : 0;
            $totalScore = is_numeric($row['totalScore'] ?? 0) ? (int) ($row['totalScore'] ?? 0) : 0;

            $statistics[$difficulty] = [
                'count' => $count,
                'totalScore' => $totalScore,
            ];
        }

        return $statistics;
    }

    /**
     * @return array<string, array{count: int, totalScore: int}>
     */
    public function getStatisticsByKnowledgePoint(PaperTemplate $template): array
    {
        /** @var array<array{name: mixed, count: mixed, totalScore: mixed}> $result */
        $result = $this->createQueryBuilder('tr')
            ->select('kp.name, SUM(tr.questionCount) as count, SUM(tr.questionCount * tr.scorePerQuestion) as totalScore')
            ->join('tr.knowledgePoint', 'kp')
            ->andWhere('tr.template = :template')
            ->andWhere('tr.knowledgePoint IS NOT NULL')
            ->setParameter('template', $template)
            ->groupBy('kp.id')
            ->getQuery()
            ->getResult()
        ;

        $statistics = [];
        foreach ($result as $row) {
            $nameValue = $row['name'] ?? '';
            /** @var string $name */
            $name = is_string($nameValue) ? $nameValue : (string) $nameValue;
            $count = is_numeric($row['count'] ?? 0) ? (int) ($row['count'] ?? 0) : 0;
            $totalScore = is_numeric($row['totalScore'] ?? 0) ? (int) ($row['totalScore'] ?? 0) : 0;

            $statistics[$name] = [
                'count' => $count,
                'totalScore' => $totalScore,
            ];
        }

        return $statistics;
    }

    public function save(TemplateRule $entity, bool $flush = true): void
    {
        static::getEntityManager()->persist($entity);

        if ($flush) {
            static::getEntityManager()->flush();
        }
    }

    public function remove(TemplateRule $entity, bool $flush = true): void
    {
        static::getEntityManager()->remove($entity);

        if ($flush) {
            static::getEntityManager()->flush();
        }
    }
}
