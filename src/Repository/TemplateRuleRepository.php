<?php

namespace Tourze\TestPaperBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\TestPaperBundle\Entity\PaperTemplate;
use Tourze\TestPaperBundle\Entity\TemplateRule;

/**
 * @extends ServiceEntityRepository<TemplateRule>
 */
class TemplateRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TemplateRule::class);
    }

    public function findByTemplate(PaperTemplate $template): array
    {
        return $this->createQueryBuilder('tr')
            ->andWhere('tr.template = :template')
            ->setParameter('template', $template)
            ->orderBy('tr.sort', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalQuestions(PaperTemplate $template): int
    {
        $result = $this->createQueryBuilder('tr')
            ->select('SUM(tr.questionCount) as totalQuestions')
            ->andWhere('tr.template = :template')
            ->setParameter('template', $template)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    public function getTotalScore(PaperTemplate $template): int
    {
        $result = $this->createQueryBuilder('tr')
            ->select('SUM(tr.questionCount * tr.scorePerQuestion) as totalScore')
            ->andWhere('tr.template = :template')
            ->setParameter('template', $template)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

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
                ->execute();
        }
    }

    public function getStatisticsByType(PaperTemplate $template): array
    {
        $result = $this->createQueryBuilder('tr')
            ->select('tr.questionType, SUM(tr.questionCount) as count, SUM(tr.questionCount * tr.scorePerQuestion) as totalScore')
            ->andWhere('tr.template = :template')
            ->andWhere('tr.questionType IS NOT NULL')
            ->setParameter('template', $template)
            ->groupBy('tr.questionType')
            ->getQuery()
            ->getResult();

        $statistics = [];
        foreach ($result as $row) {
            $statistics[$row['questionType']] = [
                'count' => (int) $row['count'],
                'totalScore' => (int) $row['totalScore'],
            ];
        }

        return $statistics;
    }

    public function getStatisticsByDifficulty(PaperTemplate $template): array
    {
        $result = $this->createQueryBuilder('tr')
            ->select('tr.difficulty, SUM(tr.questionCount) as count, SUM(tr.questionCount * tr.scorePerQuestion) as totalScore')
            ->andWhere('tr.template = :template')
            ->andWhere('tr.difficulty IS NOT NULL')
            ->setParameter('template', $template)
            ->groupBy('tr.difficulty')
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

    public function getStatisticsByKnowledgePoint(PaperTemplate $template): array
    {
        $result = $this->createQueryBuilder('tr')
            ->select('kp.name, SUM(tr.questionCount) as count, SUM(tr.questionCount * tr.scorePerQuestion) as totalScore')
            ->join('tr.knowledgePoint', 'kp')
            ->andWhere('tr.template = :template')
            ->andWhere('tr.knowledgePoint IS NOT NULL')
            ->setParameter('template', $template)
            ->groupBy('kp.id')
            ->getQuery()
            ->getResult();

        $statistics = [];
        foreach ($result as $row) {
            $statistics[$row['name']] = [
                'count' => (int) $row['count'],
                'totalScore' => (int) $row['totalScore'],
            ];
        }

        return $statistics;
    }
}