<?php

namespace Tourze\TestPaperBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\QuestionBankBundle\Entity\Question;
use Tourze\TestPaperBundle\Repository\PaperQuestionRepository;

/**
 * @implements ApiArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: PaperQuestionRepository::class)]
#[ORM\Table(name: 'test_paper_question', options: ['comment' => '试卷题目关联表'])]
#[ORM\Index(columns: ['paper_id', 'sort_order'], name: 'test_paper_question_idx_paper_sort')]
class PaperQuestion implements \Stringable, ApiArrayInterface
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;

    #[ORM\ManyToOne(inversedBy: 'paperQuestions', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private TestPaper $paper;

    #[ORM\ManyToOne(targetEntity: Question::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private Question $question;

    #[ORM\Column(options: ['comment' => '排序顺序', 'default' => 0])]
    #[Assert\Range(min: 0)]
    private int $sortOrder = 0;

    #[ORM\Column(options: ['comment' => '题目分数'])]
    #[Assert\Range(min: 1)]
    private int $score = 1;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '自定义选项（用于随机化选项）'])]
    #[Assert\Type(type: 'array')]
    private ?array $customOptions = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否必答题', 'default' => true])]
    #[Assert\Type(type: 'bool')]
    private bool $isRequired = true;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注'])]
    #[Assert\Length(max: 65535)]
    private ?string $remark = null;

    public function __toString(): string
    {
        if (null === $this->getId()) {
            return '';
        }

        return "#{$this->sortOrder} {$this->question}";
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
    public function getCustomOptions(): ?array
    {
        return $this->customOptions;
    }

    /**
     * @param array<string, mixed>|null $customOptions
     */
    public function setCustomOptions(?array $customOptions): void
    {
        $this->customOptions = $customOptions;
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveSecretArray(): array
    {
        $result = $this->retrieveApiArray();
        $result['question'] = $this->getQuestion()->retrieveSecretArray();

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveApiArray(): array
    {
        return [
            'id' => $this->getId(),
            'sortOrder' => $this->getSortOrder(),
            'score' => $this->getScore(),
            'isRequired' => $this->isRequired(),
            'remark' => $this->getRemark(),
            'question' => $this->getQuestion()->retrieveApiArray(),
            'actualOptions' => $this->getActualOptions(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
        ];
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): void
    {
        $this->score = $score;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function setIsRequired(bool $isRequired): void
    {
        $this->isRequired = $isRequired;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }

    public function getQuestion(): Question
    {
        return $this->question;
    }

    public function setQuestion(Question $question): void
    {
        $this->question = $question;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getActualOptions(): array
    {
        if (null !== $this->customOptions) {
            return $this->customOptions;
        }

        return $this->question->getOptions()->toArray();
    }
}
