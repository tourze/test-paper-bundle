<?php

declare(strict_types=1);

namespace Tourze\TestPaperBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\TestPaperBundle\Entity\PaperTemplate;

class PaperTemplateFixtures extends Fixture
{
    public const PAPER_TEMPLATE_1 = 'paper-template-1';

    public function load(ObjectManager $manager): void
    {
        $template = new PaperTemplate();
        $template->setName('测试模板1');
        $template->setDescription('这是一个用于测试的试卷模板');
        $template->setPassScore(60);
        $template->setShuffleQuestions(false);
        $template->setShuffleOptions(false);
        $template->setTotalQuestions(10);
        $template->setTotalScore(100);

        $manager->persist($template);
        $manager->flush();

        $this->addReference(self::PAPER_TEMPLATE_1, $template);
    }
}
