<?php

namespace Tourze\TestPaperBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\DoctrineUserBundle\Attribute\UpdatedByColumn;
use Tourze\EasyAdmin\Attribute\Action\Copyable;
use Tourze\TestPaperBundle\Enum\SessionStatus;
use Tourze\TestPaperBundle\Repository\TestSessionRepository;

#[Copyable]
#[ORM\Entity(repositoryClass: TestSessionRepository::class)]
#[ORM\Table(name: 'test_session', options: ['comment' => '考试会话'])]
class TestSession implements \Stringable, ApiArrayInterface
{
    use TimestampableAware;
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

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false)]
    private TestPaper $paper;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private UserInterface $user;

    #[ORM\Column(type: Types::STRING, enumType: SessionStatus::class, options: ['comment' => '会话状态'])]
    private SessionStatus $status = SessionStatus::PENDING;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '开始时间'])]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '结束时间'])]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '到期时间'])]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\Column(nullable: true, options: ['comment' => '得分'])]
    private ?int $score = null;

    #[ORM\Column(nullable: true, options: ['comment' => '总分'])]
    private ?int $totalScore = null;

    #[ORM\Column(options: ['comment' => '尝试次数', 'default' => 1])]
    private int $attemptNumber = 1;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '用户答案'])]
    private ?array $answers = null;

    #[ORM\Column(nullable: true, options: ['comment' => '用时（秒）'])]
    private ?int $duration = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否通过', 'default' => false])]
    private bool $passed = false;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注'])]
    private ?string $remark = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '题目答题时间记录'])]
    private ?array $questionTimings = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '当前题目开始时间'])]
    private ?\DateTimeInterface $currentQuestionStartTime = null;

    #[ORM\Column(nullable: true, options: ['comment' => '当前题目ID'])]
    private ?string $currentQuestionId = null;

    public function __toString(): string
    {
        if (!$this->getId()) {
            return '';
        }

        return "#{$this->getId()} {$this->paper->getTitle()} - {$this->user->getUserIdentifier()}";
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

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getQuestionTimings(): ?array
    {
        return $this->questionTimings;
    }

    public function setQuestionTimings(?array $questionTimings): static
    {
        $this->questionTimings = $questionTimings;
        return $this;
    }

    public function getCurrentQuestionStartTime(): ?\DateTimeInterface
    {
        return $this->currentQuestionStartTime;
    }

    public function setCurrentQuestionStartTime(?\DateTimeInterface $currentQuestionStartTime): static
    {
        $this->currentQuestionStartTime = $currentQuestionStartTime;
        return $this;
    }

    public function getCurrentQuestionId(): ?string
    {
        return $this->currentQuestionId;
    }

    public function setCurrentQuestionId(?string $currentQuestionId): static
    {
        $this->currentQuestionId = $currentQuestionId;
        return $this;
    }

    public function startQuestionTiming(string $questionId): void
    {
        $this->currentQuestionId = $questionId;
        $this->currentQuestionStartTime = new \DateTime();
    }

    public function recordQuestionTiming(string $questionId): int
    {
        if ($this->currentQuestionId !== $questionId || !$this->currentQuestionStartTime) {
            return 0;
        }

        $endTime = new \DateTime();
        $duration = $endTime->getTimestamp() - $this->currentQuestionStartTime->getTimestamp();

        $timings = $this->questionTimings ?? [];
        $timings[$questionId] = [
            'startTime' => $this->currentQuestionStartTime->format('Y-m-d H:i:s'),
            'endTime' => $endTime->format('Y-m-d H:i:s'),
            'duration' => $duration,
        ];
        $this->questionTimings = $timings;

        // 清除当前题目记录
        $this->currentQuestionId = null;
        $this->currentQuestionStartTime = null;

        return $duration;
    }

    public function getQuestionDuration(string $questionId): ?int
    {
        $timings = $this->questionTimings ?? [];
        return $timings[$questionId]['duration'] ?? null;
    }

    public function isCurrentQuestionExpired(int $timeLimit): bool
    {
        if (!$this->currentQuestionStartTime) {
            return false;
        }

        $elapsed = (new \DateTime())->getTimestamp() - $this->currentQuestionStartTime->getTimestamp();
        return $elapsed > $timeLimit;
    }

    public function getCurrentQuestionRemainingTime(int $timeLimit): int
    {
        if (!$this->currentQuestionStartTime) {
            return $timeLimit;
        }

        $elapsed = (new \DateTime())->getTimestamp() - $this->currentQuestionStartTime->getTimestamp();
        return max(0, $timeLimit - $elapsed);
    }

    public function submitAnswer(string $questionId, $answer): void
    {
        $answers = $this->answers ?? [];
        $answers[$questionId] = $answer;
        $this->answers = $answers;
    }

    public function hasAnswered(string $questionId): bool
    {
        return isset($this->answers[$questionId]);
    }

    public function getAnswer(string $questionId)
    {
        return $this->answers[$questionId] ?? null;
    }

    public function retrieveSecretArray(): array
    {
        $result = $this->retrieveApiArray();
        $result['answers'] = $this->getAnswers();
        $result['remark'] = $this->getRemark();
        return $result;
    }

    public function retrieveApiArray(): array
    {
        return [
            'id' => $this->getId(),
            'status' => $this->getStatus()->toArray(),
            'startTime' => $this->getStartTime()?->format('Y-m-d H:i:s'),
            'endTime' => $this->getEndTime()?->format('Y-m-d H:i:s'),
            'expiresAt' => $this->getExpiresAt()?->format('Y-m-d H:i:s'),
            'score' => $this->getScore(),
            'totalScore' => $this->getTotalScore(),
            'scorePercentage' => $this->getScorePercentage(),
            'attemptNumber' => $this->getAttemptNumber(),
            'duration' => $this->getDuration(),
            'passed' => $this->isPassed(),
            'remainingTime' => $this->getRemainingTime(),
            'isExpired' => $this->isExpired(),
            'paper' => $this->getPaper()->retrieveApiArray(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
        ];
    }

    public function getStatus(): SessionStatus
    {
        return $this->status;
    }

    public function setStatus(SessionStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeInterface $startTime): static
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeInterface $endTime): static
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): static
    {
        $this->score = $score;
        return $this;
    }

    public function getTotalScore(): ?int
    {
        return $this->totalScore;
    }

    public function setTotalScore(?int $totalScore): static
    {
        $this->totalScore = $totalScore;
        return $this;
    }

    public function getScorePercentage(): ?float
    {
        if ($this->score === null || $this->totalScore === null || $this->totalScore === 0) {
            return null;
        }

        return round(($this->score / $this->totalScore) * 100, 2);
    }

    public function getAttemptNumber(): int
    {
        return $this->attemptNumber;
    }

    public function setAttemptNumber(int $attemptNumber): static
    {
        $this->attemptNumber = $attemptNumber;
        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function isPassed(): bool
    {
        return $this->passed;
    }

    public function setPassed(bool $passed): static
    {
        $this->passed = $passed;
        return $this;
    }

    public function getRemainingTime(): ?int
    {
        if (!$this->expiresAt || $this->status !== SessionStatus::IN_PROGRESS) {
            return null;
        }

        $now = new \DateTime();
        $remaining = $this->expiresAt->getTimestamp() - $now->getTimestamp();

        return max(0, $remaining);
    }

    public function isExpired(): bool
    {
        if (!$this->expiresAt) {
            return false;
        }

        return new \DateTime() > $this->expiresAt;
    }

    public function getPaper(): TestPaper
    {
        return $this->paper;
    }

    public function setPaper(TestPaper $paper): static
    {
        $this->paper = $paper;
        return $this;
    }public function getAnswers(): ?array
    {
        return $this->answers;
    }

    public function setAnswers(?array $answers): static
    {
        $this->answers = $answers;
        return $this;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): static
    {
        $this->remark = $remark;
        return $this;
    }
}