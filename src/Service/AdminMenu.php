<?php

declare(strict_types=1);

namespace Tourze\TestPaperBundle\Service;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\TestPaperBundle\Entity\PaperQuestion;
use Tourze\TestPaperBundle\Entity\PaperTemplate;
use Tourze\TestPaperBundle\Entity\TemplateRule;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Entity\TestSession;

/**
 * 试卷管理后台菜单提供者
 */
#[Autoconfigure(public: true)]
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('试卷管理')) {
            $item->addChild('试卷管理')
                ->setAttribute('icon', 'fas fa-file-alt')
            ;
        }

        $testPaperMenu = $item->getChild('试卷管理');
        if (null === $testPaperMenu) {
            return;
        }

        // 试卷管理
        $testPaperMenu->addChild('试卷管理')
            ->setUri($this->linkGenerator->getCurdListPage(TestPaper::class))
            ->setAttribute('icon', 'fas fa-clipboard-list')
        ;

        // 试卷模板
        $testPaperMenu->addChild('试卷模板')
            ->setUri($this->linkGenerator->getCurdListPage(PaperTemplate::class))
            ->setAttribute('icon', 'fas fa-file-contract')
        ;

        // 模板规则
        $testPaperMenu->addChild('模板规则')
            ->setUri($this->linkGenerator->getCurdListPage(TemplateRule::class))
            ->setAttribute('icon', 'fas fa-cogs')
        ;

        // 试卷题目
        $testPaperMenu->addChild('试卷题目')
            ->setUri($this->linkGenerator->getCurdListPage(PaperQuestion::class))
            ->setAttribute('icon', 'fas fa-question-circle')
        ;

        // 考试会话
        $testPaperMenu->addChild('考试会话')
            ->setUri($this->linkGenerator->getCurdListPage(TestSession::class))
            ->setAttribute('icon', 'fas fa-user-clock')
        ;
    }
}
