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
use Tourze\TestPaperBundle\Enum\PaperGenerationType;
use Tourze\TestPaperBundle\Enum\PaperStatus;
use Tourze\TestPaperBundle\Repository\TestPaperRepository;

#[Copyable]
#[AsPermission(title: '试卷')]
#[Deletable]
#[Editable]
#[Creatable]
#[ORM\Entity(repositoryClass: TestPaperRepository::class)]
#[ORM\Table(name: 'test_paper', options: ['comment' => '试卷'])]
class TestPaper implements \Stringable, ApiArrayInterface
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

    #[CopyColumn(suffix: true)]
    #[ListColumn]
    #[FormField]
    #[ORM\Column(length: 120, unique: true, options: ['comment' => '试卷标题'])]
    private string $title;

    #[FormField]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '试卷描述'])]
    private ?string $description = null;

    #[ListColumn]
    #[FormField]
    #[ORM\Column(type: Types::STRING, enumType: PaperStatus::class, options: ['comment' => '试卷状态'])]
    private PaperStatus $status = PaperStatus::DRAFT;

    #[ListColumn]
    #[FormField]
    #[ORM\Column(type: Types::STRING, enumType: PaperGenerationType::class, options: ['comment' => '组卷方式'])]
    private PaperGenerationType $generationType = PaperGenerationType::MANUAL;

    #[FormField]
    #[ORM\Column(options: ['comment' => '总分'])]
    private int $totalScore = 100;

    #[FormField]
    #[ORM\Column(options: ['comment' => '及格分数'])]
    private int $passScore = 60;

    #[FormField]
    #[ORM\Column(nullable: true, options: ['comment' => '考试时长（秒）'])]
    private ?int $timeLimit = null;

    #[FormField]
    #[ORM\Column(options: ['comment' => '题目总数'])]
    private int $questionCount = 0;

    #[ORM\OneToMany(mappedBy: 'paper', targetEntity: PaperQuestion::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $paperQuestions;

    #[ORM\OneToMany(mappedBy: 'paper', targetEntity: PaperTemplate::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $templates;

    #[ORM\OneToMany(mappedBy: 'paper', targetEntity: TestSession::class, orphanRemoval: true)]
    private Collection $sessions;

    #[FormField]
    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否随机排序题目', 'default' => false])]
    private bool $randomizeQuestions = false;

    #[FormField]
    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否随机排序选项', 'default' => false])]
    private bool $randomizeOptions = false;

    #[FormField]
    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否允许重做', 'default' => true])]
    private bool $allowRetake = true;

    #[FormField]
    #[ORM\Column(nullable: true, options: ['comment' => '最大重做次数'])]
    private ?int $maxAttempts = null;

    public function __construct()
    {
        $this->paperQuestions = new ArrayCollection();
        $this->templates = new ArrayCollection();
        $this->sessions = new ArrayCollection();
    }

    public function __toString(): string
    {
        if (!$this->getId()) {
            return '';
        }

        return "#{$this->getId()} {$this->getTitle()}";
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
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

    /**
     * @return Collection<int, PaperQuestion>
     */
    public function getPaperQuestions(): Collection
    {
        return $this->paperQuestions;
    }

    public function addPaperQuestion(PaperQuestion $paperQuestion): static
    {
        if (!$this->paperQuestions->contains($paperQuestion)) {
            $this->paperQuestions->add($paperQuestion);
            $paperQuestion->setPaper($this);
        }

        return $this;
    }

    public function removePaperQuestion(PaperQuestion $paperQuestion): static
    {
        if ($this->paperQuestions->removeElement($paperQuestion)) {
            if ($paperQuestion->getPaper() === $this) {
                $paperQuestion->setPaper(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PaperTemplate>
     */
    public function getTemplates(): Collection
    {
        return $this->templates;
    }

    public function addTemplate(PaperTemplate $template): static
    {
        if (!$this->templates->contains($template)) {
            $this->templates->add($template);
            $template->setPaper($this);
        }

        return $this;
    }

    public function removeTemplate(PaperTemplate $template): static
    {
        if ($this->templates->removeElement($template)) {
            if ($template->getPaper() === $this) {
                $template->setPaper(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TestSession>
     */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function addSession(TestSession $session): static
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions->add($session);
            $session->setPaper($this);
        }

        return $this;
    }

    public function removeSession(TestSession $session): static
    {
        if ($this->sessions->removeElement($session)) {
            if ($session->getPaper() === $this) {
                $session->setPaper(null);
            }
        }

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
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'status' => $this->getStatus()->value,
            'generationType' => $this->getGenerationType()->value,
            'totalScore' => $this->getTotalScore(),
            'passScore' => $this->getPassScore(),
            'timeLimit' => $this->getTimeLimit(),
            'questionCount' => $this->getQuestionCount(),
            'randomizeQuestions' => $this->isRandomizeQuestions(),
            'randomizeOptions' => $this->isRandomizeOptions(),
            'allowRetake' => $this->isAllowRetake(),
            'maxAttempts' => $this->getMaxAttempts(),
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

    public function getStatus(): PaperStatus
    {
        return $this->status;
    }

    public function setStatus(PaperStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getGenerationType(): PaperGenerationType
    {
        return $this->generationType;
    }

    public function setGenerationType(PaperGenerationType $generationType): static
    {
        $this->generationType = $generationType;
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

    public function getPassScore(): int
    {
        return $this->passScore;
    }

    public function setPassScore(int $passScore): static
    {
        $this->passScore = $passScore;
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

    public function getQuestionCount(): int
    {
        return $this->questionCount;
    }

    public function setQuestionCount(int $questionCount): static
    {
        $this->questionCount = $questionCount;
        return $this;
    }

    public function isRandomizeQuestions(): bool
    {
        return $this->randomizeQuestions;
    }

    public function setRandomizeQuestions(bool $randomizeQuestions): static
    {
        $this->randomizeQuestions = $randomizeQuestions;
        return $this;
    }

    public function isRandomizeOptions(): bool
    {
        return $this->randomizeOptions;
    }

    public function setRandomizeOptions(bool $randomizeOptions): static
    {
        $this->randomizeOptions = $randomizeOptions;
        return $this;
    }

    public function isAllowRetake(): bool
    {
        return $this->allowRetake;
    }

    public function setAllowRetake(bool $allowRetake): static
    {
        $this->allowRetake = $allowRetake;
        return $this;
    }

    public function getMaxAttempts(): ?int
    {
        return $this->maxAttempts;
    }

    public function setMaxAttempts(?int $maxAttempts): static
    {
        $this->maxAttempts = $maxAttempts;
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