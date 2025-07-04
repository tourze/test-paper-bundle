<?php

namespace Tourze\TestPaperBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\TestPaperBundle\Repository\TemplateRuleRepository;

#[ORM\Entity(repositoryClass: TemplateRuleRepository::class)]
#[ORM\Table(name: 'test_template_rule', options: ['comment' => '模板规则'])]
class TemplateRule implements \Stringable, ApiArrayInterface
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;


    #[ORM\ManyToOne(inversedBy: 'rules')]
    #[ORM\JoinColumn(nullable: false)]
    private PaperTemplate $template;

    #[ORM\Column(nullable: true, options: ['comment' => '题目分类ID（来自question-bank-bundle）'])]
    private ?string $categoryId = null;

    #[ORM\Column(nullable: true, options: ['comment' => '题目类型'])]
    private ?string $questionType = null;

    #[ORM\Column(nullable: true, options: ['comment' => '难度等级'])]
    private ?string $difficulty = null;

    #[ORM\Column(options: ['comment' => '题目数量'])]
    private int $questionCount = 1;

    #[ORM\Column(options: ['comment' => '每题分数'])]
    private int $scorePerQuestion = 1;

    #[ORM\Column(options: ['comment' => '排序', 'default' => 0])]
    private int $sort = 0;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '标签过滤条件'])]
    private ?array $tagFilters = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => '最小正确率'])]
    private ?string $minCorrectRate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => '最大正确率'])]
    private ?string $maxCorrectRate = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '排除已使用题目', 'default' => false])]
    private bool $excludeUsed = false;

    public function __toString(): string
    {
        if ($this->getId() === null) {
            return '';
        }

        $parts = [];
        if ($this->categoryId !== null) {
            $parts[] = "分类:{$this->categoryId}";
        }
        if ($this->questionType !== null) {
            $parts[] = $this->questionType;
        }
        if ($this->difficulty !== null) {
            $parts[] = $this->difficulty;
        }
        $parts[] = "{$this->questionCount}题";

        return implode(' - ', $parts);
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
    }}