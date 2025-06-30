<?php

namespace Tourze\TestPaperBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\TestPaperBundle\Enum\PaperGenerationType;
use Tourze\TestPaperBundle\Enum\PaperStatus;
use Tourze\TestPaperBundle\Repository\TestPaperRepository;

#[ORM\Entity(repositoryClass: TestPaperRepository::class)]
#[ORM\Table(name: 'test_paper', options: ['comment' => '试卷'])]
class TestPaper implements \Stringable, ApiArrayInterface
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;

    #[ORM\Column(length: 120, unique: true, options: ['comment' => '试卷标题'])]
    private string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '试卷描述'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, enumType: PaperStatus::class, options: ['comment' => '试卷状态'])]
    private PaperStatus $status = PaperStatus::DRAFT;

    #[ORM\Column(type: Types::STRING, enumType: PaperGenerationType::class, options: ['comment' => '组卷方式'])]
    private PaperGenerationType $generationType = PaperGenerationType::MANUAL;

    #[ORM\Column(options: ['comment' => '总分'])]
    private int $totalScore = 100;

    #[ORM\Column(options: ['comment' => '及格分数'])]
    private int $passScore = 60;

    #[ORM\Column(nullable: true, options: ['comment' => '考试时长（秒）'])]
    private ?int $timeLimit = null;

    #[ORM\Column(options: ['comment' => '题目总数'])]
    private int $questionCount = 0;

    #[ORM\OneToMany(mappedBy: 'paper', targetEntity: PaperQuestion::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $paperQuestions;

    #[ORM\OneToMany(mappedBy: 'paper', targetEntity: PaperTemplate::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $templates;

    #[ORM\OneToMany(mappedBy: 'paper', targetEntity: TestSession::class, orphanRemoval: true)]
    private Collection $sessions;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否随机排序题目', 'default' => false])]
    private bool $randomizeQuestions = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否随机排序选项', 'default' => false])]
    private bool $randomizeOptions = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否允许重做', 'default' => true])]
    private bool $allowRetake = true;

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
        if ($this->getId() === null) {
            return '';
        }

        return "#{$this->getId()} {$this->getTitle()}";
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
        $this->paperQuestions->removeElement($paperQuestion);
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
        $this->sessions->removeElement($session);
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
    }}