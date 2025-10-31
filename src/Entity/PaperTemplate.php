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
use Tourze\TestPaperBundle\Repository\PaperTemplateRepository;

/**
 * @implements ApiArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: PaperTemplateRepository::class)]
#[ORM\Table(name: 'test_paper_template', options: ['comment' => '试卷模板'])]
class PaperTemplate implements \Stringable, ApiArrayInterface
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;

    #[ORM\Column(options: ['comment' => '及格分数', 'default' => 60])]
    #[Assert\Range(min: 0, max: 100)]
    private int $passScore = 60;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否打乱题目', 'default' => false])]
    #[Assert\Type(type: 'bool')]
    private bool $shuffleQuestions = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否打乱选项', 'default' => false])]
    #[Assert\Type(type: 'bool')]
    private bool $shuffleOptions = false;

    #[ORM\Column(length: 120, options: ['comment' => '模板名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '模板描述'])]
    #[Assert\Length(max: 65535)]
    private ?string $description = null;

    #[ORM\Column(options: ['comment' => '总题数'])]
    #[Assert\PositiveOrZero]
    private int $totalQuestions = 0;

    #[ORM\Column(options: ['comment' => '总分'])]
    #[Assert\PositiveOrZero]
    private int $totalScore = 100;

    #[ORM\Column(nullable: true, options: ['comment' => '考试时长（分钟）'])]
    #[Assert\PositiveOrZero]
    private ?int $timeLimit = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '难度分布配置'])]
    #[Assert\Type(type: 'array')]
    private ?array $difficultyDistribution = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '题型分布配置'])]
    #[Assert\Type(type: 'array')]
    private ?array $questionTypeDistribution = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否启用', 'default' => true])]
    #[Assert\Type(type: 'bool')]
    private bool $isActive = true;

    /**
     * @var Collection<int, TemplateRule>
     */
    #[ORM\OneToMany(mappedBy: 'template', targetEntity: TemplateRule::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $rules;

    #[ORM\ManyToOne(targetEntity: TestPaper::class, inversedBy: 'templates', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?TestPaper $paper = null;

    public function __construct()
    {
        $this->rules = new ArrayCollection();
    }

    public function __toString(): string
    {
        if (null === $this->getId()) {
            return '';
        }

        return $this->getName();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function addRule(TemplateRule $rule): void
    {
        if (!$this->rules->contains($rule)) {
            $this->rules->add($rule);
            $rule->setTemplate($this);
        }
    }

    public function removeRule(TemplateRule $rule): void
    {
        $this->rules->removeElement($rule);
    }

    public function getPaper(): ?TestPaper
    {
        return $this->paper;
    }

    public function setPaper(?TestPaper $paper): void
    {
        $this->paper = $paper;
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveSecretArray(): array
    {
        $result = $this->retrieveApiArray();
        $result['rules'] = array_map(
            fn (TemplateRule $rule) => $rule->retrieveApiArray(),
            $this->getRules()->toArray()
        );

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
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

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getTotalQuestions(): int
    {
        return $this->totalQuestions;
    }

    public function setTotalQuestions(int $totalQuestions): void
    {
        $this->totalQuestions = $totalQuestions;
    }

    public function getTotalScore(): int
    {
        return $this->totalScore;
    }

    public function setTotalScore(int $totalScore): void
    {
        $this->totalScore = $totalScore;
    }

    public function getTimeLimit(): ?int
    {
        return $this->timeLimit;
    }

    public function setTimeLimit(?int $timeLimit): void
    {
        $this->timeLimit = $timeLimit;
    }

    public function getPassScore(): int
    {
        return $this->passScore;
    }

    public function setPassScore(int $passScore): void
    {
        $this->passScore = $passScore;
    }

    public function isShuffleQuestions(): bool
    {
        return $this->shuffleQuestions;
    }

    public function setShuffleQuestions(bool $shuffleQuestions): void
    {
        $this->shuffleQuestions = $shuffleQuestions;
    }

    public function isShuffleOptions(): bool
    {
        return $this->shuffleOptions;
    }

    public function setShuffleOptions(bool $shuffleOptions): void
    {
        $this->shuffleOptions = $shuffleOptions;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDifficultyDistribution(): ?array
    {
        return $this->difficultyDistribution;
    }

    /**
     * @param array<string, mixed>|null $difficultyDistribution
     */
    public function setDifficultyDistribution(?array $difficultyDistribution): void
    {
        $this->difficultyDistribution = $difficultyDistribution;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getQuestionTypeDistribution(): ?array
    {
        return $this->questionTypeDistribution;
    }

    /**
     * @param array<string, mixed>|null $questionTypeDistribution
     */
    public function setQuestionTypeDistribution(?array $questionTypeDistribution): void
    {
        $this->questionTypeDistribution = $questionTypeDistribution;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getRuleCount(): int
    {
        return $this->rules->count();
    }

    /**
     * @return Collection<int, TemplateRule>
     */
    public function getRules(): Collection
    {
        return $this->rules;
    }
}
