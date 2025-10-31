<?php

namespace Tourze\TestPaperBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\TestPaperBundle\Repository\TemplateRuleRepository;

/**
 * @implements ApiArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: TemplateRuleRepository::class)]
#[ORM\Table(name: 'test_template_rule', options: ['comment' => '模板规则'])]
class TemplateRule implements \Stringable, ApiArrayInterface
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;

    #[ORM\ManyToOne(inversedBy: 'rules', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private PaperTemplate $template;

    #[ORM\Column(nullable: true, options: ['comment' => '题目分类ID（来自question-bank-bundle）'])]
    #[Assert\Length(max: 255)]
    private ?string $categoryId = null;

    #[ORM\Column(nullable: true, options: ['comment' => '题目类型'])]
    #[Assert\Length(max: 255)]
    private ?string $questionType = null;

    #[ORM\Column(nullable: true, options: ['comment' => '难度等级'])]
    #[Assert\Length(max: 255)]
    private ?string $difficulty = null;

    #[ORM\Column(options: ['comment' => '题目数量'])]
    #[Assert\Range(min: 1)]
    private int $questionCount = 1;

    #[ORM\Column(options: ['comment' => '每题分数'])]
    #[Assert\Range(min: 1)]
    private int $scorePerQuestion = 1;

    #[ORM\Column(options: ['comment' => '排序', 'default' => 0])]
    #[Assert\Range(min: 0)]
    private int $sort = 0;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '标签过滤条件'])]
    #[Assert\Type(type: 'array')]
    private ?array $tagFilters = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => '最小正确率'])]
    #[Assert\Range(min: 0, max: 100)]
    #[Assert\Length(max: 6)]
    private ?string $minCorrectRate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => '最大正确率'])]
    #[Assert\Range(min: 0, max: 100)]
    #[Assert\Length(max: 6)]
    private ?string $maxCorrectRate = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '排除已使用题目', 'default' => false])]
    #[Assert\Type(type: 'bool')]
    private bool $excludeUsed = false;

    public function __toString(): string
    {
        if (null === $this->getId()) {
            return '';
        }

        $parts = [];
        if (null !== $this->categoryId) {
            $parts[] = "分类:{$this->categoryId}";
        }
        if (null !== $this->questionType) {
            $parts[] = $this->questionType;
        }
        if (null !== $this->difficulty) {
            $parts[] = $this->difficulty;
        }
        $parts[] = "{$this->questionCount}题";

        return implode(' - ', $parts);
    }

    public function getTemplate(): PaperTemplate
    {
        return $this->template;
    }

    public function setTemplate(PaperTemplate $template): void
    {
        $this->template = $template;
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

    public function setCategoryId(?string $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function getQuestionType(): ?string
    {
        return $this->questionType;
    }

    public function setQuestionType(?string $questionType): void
    {
        $this->questionType = $questionType;
    }

    public function getDifficulty(): ?string
    {
        return $this->difficulty;
    }

    public function setDifficulty(?string $difficulty): void
    {
        $this->difficulty = $difficulty;
    }

    public function getQuestionCount(): int
    {
        return $this->questionCount;
    }

    public function setQuestionCount(int $questionCount): void
    {
        $this->questionCount = $questionCount;
    }

    public function getScorePerQuestion(): int
    {
        return $this->scorePerQuestion;
    }

    public function setScorePerQuestion(int $scorePerQuestion): void
    {
        $this->scorePerQuestion = $scorePerQuestion;
    }

    public function getTotalScore(): int
    {
        return $this->questionCount * $this->scorePerQuestion;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort): void
    {
        $this->sort = $sort;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getTagFilters(): ?array
    {
        return $this->tagFilters;
    }

    /**
     * @param array<string, mixed>|null $tagFilters
     */
    public function setTagFilters(?array $tagFilters): void
    {
        $this->tagFilters = $tagFilters;
    }

    public function getMinCorrectRate(): ?string
    {
        return $this->minCorrectRate;
    }

    public function setMinCorrectRate(?string $minCorrectRate): void
    {
        $this->minCorrectRate = $minCorrectRate;
    }

    public function getMaxCorrectRate(): ?string
    {
        return $this->maxCorrectRate;
    }

    public function setMaxCorrectRate(?string $maxCorrectRate): void
    {
        $this->maxCorrectRate = $maxCorrectRate;
    }

    public function isExcludeUsed(): bool
    {
        return $this->excludeUsed;
    }

    public function setExcludeUsed(bool $excludeUsed): void
    {
        $this->excludeUsed = $excludeUsed;
    }
}
