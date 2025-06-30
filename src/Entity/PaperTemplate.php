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
use Tourze\TestPaperBundle\Repository\PaperTemplateRepository;

#[ORM\Entity(repositoryClass: PaperTemplateRepository::class)]
#[ORM\Table(name: 'test_paper_template', options: ['comment' => '试卷模板'])]
class PaperTemplate implements \Stringable, ApiArrayInterface
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;


    #[ORM\Column(options: ['comment' => '及格分数', 'default' => 60])]
    private int $passScore = 60;
    
    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否打乱题目', 'default' => false])]
    private bool $shuffleQuestions = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否打乱选项', 'default' => false])]
    private bool $shuffleOptions = false;

    #[ORM\Column(length: 120, options: ['comment' => '模板名称'])]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '模板描述'])]
    private ?string $description = null;

    #[ORM\Column(options: ['comment' => '总题数'])]
    private int $totalQuestions = 0;

    #[ORM\Column(options: ['comment' => '总分'])]
    private int $totalScore = 100;

    #[ORM\Column(nullable: true, options: ['comment' => '考试时长（分钟）'])]
    private ?int $timeLimit = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '难度分布配置'])]
    private ?array $difficultyDistribution = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '题型分布配置'])]
    private ?array $questionTypeDistribution = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否启用', 'default' => true])]
    private bool $isActive = true;

    #[ORM\OneToMany(mappedBy: 'template', targetEntity: TemplateRule::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $rules;

    #[ORM\ManyToOne(targetEntity: TestPaper::class, inversedBy: 'templates')]
    #[ORM\JoinColumn(nullable: true)]
    private ?TestPaper $paper = null;

    public function __construct()
    {
        $this->rules = new ArrayCollection();
    }

    public function __toString(): string
    {
        if ($this->getId() === null) {
            return '';
        }

        return $this->getName();
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
        $this->rules->removeElement($rule);
        return $this;
    }

    public function getPaper(): ?TestPaper
    {
        return $this->paper;
    }

    public function setPaper(?TestPaper $paper): static
    {
        $this->paper = $paper;
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

    public function getRuleCount(): int
    {
        return $this->rules->count();
    }/**
     * @return Collection<int, TemplateRule>
     */
    public function getRules(): Collection
    {
        return $this->rules;
    }
}