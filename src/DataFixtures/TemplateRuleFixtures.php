<?php

declare(strict_types=1);

namespace Tourze\TestPaperBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\TestPaperBundle\Entity\PaperTemplate;
use Tourze\TestPaperBundle\Entity\TemplateRule;

class TemplateRuleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $template = $this->getReference(PaperTemplateFixtures::PAPER_TEMPLATE_1, PaperTemplate::class);

        $rule1 = new TemplateRule();
        $rule1->setTemplate($template);
        $rule1->setQuestionCount(5);
        $rule1->setScorePerQuestion(10);
        $rule1->setSort(1);

        $rule2 = new TemplateRule();
        $rule2->setTemplate($template);
        $rule2->setQuestionCount(5);
        $rule2->setScorePerQuestion(10);
        $rule2->setSort(2);

        $manager->persist($rule1);
        $manager->persist($rule2);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            PaperTemplateFixtures::class,
        ];
    }
}
