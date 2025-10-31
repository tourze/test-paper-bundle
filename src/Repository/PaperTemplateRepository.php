<?php

namespace Tourze\TestPaperBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TestPaperBundle\Entity\PaperTemplate;

/**
 * @extends ServiceEntityRepository<PaperTemplate>
 */
#[AsRepository(entityClass: PaperTemplate::class)]
class PaperTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaperTemplate::class);
    }

    /**
     * @return PaperTemplate[]
     * @phpstan-return array<PaperTemplate>
     */
    public function findActive(): array
    {
        /** @var PaperTemplate[] */
        return $this->createQueryBuilder('pt')
            ->andWhere('pt.isActive = true')
            ->orderBy('pt.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return PaperTemplate[]
     * @phpstan-return array<PaperTemplate>
     */
    public function findByCreator(string $createdBy): array
    {
        /** @var PaperTemplate[] */
        return $this->createQueryBuilder('pt')
            ->andWhere('pt.createdBy = :createdBy')
            ->setParameter('createdBy', $createdBy)
            ->orderBy('pt.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return PaperTemplate[]
     * @phpstan-return array<PaperTemplate>
     */
    public function searchByKeyword(string $keyword): array
    {
        /** @var PaperTemplate[] */
        return $this->createQueryBuilder('pt')
            ->andWhere('pt.name LIKE :keyword OR pt.description LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('pt.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<int, array{0: PaperTemplate, ruleCount: string}>
     * @phpstan-return array<int, array{0: PaperTemplate, ruleCount: string}>
     */
    public function findWithRuleCount(): array
    {
        /** @var array<int, array{0: PaperTemplate, ruleCount: string}> */
        return $this->createQueryBuilder('pt')
            ->select('pt', 'COUNT(tr.id) as ruleCount')
            ->leftJoin('pt.rules', 'tr')
            ->groupBy('pt.id')
            ->orderBy('pt.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array{total: int, active: int, inactive: int}
     */
    public function getStatistics(): array
    {
        $result = $this->createQueryBuilder('pt')
            ->select('COUNT(pt.id) as totalTemplates')
            ->addSelect('SUM(CASE WHEN pt.isActive = true THEN 1 ELSE 0 END) as activeTemplates')
            ->getQuery()
            ->getSingleResult()
        ;

        if (!is_array($result)) {
            return [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
            ];
        }

        $totalTemplates = is_numeric($result['totalTemplates'] ?? 0) ? (int) ($result['totalTemplates'] ?? 0) : 0;
        $activeTemplates = is_numeric($result['activeTemplates'] ?? 0) ? (int) ($result['activeTemplates'] ?? 0) : 0;

        return [
            'total' => $totalTemplates,
            'active' => $activeTemplates,
            'inactive' => $totalTemplates - $activeTemplates,
        ];
    }

    public function save(PaperTemplate $entity, bool $flush = true): void
    {
        static::getEntityManager()->persist($entity);

        if ($flush) {
            static::getEntityManager()->flush();
        }
    }

    public function remove(PaperTemplate $entity, bool $flush = true): void
    {
        static::getEntityManager()->remove($entity);

        if ($flush) {
            static::getEntityManager()->flush();
        }
    }
}
