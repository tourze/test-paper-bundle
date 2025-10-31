<?php

namespace Tourze\TestPaperBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TestPaperBundle\Entity\PaperTemplate;
use Tourze\TestPaperBundle\Repository\PaperTemplateRepository;

/**
 * @internal
 */
#[CoversClass(PaperTemplateRepository::class)]
#[RunTestsInSeparateProcesses]
final class PaperTemplateRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): object
    {
        $template = new PaperTemplate();
        $template->setName('测试模板_' . uniqid());
        $template->setDescription('测试模板描述');
        $template->setTotalQuestions(20);
        $template->setTotalScore(100);
        $template->setPassScore(60);
        $template->setIsActive(true);
        $template->setShuffleQuestions(false);
        $template->setShuffleOptions(false);
        $template->setCreateTime(new \DateTimeImmutable());
        $template->setUpdateTime(new \DateTimeImmutable());
        $template->setCreatedBy('test-user');
        $template->setUpdatedBy('test-user');

        return $template;
    }

    protected function onSetUp(): void
    {
        // Repository测试不需要额外的设置
    }

    protected function getRepository(): PaperTemplateRepository
    {
        return self::getService(PaperTemplateRepository::class);
    }

    public function testFindActive(): void
    {
        $results = $this->getRepository()->findActive();
        $this->assertIsArray($results);
    }

    public function testFindByCreator(): void
    {
        $results = $this->getRepository()->findByCreator('test-user');
        $this->assertIsArray($results);
    }

    public function testSearchByKeyword(): void
    {
        $results = $this->getRepository()->searchByKeyword('test');
        $this->assertIsArray($results);
    }

    public function testFindWithRuleCount(): void
    {
        $results = $this->getRepository()->findWithRuleCount();
        $this->assertIsArray($results);
    }
}
