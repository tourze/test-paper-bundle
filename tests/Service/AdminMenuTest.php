<?php

declare(strict_types=1);

namespace Tourze\TestPaperBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use Tourze\TestPaperBundle\Service\AdminMenu;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    protected function onSetUp(): void
    {
        // Base class handles setup
    }

    protected function getMenuProviderService(): AdminMenu
    {
        return self::getService(AdminMenu::class);
    }

    public function testMenuProviderAddsTrialPaperManagementMenu(): void
    {
        $adminMenu = $this->getMenuProviderService();
        $item = $this->createMock(ItemInterface::class);
        $subMenu = $this->createMock(ItemInterface::class);

        // Mock main item behavior - simulate first call returns null, second call returns submenu
        $callCount = 0;
        $item->method('getChild')
            ->willReturnCallback(function () use ($subMenu, &$callCount) {
                ++$callCount;

                return 1 === $callCount ? null : $subMenu;
            })
        ;

        $item->expects($this->once())
            ->method('addChild')
            ->with('试卷管理')
            ->willReturn($subMenu)
        ;

        // Mock submenu behavior
        $subMenu->method('setAttribute')
            ->willReturn($subMenu)
        ;
        $subMenu->method('addChild')
            ->willReturn($subMenu)
        ;
        $subMenu->method('setUri')
            ->willReturn($subMenu)
        ;

        // Execute the test
        $adminMenu($item);

        // Mock expectations above provide the verification
    }

    public function testMenuProviderHandlesExistingMenu(): void
    {
        $adminMenu = $this->getMenuProviderService();
        $item = $this->createMock(ItemInterface::class);
        $subMenu = $this->createMock(ItemInterface::class);

        // Mock that main menu already exists
        $item->method('getChild')
            ->with('试卷管理')
            ->willReturn($subMenu)
        ;

        // Mock submenu behavior
        $subMenu->method('addChild')
            ->willReturn($subMenu)
        ;
        $subMenu->method('setUri')
            ->willReturn($subMenu)
        ;
        $subMenu->method('setAttribute')
            ->willReturn($subMenu)
        ;

        // Execute the test
        $adminMenu($item);

        // Mock expectations provide verification
    }
}
