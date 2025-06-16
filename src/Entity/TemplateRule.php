<?php

namespace Tourze\TestPaperBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\DoctrineUserBundle\Attribute\UpdatedByColumn;
use Tourze\EasyAdmin\Attribute\Action\Copyable;
use Tourze\EasyAdmin\Attribute\Action\Creatable;
use Tourze\EasyAdmin\Attribute\Action\Deletable;
use Tourze\EasyAdmin\Attribute\Action\Editable;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Field\FormField;
use Tourze\EasyAdmin\Attribute\Filter\Filterable;
use Tourze\EasyAdmin\Attribute\Permission\AsPermission;
use Tourze\TestPaperBundle\Repository\TemplateRuleRepository;

#[Copyable]
#[AsPermission(title: '模板规则')]
#[Creatable]
#[Editable]
#[Deletable]
#[ORM\Entity(repositoryClass: TemplateRuleRepository::class)]
#[ORM\Table(name: 'test_template_rule', options: ['comment' => '模板规则'])]
class TemplateRule implements \Stringable, ApiArrayInterface
{
    #[Filterable]
    #[IndexColumn]
    #[ListColumn(order: 98, sorter: true)]
    #[ExportColumn]
    #[CreateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeInterface $createTime = null;

    #[UpdateTimeColumn]
    #[ListColumn(order: 99, sorter: true)]
    #[Filterable]
    #[ExportColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '更新时间'])]
    private ?\DateTimeInterface $updateTime = null;

    #[ExportColumn]
    #[ListColumn(order: -1, sorter: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[CreatedByColumn]
    #[ORM\Column(nullable: true, options: ['comment' => '创建人'])]
    private ?string $createdBy = null;

    #[UpdatedByColumn]
    #[ORM\Column(nullable: true, options: ['comment' => '更新人'])]
    private ?string $updatedBy = null;

    #[ListColumn(title: '模板')]
    #[FormField(title: '模板')]
    #[ORM\ManyToOne(inversedBy: 'rules')]
    #[ORM\JoinColumn(nullable: false)]
    private PaperTemplate $template;

    #[ListColumn(title: '分类ID')]
    #[FormField(title: '分类ID')]
    #[ORM\Column(nullable: true, options: ['comment' => '题目分类ID（来自question-bank-bundle）'])]
    private ?string $categoryId = null;

    #[ListColumn]
    #[FormField]
    #[ORM\Column(nullable: true, options: ['comment' => '题目类型'])]
    private ?string $questionType = null;

    #[ListColumn]
    #[FormField]
    #[ORM\Column(nullable: true, options: ['comment' => '难度等级'])]
    private ?string $difficulty = null;

    #[FormField]
    #[ORM\Column(options: ['comment' => '题目数量'])]
    private int $questionCount = 1;

    #[FormField]
    #[ORM\Column(options: ['comment' => '每题分数'])]
    private int $scorePerQuestion = 1;

    #[FormField]
    #[ORM\Column(options: ['comment' => '排序', 'default' => 0])]
    private int $sort = 0;

    #[FormField]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '标签过滤条件'])]
    private ?array $tagFilters = null;

    #[FormField]
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => '最小正确率'])]
    private ?string $minCorrectRate = null;

    #[FormField]
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => '最大正确率'])]
    private ?string $maxCorrectRate = null;

    #[FormField]
    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '排除已使用题目', 'default' => false])]
    private bool $excludeUsed = false;

    public function __toString(): string
    {
        if (!$this->getId()) {
            return '';
        }

        $parts = [];
        if ($this->categoryId) {
            $parts[] = "分类:{$this->categoryId}";
        }
        if ($this->questionType) {
            $parts[] = $this->questionType;
        }
        if ($this->difficulty) {
            $parts[] = $this->difficulty;
        }
        $parts[] = "{$this->questionCount}题";

        return implode(' - ', $parts);
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?string $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?string $updatedBy): self
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }

    public function getTemplate(): PaperTemplate
    {
        return $this->template;
    }

    public function setTemplate(PaperTemplate $template): static
    {
        $this->template = $template;
        return $this;
    }

    public function retrieveSecretArray(): array
    {
        return $this->retrieveApiArray();
    }

    public function retrieveApiArray(): array
    {
        return [
            'id' => $this->getId(),
            'categoryId' => $this->getCategoryId(),
            'questionType' => $this->getQuestionType(),
            'difficulty' => $this->getDifficulty(),
            'questionCount' => $this->getQuestionCount(),
            'scorePerQuestion' => $this->getScorePerQuestion(),
            'totalScore' => $this->getTotalScore(),
            'sort' => $this->getSort(),
            'tagFilters' => $this->getTagFilters(),
            'minCorrectRate' => $this->getMinCorrectRate(),
            'maxCorrectRate' => $this->getMaxCorrectRate(),
            'excludeUsed' => $this->isExcludeUsed(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
        ];
    }

    public function getCategoryId(): ?string
    {
        return $this->categoryId;
    }

    public function setCategoryId(?string $categoryId): static
    {
        $this->categoryId = $categoryId;
        return $this;
    }

    public function getQuestionType(): ?string
    {
        return $this->questionType;
    }

    public function setQuestionType(?string $questionType): static
    {
        $this->questionType = $questionType;
        return $this;
    }

    public function getDifficulty(): ?string
    {
        return $this->difficulty;
    }

    public function setDifficulty(?string $difficulty): static
    {
        $this->difficulty = $difficulty;
        return $this;
    }

    public function getQuestionCount(): int
    {
        return $this->questionCount;
    }

    public function setQuestionCount(int $questionCount): static
    {
        $this->questionCount = $questionCount;
        return $this;
    }

    public function getScorePerQuestion(): int
    {
        return $this->scorePerQuestion;
    }

    public function setScorePerQuestion(int $scorePerQuestion): static
    {
        $this->scorePerQuestion = $scorePerQuestion;
        return $this;
    }

    public function getTotalScore(): int
    {
        return $this->questionCount * $this->scorePerQuestion;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort): static
    {
        $this->sort = $sort;
        return $this;
    }

    public function getTagFilters(): ?array
    {
        return $this->tagFilters;
    }

    public function setTagFilters(?array $tagFilters): static
    {
        $this->tagFilters = $tagFilters;
        return $this;
    }

    public function getMinCorrectRate(): ?string
    {
        return $this->minCorrectRate;
    }

    public function setMinCorrectRate(?string $minCorrectRate): static
    {
        $this->minCorrectRate = $minCorrectRate;
        return $this;
    }

    public function getMaxCorrectRate(): ?string
    {
        return $this->maxCorrectRate;
    }

    public function setMaxCorrectRate(?string $maxCorrectRate): static
    {
        $this->maxCorrectRate = $maxCorrectRate;
        return $this;
    }

    public function isExcludeUsed(): bool
    {
        return $this->excludeUsed;
    }

    public function setExcludeUsed(bool $excludeUsed): static
    {
        $this->excludeUsed = $excludeUsed;
        return $this;
    }

    public function getCreateTime(): ?\DateTimeInterface
    {
        return $this->createTime;
    }

    public function setCreateTime(?\DateTimeInterface $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function getUpdateTime(): ?\DateTimeInterface
    {
        return $this->updateTime;
    }

    public function setUpdateTime(?\DateTimeInterface $updateTime): void
    {
        $this->updateTime = $updateTime;
    }
}