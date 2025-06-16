<?php

namespace Tourze\TestPaperBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\TestPaperBundle\Entity\PaperTemplate;

/**
 * @extends ServiceEntityRepository<PaperTemplate>
 */
class PaperTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaperTemplate::class);
    }

    public function findActive(): array
    {
        return $this->createQueryBuilder('pt')
            ->andWhere('pt.isActive = true')
            ->orderBy('pt.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCreator(string $createdBy): array
    {
        return $this->createQueryBuilder('pt')
            ->andWhere('pt.createdBy = :createdBy')
            ->setParameter('createdBy', $createdBy)
            ->orderBy('pt.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function searchByKeyword(string $keyword): array
    {
        return $this->createQueryBuilder('pt')
            ->andWhere('pt.name LIKE :keyword OR pt.description LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('pt.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findWithRuleCount(): array
    {
        return $this->createQueryBuilder('pt')
            ->select('pt', 'COUNT(tr.id) as ruleCount')
            ->leftJoin('pt.rules', 'tr')
            ->groupBy('pt.id')
            ->orderBy('pt.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getStatistics(): array
    {
        $result = $this->createQueryBuilder('pt')
            ->select('COUNT(pt.id) as totalTemplates')
            ->addSelect('COUNT(CASE WHEN pt.isActive = true THEN 1 END) as activeTemplates')
            ->getQuery()
            ->getSingleResult();

        return [
            'total' => (int) $result['totalTemplates'],
            'active' => (int) $result['activeTemplates'],
            'inactive' => (int) $result['totalTemplates'] - (int) $result['activeTemplates'],
        ];
    }
}