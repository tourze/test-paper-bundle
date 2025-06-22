<?php

namespace Tourze\TestPaperBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\QuestionBankBundle\Entity\Question;
use Tourze\TestPaperBundle\Repository\PaperQuestionRepository;

#[ORM\Entity(repositoryClass: PaperQuestionRepository::class)]
#[ORM\Table(name: 'test_paper_question', options: ['comment' => '试卷题目关联表'])]
#[ORM\Index(columns: ['paper_id', 'sort_order'], name: 'idx_paper_sort')]
class PaperQuestion implements \Stringable, ApiArrayInterface
{
    use TimestampableAware;
    use BlameableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[ORM\ManyToOne(inversedBy: 'paperQuestions')]
    #[ORM\JoinColumn(nullable: false)]
    private TestPaper $paper;

    #[ORM\ManyToOne(targetEntity: Question::class)]
    #[ORM\JoinColumn(referencedColumnName: 'uuid', nullable: false)]
    private Question $question;

    #[ORM\Column(options: ['comment' => '排序顺序', 'default' => 0])]
    private int $sortOrder = 0;

    #[ORM\Column(options: ['comment' => '题目分数'])]
    private int $score = 1;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '自定义选项（用于随机化选项）'])]
    private ?array $customOptions = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否必答题', 'default' => true])]
    private bool $isRequired = true;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注'])]
    private ?string $remark = null;

    public function __toString(): string
    {
        if ($this->getId() === null) {
            return '';
        }

        return "#{$this->sortOrder} {$this->question}";
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getPaper(): TestPaper
    {
        return $this->paper;
    }

    public function setPaper(TestPaper $paper): static
    {
        $this->paper = $paper;
        return $this;
    }

    public function getCustomOptions(): ?array
    {
        return $this->customOptions;
    }

    public function setCustomOptions(?array $customOptions): static
    {
        $this->customOptions = $customOptions;
        return $this;
    }

    public function retrieveSecretArray(): array
    {
        $result = $this->retrieveApiArray();
        $result['question'] = $this->getQuestion()->retrieveSecretArray();
        return $result;
    }

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

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): static
    {
        $this->score = $score;
        return $this;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function setIsRequired(bool $isRequired): static
    {
        $this->isRequired = $isRequired;
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

    public function getQuestion(): Question
    {
        return $this->question;
    }

    public function setQuestion(Question $question): static
    {
        $this->question = $question;
        return $this;
    }

    public function getActualOptions(): array
    {
        return $this->customOptions ?? $this->question->getOptions()->toArray() ?? [];
    }
}
