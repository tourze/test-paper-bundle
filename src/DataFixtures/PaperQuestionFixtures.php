<?php

declare(strict_types=1);

namespace Tourze\TestPaperBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\QuestionBankBundle\DataFixtures\QuestionFixtures;
use Tourze\QuestionBankBundle\Entity\Question;
use Tourze\TestPaperBundle\Entity\PaperQuestion;
use Tourze\TestPaperBundle\Entity\TestPaper;

class PaperQuestionFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $testPaper = $this->getReference(TestPaperFixtures::TEST_PAPER_1, TestPaper::class);

        $question1 = $this->getReference(QuestionFixtures::QUESTION_SINGLE_CHOICE, Question::class);

        $question2 = $this->getReference(QuestionFixtures::QUESTION_MULTIPLE_CHOICE, Question::class);

        $question3 = $this->getReference(QuestionFixtures::QUESTION_TRUE_FALSE, Question::class);

        // 创建试卷题目关联
        $paperQuestion1 = new PaperQuestion();
        $paperQuestion1->setPaper($testPaper);
        $paperQuestion1->setQuestion($question1);
        $paperQuestion1->setSortOrder(1);
        $paperQuestion1->setScore(10);
        $paperQuestion1->setIsRequired(true);

        $paperQuestion2 = new PaperQuestion();
        $paperQuestion2->setPaper($testPaper);
        $paperQuestion2->setQuestion($question2);
        $paperQuestion2->setSortOrder(2);
        $paperQuestion2->setScore(20);
        $paperQuestion2->setIsRequired(true);

        $paperQuestion3 = new PaperQuestion();
        $paperQuestion3->setPaper($testPaper);
        $paperQuestion3->setQuestion($question3);
        $paperQuestion3->setSortOrder(3);
        $paperQuestion3->setScore(5);
        $paperQuestion3->setIsRequired(true);

        $manager->persist($paperQuestion1);
        $manager->persist($paperQuestion2);
        $manager->persist($paperQuestion3);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            TestPaperFixtures::class,
            QuestionFixtures::class,
        ];
    }
}
