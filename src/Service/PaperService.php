<?php

namespace Tourze\TestPaperBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\QuestionBankBundle\Entity\Question;
use Tourze\TestPaperBundle\Entity\PaperQuestion;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Enum\PaperGenerationType;
use Tourze\TestPaperBundle\Enum\PaperStatus;
use Tourze\TestPaperBundle\Exception\PaperException;
use Tourze\TestPaperBundle\Repository\PaperQuestionRepository;

/**
 * 试卷管理核心服务
 */
class PaperService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PaperQuestionRepository $paperQuestionRepository
    ) {
    }

    /**
     * 创建空白试卷
     */
    public function createPaper(
        string $title,
        string $description = '',
        int $timeLimit = 3600,
        int $passScore = 60
    ): TestPaper {
        $paper = new TestPaper();
        $paper->setTitle($title);
        $paper->setDescription($description);
        $paper->setTimeLimit($timeLimit);
        $paper->setPassScore($passScore);
        $paper->setStatus(PaperStatus::DRAFT);
        $paper->setGenerationType(PaperGenerationType::MANUAL);
        
        $this->entityManager->persist($paper);
        $this->entityManager->flush();
        
        return $paper;
    }

    /**
     * 添加题目到试卷
     */
    public function addQuestion(
        TestPaper $paper,
        Question $question,
        int $score,
        int $sortOrder = 0
    ): PaperQuestion {
        // 检查题目是否已存在
        $existing = $this->paperQuestionRepository->findOneBy([
            'paper' => $paper,
            'question' => $question
        ]);
        
        if ($existing !== null) {
            throw new PaperException('题目已存在于试卷中');
        }
        
        $paperQuestion = new PaperQuestion();
        $paperQuestion->setPaper($paper);
        $paperQuestion->setQuestion($question);
        $paperQuestion->setScore($score);
        $paperQuestion->setSortOrder($sortOrder !== 0 ? $sortOrder : $this->getNextSortOrder($paper));
        
        $paper->addPaperQuestion($paperQuestion);
        $this->updatePaperStatistics($paper);
        
        $this->entityManager->persist($paperQuestion);
        $this->entityManager->flush();
        
        return $paperQuestion;
    }

    /**
     * 获取下一个排序号
     */
    private function getNextSortOrder(TestPaper $paper): int
    {
        $maxOrder = 0;
        foreach ($paper->getPaperQuestions() as $pq) {
            if ($pq->getSortOrder() > $maxOrder) {
                $maxOrder = $pq->getSortOrder();
            }
        }

        return $maxOrder + 1;
    }

    /**
     * 更新试卷统计信息
     */
    private function updatePaperStatistics(TestPaper $paper): void
    {
        $totalScore = 0;
        $questionCount = 0;

        foreach ($paper->getPaperQuestions() as $paperQuestion) {
            $totalScore += $paperQuestion->getScore();
            $questionCount++;
        }

        $paper->setTotalScore($totalScore);
        $paper->setQuestionCount($questionCount);
    }

    /**
     * 批量添加题目
     */
    public function addQuestions(TestPaper $paper, array $questions): void
    {
        $sortOrder = $this->getNextSortOrder($paper);

        foreach ($questions as $questionData) {
            $question = $questionData['question'] ?? null;
            $score = $questionData['score'] ?? 5;

            if (!$question instanceof Question) {
                continue;
            }

            $paperQuestion = new PaperQuestion();
            $paperQuestion->setPaper($paper);
            $paperQuestion->setQuestion($question);
            $paperQuestion->setScore($score);
            $paperQuestion->setSortOrder($sortOrder++);

            $paper->addPaperQuestion($paperQuestion);
            $this->entityManager->persist($paperQuestion);
        }

        $this->updatePaperStatistics($paper);
        $this->entityManager->flush();
    }

    /**
     * 移除题目
     */
    public function removeQuestion(TestPaper $paper, PaperQuestion $paperQuestion): void
    {
        if ($paperQuestion->getPaper() !== $paper) {
            throw new PaperException('题目不属于该试卷');
        }

        $paper->removePaperQuestion($paperQuestion);
        $this->entityManager->remove($paperQuestion);

        $this->updatePaperStatistics($paper);
        $this->reorderQuestions($paper);

        $this->entityManager->flush();
    }

    /**
     * 重新排序题目
     */
    private function reorderQuestions(TestPaper $paper): void
    {
        $questions = $paper->getPaperQuestions()->toArray();
        usort($questions, fn($a, $b) => $a->getSortOrder() <=> $b->getSortOrder());

        $sortOrder = 1;
        foreach ($questions as $paperQuestion) {
            $paperQuestion->setSortOrder($sortOrder++);
        }
    }

    /**
     * 更新题目顺序
     */
    public function updateQuestionOrder(TestPaper $paper, array $orderMapping): void
    {
        foreach ($paper->getPaperQuestions() as $paperQuestion) {
            $questionId = $paperQuestion->getId();
            if (isset($orderMapping[$questionId])) {
                $paperQuestion->setSortOrder($orderMapping[$questionId]);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * 随机打乱题目顺序
     */
    public function shuffleQuestions(TestPaper $paper): void
    {
        $questions = $paper->getPaperQuestions()->toArray();
        shuffle($questions);

        $sortOrder = 1;
        foreach ($questions as $paperQuestion) {
            $paperQuestion->setSortOrder($sortOrder++);
        }

        $paper->setRandomizeQuestions(true);
        $this->entityManager->flush();
    }

    /**
     * 随机打乱选项顺序
     */
    public function shuffleOptions(TestPaper $paper): void
    {
        foreach ($paper->getPaperQuestions() as $paperQuestion) {
            $question = $paperQuestion->getQuestion();

            // 只处理有选项的题型
            if (!in_array($question->getType(), ['single_choice', 'multiple_choice'])) {
                continue;
            }

            $options = $question->getOptions();
            if ($options === null || $options->isEmpty()) {
                continue;
            }
            
            $optionsArray = $options->toArray();

            // 保存原始答案索引
            $correctAnswers = [];
            $apiArray = $question->retrieveApiArray();
            $correctLetters = $apiArray['correctLetters'] ?? [];
            foreach ($correctLetters as $letter) {
                if (isset($optionsArray[$letter])) {
                    $correctAnswers[] = $optionsArray[$letter];
                }
            }

            // 打乱选项
            $shuffledOptions = $optionsArray;
            shuffle($shuffledOptions);

            // 更新正确答案索引
            $newCorrectAnswers = [];
            foreach ($correctAnswers as $correctAnswer) {
                $newIndex = array_search($correctAnswer, $shuffledOptions);
                if ($newIndex !== false) {
                    $newCorrectAnswers[] = $newIndex;
                }
            }

            // 保存自定义选项
            $paperQuestion->setCustomOptions([
                'options' => $shuffledOptions,
                'correctAnswer' => $newCorrectAnswers
            ]);
        }

        $paper->setRandomizeOptions(true);
        $this->entityManager->flush();
    }

    /**
     * 发布试卷
     */
    public function publishPaper(TestPaper $paper): void
    {
        if ($paper->getStatus() !== PaperStatus::DRAFT) {
            throw new PaperException('只有草稿状态的试卷可以发布');
        }

        if ($paper->getQuestionCount() === 0) {
            throw new PaperException('试卷中没有题目');
        }

        $paper->setStatus(PaperStatus::PUBLISHED);
        $this->entityManager->flush();
    }

    /**
     * 归档试卷
     */
    public function archivePaper(TestPaper $paper): void
    {
        if ($paper->getStatus() !== PaperStatus::PUBLISHED) {
            throw new PaperException('只有已发布的试卷可以归档');
        }

        $paper->setStatus(PaperStatus::ARCHIVED);
        $this->entityManager->flush();
    }

    /**
     * 复制试卷
     */
    public function duplicatePaper(TestPaper $originalPaper, string $newTitle): TestPaper
    {
        $newPaper = new TestPaper();
        $newPaper->setTitle($newTitle);
        $newPaper->setDescription($originalPaper->getDescription());
        $newPaper->setTimeLimit($originalPaper->getTimeLimit());
        $newPaper->setTotalScore($originalPaper->getTotalScore());
        $newPaper->setPassScore($originalPaper->getPassScore());
        $newPaper->setQuestionCount($originalPaper->getQuestionCount());
        $newPaper->setGenerationType($originalPaper->getGenerationType());
        $newPaper->setStatus(PaperStatus::DRAFT);

        // 复制试卷设置
        $newPaper->setAllowRetake($originalPaper->isAllowRetake());
        $newPaper->setMaxAttempts($originalPaper->getMaxAttempts());

        $this->entityManager->persist($newPaper);

        // 复制题目
        foreach ($originalPaper->getPaperQuestions() as $originalPQ) {
            $newPQ = new PaperQuestion();
            $newPQ->setPaper($newPaper);
            $newPQ->setQuestion($originalPQ->getQuestion());
            $newPQ->setSortOrder($originalPQ->getSortOrder());
            $newPQ->setScore($originalPQ->getScore());
            $newPQ->setIsRequired($originalPQ->isRequired());
            $newPQ->setRemark($originalPQ->getRemark());

            $newPaper->addPaperQuestion($newPQ);
            $this->entityManager->persist($newPQ);
        }

        $this->entityManager->flush();

        return $newPaper;
    }
}