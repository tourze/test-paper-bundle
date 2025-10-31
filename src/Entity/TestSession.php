<?php

namespace Tourze\TestPaperBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\TestPaperBundle\Enum\SessionStatus;
use Tourze\TestPaperBundle\Repository\TestSessionRepository;

/**
 * @implements ApiArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: TestSessionRepository::class)]
#[ORM\Table(name: 'test_session', options: ['comment' => '考试会话'])]
class TestSession implements \Stringable, ApiArrayInterface
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;

    #[ORM\ManyToOne(inversedBy: 'sessions', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private TestPaper $paper;

    #[ORM\ManyToOne(targetEntity: UserInterface::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private UserInterface $user;

    #[ORM\Column(type: Types::STRING, enumType: SessionStatus::class, options: ['comment' => '会话状态'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [SessionStatus::class, 'cases'])]
    private SessionStatus $status = SessionStatus::PENDING;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '开始时间'])]
    #[Assert\Type(type: '\DateTimeInterface')]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '结束时间'])]
    #[Assert\Type(type: '\DateTimeInterface')]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '到期时间'])]
    #[Assert\Type(type: '\DateTimeInterface')]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\Column(nullable: true, options: ['comment' => '得分'])]
    #[Assert\PositiveOrZero]
    private ?int $score = null;

    #[ORM\Column(nullable: true, options: ['comment' => '总分'])]
    #[Assert\PositiveOrZero]
    private ?int $totalScore = null;

    #[ORM\Column(options: ['comment' => '尝试次数', 'default' => 1])]
    #[Assert\PositiveOrZero]
    private int $attemptNumber = 1;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '用户答案'])]
    #[Assert\Type(type: 'array')]
    private ?array $answers = null;

    #[ORM\Column(nullable: true, options: ['comment' => '用时（秒）'])]
    #[Assert\PositiveOrZero]
    private ?int $duration = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否通过', 'default' => false])]
    #[Assert\Type(type: 'bool')]
    private bool $passed = false;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注'])]
    #[Assert\Length(max: 65535)]
    private ?string $remark = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '题目答题时间记录'])]
    #[Assert\Type(type: 'array')]
    private ?array $questionTimings = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '当前题目开始时间'])]
    #[Assert\Type(type: '\DateTimeInterface')]
    private ?\DateTimeInterface $currentQuestionStartTime = null;

    #[ORM\Column(nullable: true, options: ['comment' => '当前题目 ID'])]
    #[Assert\Length(max: 255)]
    private ?string $currentQuestionId = null;

    public function __toString(): string
    {
        if (null === $this->getId()) {
            return '';
        }

        return "#{$this->getId()} {$this->paper->getTitle()} - {$this->user->getUserIdentifier()}";
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): void
    {
        $this->user = $user;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getQuestionTimings(): ?array
    {
        return $this->questionTimings;
    }

    /**
     * @param array<string, mixed>|null $questionTimings
     */
    public function setQuestionTimings(?array $questionTimings): void
    {
        $this->questionTimings = $questionTimings;
    }

    public function getCurrentQuestionStartTime(): ?\DateTimeInterface
    {
        return $this->currentQuestionStartTime;
    }

    public function setCurrentQuestionStartTime(?\DateTimeInterface $currentQuestionStartTime): void
    {
        $this->currentQuestionStartTime = $currentQuestionStartTime;
    }

    public function getCurrentQuestionId(): ?string
    {
        return $this->currentQuestionId;
    }

    public function setCurrentQuestionId(?string $currentQuestionId): void
    {
        $this->currentQuestionId = $currentQuestionId;
    }

    public function startQuestionTiming(string $questionId): void
    {
        $this->currentQuestionId = $questionId;
        $this->currentQuestionStartTime = new \DateTimeImmutable();
    }

    public function recordQuestionTiming(string $questionId): int
    {
        if ($this->currentQuestionId !== $questionId || null === $this->currentQuestionStartTime) {
            return 0;
        }

        $endTime = new \DateTimeImmutable();
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

        if (!isset($timings[$questionId]) || !is_array($timings[$questionId])) {
            return null;
        }

        $questionData = $timings[$questionId];

        return isset($questionData['duration']) && is_int($questionData['duration'])
            ? $questionData['duration']
            : null;
    }

    public function isCurrentQuestionExpired(int $timeLimit): bool
    {
        if (null === $this->currentQuestionStartTime) {
            return false;
        }

        $elapsed = (new \DateTimeImmutable())->getTimestamp() - $this->currentQuestionStartTime->getTimestamp();

        return $elapsed > $timeLimit;
    }

    public function getCurrentQuestionRemainingTime(int $timeLimit): int
    {
        if (null === $this->currentQuestionStartTime) {
            return $timeLimit;
        }

        $elapsed = (new \DateTimeImmutable())->getTimestamp() - $this->currentQuestionStartTime->getTimestamp();

        return max(0, $timeLimit - $elapsed);
    }

    /**
     * @param mixed $answer
     */
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

    /**
     * @return mixed
     */
    public function getAnswer(string $questionId)
    {
        return $this->answers[$questionId] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveSecretArray(): array
    {
        $result = $this->retrieveApiArray();
        $result['answers'] = $this->getAnswers();
        $result['remark'] = $this->getRemark();

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
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

    public function setStatus(SessionStatus $status): void
    {
        $this->status = $status;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeInterface $startTime): void
    {
        $this->startTime = $startTime;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeInterface $endTime): void
    {
        $this->endTime = $endTime;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): void
    {
        $this->score = $score;
    }

    public function getTotalScore(): ?int
    {
        return $this->totalScore;
    }

    public function setTotalScore(?int $totalScore): void
    {
        $this->totalScore = $totalScore;
    }

    public function getScorePercentage(): ?float
    {
        if (null === $this->score || null === $this->totalScore || 0 === $this->totalScore) {
            return null;
        }

        return round(($this->score / $this->totalScore) * 100, 2);
    }

    public function getAttemptNumber(): int
    {
        return $this->attemptNumber;
    }

    public function setAttemptNumber(int $attemptNumber): void
    {
        $this->attemptNumber = $attemptNumber;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): void
    {
        $this->duration = $duration;
    }

    public function isPassed(): bool
    {
        return $this->passed;
    }

    public function setPassed(bool $passed): void
    {
        $this->passed = $passed;
    }

    public function getRemainingTime(): ?int
    {
        if (null === $this->expiresAt || SessionStatus::IN_PROGRESS !== $this->status) {
            return null;
        }

        $now = new \DateTimeImmutable();
        $remaining = $this->expiresAt->getTimestamp() - $now->getTimestamp();

        return max(0, $remaining);
    }

    public function isExpired(): bool
    {
        if (null === $this->expiresAt) {
            return false;
        }

        return new \DateTimeImmutable() > $this->expiresAt;
    }

    public function getPaper(): TestPaper
    {
        return $this->paper;
    }

    public function setPaper(TestPaper $paper): void
    {
        $this->paper = $paper;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAnswers(): ?array
    {
        return $this->answers;
    }

    /**
     * @param array<string, mixed>|null $answers
     */
    public function setAnswers(?array $answers): void
    {
        $this->answers = $answers;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }
}
