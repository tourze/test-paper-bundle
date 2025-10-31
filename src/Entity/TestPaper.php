<?php

namespace Tourze\TestPaperBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\TestPaperBundle\Enum\PaperGenerationType;
use Tourze\TestPaperBundle\Enum\PaperStatus;
use Tourze\TestPaperBundle\Repository\TestPaperRepository;

/**
 * @implements ApiArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: TestPaperRepository::class)]
#[ORM\Table(name: 'test_paper', options: ['comment' => '试卷'])]
class TestPaper implements \Stringable, ApiArrayInterface
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;

    #[ORM\Column(length: 120, unique: true, options: ['comment' => '试卷标题'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    private string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '试卷描述'])]
    #[Assert\Length(max: 65535)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, enumType: PaperStatus::class, options: ['comment' => '试卷状态'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [PaperStatus::class, 'cases'])]
    private PaperStatus $status = PaperStatus::DRAFT;

    #[ORM\Column(type: Types::STRING, enumType: PaperGenerationType::class, options: ['comment' => '组卷方式'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [PaperGenerationType::class, 'cases'])]
    private PaperGenerationType $generationType = PaperGenerationType::MANUAL;

    #[ORM\Column(options: ['comment' => '总分'])]
    #[Assert\PositiveOrZero]
    private int $totalScore = 100;

    #[ORM\Column(options: ['comment' => '及格分数'])]
    #[Assert\PositiveOrZero]
    private int $passScore = 60;

    #[ORM\Column(nullable: true, options: ['comment' => '考试时长（秒）'])]
    #[Assert\PositiveOrZero]
    private ?int $timeLimit = null;

    #[ORM\Column(options: ['comment' => '题目总数'])]
    #[Assert\PositiveOrZero]
    private int $questionCount = 0;

    /**
     * @var Collection<int, PaperQuestion>
     */
    #[ORM\OneToMany(mappedBy: 'paper', targetEntity: PaperQuestion::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $paperQuestions;

    /**
     * @var Collection<int, PaperTemplate>
     */
    #[ORM\OneToMany(mappedBy: 'paper', targetEntity: PaperTemplate::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $templates;

    /**
     * @var Collection<int, TestSession>
     */
    #[ORM\OneToMany(mappedBy: 'paper', targetEntity: TestSession::class, orphanRemoval: true)]
    private Collection $sessions;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否随机排序题目', 'default' => false])]
    #[Assert\Type(type: 'bool')]
    private bool $randomizeQuestions = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否随机排序选项', 'default' => false])]
    #[Assert\Type(type: 'bool')]
    private bool $randomizeOptions = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否允许重做', 'default' => true])]
    #[Assert\Type(type: 'bool')]
    private bool $allowRetake = true;

    #[ORM\Column(nullable: true, options: ['comment' => '最大重做次数'])]
    #[Assert\PositiveOrZero]
    private ?int $maxAttempts = null;

    public function __construct()
    {
        $this->paperQuestions = new ArrayCollection();
        $this->templates = new ArrayCollection();
        $this->sessions = new ArrayCollection();
    }

    public function __toString(): string
    {
        if (null === $this->getId()) {
            return '';
        }

        return "#{$this->getId()} {$this->getTitle()}";
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return Collection<int, PaperQuestion>
     */
    public function getPaperQuestions(): Collection
    {
        return $this->paperQuestions;
    }

    public function addPaperQuestion(PaperQuestion $paperQuestion): void
    {
        if (!$this->paperQuestions->contains($paperQuestion)) {
            $this->paperQuestions->add($paperQuestion);
            $paperQuestion->setPaper($this);
        }
    }

    public function removePaperQuestion(PaperQuestion $paperQuestion): void
    {
        $this->paperQuestions->removeElement($paperQuestion);
    }

    /**
     * @return Collection<int, PaperTemplate>
     */
    public function getTemplates(): Collection
    {
        return $this->templates;
    }

    public function addTemplate(PaperTemplate $template): void
    {
        if (!$this->templates->contains($template)) {
            $this->templates->add($template);
            $template->setPaper($this);
        }
    }

    public function removeTemplate(PaperTemplate $template): void
    {
        if ($this->templates->removeElement($template)) {
            if ($template->getPaper() === $this) {
                $template->setPaper(null);
            }
        }
    }

    /**
     * @return Collection<int, TestSession>
     */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function addSession(TestSession $session): void
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions->add($session);
            $session->setPaper($this);
        }
    }

    public function removeSession(TestSession $session): void
    {
        $this->sessions->removeElement($session);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveSecretArray(): array
    {
        return $this->retrieveApiArray();
    }

    /**
     * @return array<string, mixed>
     */
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

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getStatus(): PaperStatus
    {
        return $this->status;
    }

    public function setStatus(PaperStatus $status): void
    {
        $this->status = $status;
    }

    public function getGenerationType(): PaperGenerationType
    {
        return $this->generationType;
    }

    public function setGenerationType(PaperGenerationType $generationType): void
    {
        $this->generationType = $generationType;
    }

    public function getTotalScore(): int
    {
        return $this->totalScore;
    }

    public function setTotalScore(int $totalScore): void
    {
        $this->totalScore = $totalScore;
    }

    public function getPassScore(): int
    {
        return $this->passScore;
    }

    public function setPassScore(int $passScore): void
    {
        $this->passScore = $passScore;
    }

    public function getTimeLimit(): ?int
    {
        return $this->timeLimit;
    }

    public function setTimeLimit(?int $timeLimit): void
    {
        $this->timeLimit = $timeLimit;
    }

    public function getQuestionCount(): int
    {
        return $this->questionCount;
    }

    public function setQuestionCount(int $questionCount): void
    {
        $this->questionCount = $questionCount;
    }

    public function isRandomizeQuestions(): bool
    {
        return $this->randomizeQuestions;
    }

    public function setRandomizeQuestions(bool $randomizeQuestions): void
    {
        $this->randomizeQuestions = $randomizeQuestions;
    }

    public function isRandomizeOptions(): bool
    {
        return $this->randomizeOptions;
    }

    public function setRandomizeOptions(bool $randomizeOptions): void
    {
        $this->randomizeOptions = $randomizeOptions;
    }

    public function isAllowRetake(): bool
    {
        return $this->allowRetake;
    }

    public function setAllowRetake(bool $allowRetake): void
    {
        $this->allowRetake = $allowRetake;
    }

    public function getMaxAttempts(): ?int
    {
        return $this->maxAttempts;
    }

    public function setMaxAttempts(?int $maxAttempts): void
    {
        $this->maxAttempts = $maxAttempts;
    }
}
