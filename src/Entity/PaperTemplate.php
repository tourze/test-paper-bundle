<?php

namespace Tourze\TestPaperBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
use Tourze\EasyAdmin\Attribute\Column\CopyColumn;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Field\FormField;
use Tourze\EasyAdmin\Attribute\Filter\Filterable;
use Tourze\EasyAdmin\Attribute\Permission\AsPermission;
use Tourze\TestPaperBundle\Repository\PaperTemplateRepository;

#[Copyable]
#[AsPermission(title: '试卷模板')]
#[Creatable]
#[Editable]
#[Deletable]
#[ORM\Entity(repositoryClass: PaperTemplateRepository::class)]
#[ORM\Table(name: 'test_paper_template', options: ['comment' => '试卷模板'])]
class PaperTemplate implements \Stringable, ApiArrayInterface
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

    #[FormField]
    #[ORM\Column(options: ['comment' => '及格分数', 'default' => 60])]
    private int $passScore = 60;
    
    #[FormField]
    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否打乱题目', 'default' => false])]
    private bool $shuffleQuestions = false;

    #[FormField]
    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否打乱选项', 'default' => false])]
    private bool $shuffleOptions = false;

    #[CopyColumn(suffix: true)]
    #[ListColumn]
    #[FormField]
    #[ORM\Column(length: 120, options: ['comment' => '模板名称'])]
    private string $name;

    #[FormField]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '模板描述'])]
    private ?string $description = null;

    #[FormField]
    #[ORM\Column(options: ['comment' => '总题数'])]
    private int $totalQuestions = 0;

    #[FormField]
    #[ORM\Column(options: ['comment' => '总分'])]
    private int $totalScore = 100;

    #[FormField]
    #[ORM\Column(nullable: true, options: ['comment' => '考试时长（分钟）'])]
    private ?int $timeLimit = null;

    #[FormField]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '难度分布配置'])]
    private ?array $difficultyDistribution = null;

    #[FormField]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '题型分布配置'])]
    private ?array $questionTypeDistribution = null;

    #[FormField]
    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否启用', 'default' => true])]
    private bool $isActive = true;

    #[ORM\OneToMany(mappedBy: 'template', targetEntity: TemplateRule::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $rules;

    public function __construct()
    {
        $this->rules = new ArrayCollection();
    }

    public function __toString(): string
    {
        if (!$this->getId()) {
            return '';
        }

        return $this->getName();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
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

    public function addRule(TemplateRule $rule): static
    {
        if (!$this->rules->contains($rule)) {
            $this->rules->add($rule);
            $rule->setTemplate($this);
        }

        return $this;
    }

    public function removeRule(TemplateRule $rule): static
    {
        if ($this->rules->removeElement($rule)) {
            if ($rule->getTemplate() === $this) {
                $rule->setTemplate(null);
            }
        }

        return $this;
    }

    public function retrieveSecretArray(): array
    {
        $result = $this->retrieveApiArray();
        $result['rules'] = array_map(
            fn(TemplateRule $rule) => $rule->retrieveApiArray(),
            $this->getRules()->toArray()
        );
        return $result;
    }

    public function retrieveApiArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'totalQuestions' => $this->getTotalQuestions(),
            'totalScore' => $this->getTotalScore(),
            'timeLimit' => $this->getTimeLimit(),
            'passScore' => $this->getPassScore(),
            'shuffleQuestions' => $this->isShuffleQuestions(),
            'shuffleOptions' => $this->isShuffleOptions(),
            'difficultyDistribution' => $this->getDifficultyDistribution(),
            'questionTypeDistribution' => $this->getQuestionTypeDistribution(),
            'isActive' => $this->isActive(),
            'ruleCount' => $this->getRuleCount(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
        ];
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getTotalQuestions(): int
    {
        return $this->totalQuestions;
    }

    public function setTotalQuestions(int $totalQuestions): static
    {
        $this->totalQuestions = $totalQuestions;
        return $this;
    }

    public function getTotalScore(): int
    {
        return $this->totalScore;
    }

    public function setTotalScore(int $totalScore): static
    {
        $this->totalScore = $totalScore;
        return $this;
    }

    public function getTimeLimit(): ?int
    {
        return $this->timeLimit;
    }

    public function setTimeLimit(?int $timeLimit): static
    {
        $this->timeLimit = $timeLimit;
        return $this;
    }

    public function getPassScore(): int
    {
        return $this->passScore;
    }

    public function setPassScore(int $passScore): static
    {
        $this->passScore = $passScore;
        return $this;
    }

    public function isShuffleQuestions(): bool
    {
        return $this->shuffleQuestions;
    }

    public function setShuffleQuestions(bool $shuffleQuestions): static
    {
        $this->shuffleQuestions = $shuffleQuestions;
        return $this;
    }

    public function isShuffleOptions(): bool
    {
        return $this->shuffleOptions;
    }

    public function setShuffleOptions(bool $shuffleOptions): static
    {
        $this->shuffleOptions = $shuffleOptions;
        return $this;
    }

    public function getDifficultyDistribution(): ?array
    {
        return $this->difficultyDistribution;
    }

    public function setDifficultyDistribution(?array $difficultyDistribution): static
    {
        $this->difficultyDistribution = $difficultyDistribution;
        return $this;
    }

    public function getQuestionTypeDistribution(): ?array
    {
        return $this->questionTypeDistribution;
    }

    public function setQuestionTypeDistribution(?array $questionTypeDistribution): static
    {
        $this->questionTypeDistribution = $questionTypeDistribution;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    #[ListColumn(title: '规则数量')]
    public function getRuleCount(): int
    {
        return $this->rules->count();
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

    /**
     * @return Collection<int, TemplateRule>
     */
    public function getRules(): Collection
    {
        return $this->rules;
    }
}