<?php

declare(strict_types=1);

namespace Tourze\TestPaperBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\TestPaperBundle\Entity\TestSession;
use Tourze\TestPaperBundle\Enum\SessionStatus;

/**
 * 考试会话管理控制器
 *
 * @extends AbstractCrudController<TestSession>
 */
#[AdminCrud(routePath: '/test-paper/session', routeName: 'test_paper_session')]
final class TestSessionEntityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TestSession::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('考试会话')
            ->setEntityLabelInPlural('考试会话管理')
            ->setPageTitle('index', '考试会话列表')
            ->setPageTitle('new', '新建考试会话')
            ->setPageTitle('edit', '编辑考试会话')
            ->setPageTitle('detail', '考试会话详情')
            ->setHelp('index', '管理考试会话，查看学员考试记录、答题情况和统计信息')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'user.userIdentifier', 'paper.title'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        yield AssociationField::new('paper', '试卷')
            ->setRequired(true)
            ->setHelp('考试使用的试卷')
            ->setColumns(6)
        ;

        yield AssociationField::new('user', '用户')
            ->setRequired(true)
            ->setHelp('参加考试的用户')
            ->setColumns(6)
        ;

        $statusField = EnumField::new('status', '会话状态');
        $statusField->setEnumCases(SessionStatus::cases());
        yield $statusField
            ->setRequired(true)
            ->setHelp('当前考试会话的状态')
            ->setColumns(4)
            ->setFormTypeOption('placeholder', '请选择状态')
            ->setFormTypeOption('empty_data', SessionStatus::PENDING)
        ;

        yield DateTimeField::new('startTime', '开始时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('考试开始的时间')
            ->setColumns(4)
            ->onlyOnForms()
        ;

        yield DateTimeField::new('startTime', '开始时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
        ;

        yield DateTimeField::new('endTime', '结束时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('考试结束的时间')
            ->setColumns(4)
            ->onlyOnForms()
        ;

        yield DateTimeField::new('endTime', '结束时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
            ->hideOnIndex()
        ;

        yield DateTimeField::new('expiresAt', '到期时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('考试的截止时间')
            ->setColumns(4)
        ;

        yield IntegerField::new('score', '得分')
            ->setHelp('考试获得的分数')
            ->setColumns(3)
        ;

        yield IntegerField::new('totalScore', '总分')
            ->setHelp('考试的满分')
            ->setColumns(3)
        ;

        yield IntegerField::new('attemptNumber', '尝试次数')
            ->setRequired(true)
            ->setHelp('第几次参加该试卷考试')
            ->setColumns(3)
            ->setFormTypeOptions(['attr' => ['min' => 1]])
        ;

        yield IntegerField::new('duration', '用时(秒)')
            ->setHelp('考试用时，单位为秒')
            ->setColumns(3)
        ;

        yield BooleanField::new('passed', '是否通过')
            ->setHelp('考试是否达到通过标准')
        ;

        // JSON字段 - 用户答案（只读，用于查看）
        yield CodeEditorField::new('answers', '用户答案')
            ->setLanguage('javascript')
            ->setHelp('学员提交的所有答案，JSON格式')
            ->hideOnIndex()
            ->hideOnForm()
            ->onlyOnDetail()
            ->formatValue(function ($value) {
                return is_array($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '{}';
            })
        ;

        // JSON字段 - 题目答题时间记录（只读，用于查看）
        yield CodeEditorField::new('questionTimings', '答题时间记录')
            ->setLanguage('javascript')
            ->setHelp('每道题目的答题时间记录，JSON格式')
            ->hideOnIndex()
            ->hideOnForm()
            ->onlyOnDetail()
            ->formatValue(function ($value) {
                return is_array($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '{}';
            })
        ;

        // 当前题目相关字段（主要用于查看正在进行的考试状态）
        yield TextField::new('currentQuestionId', '当前题目ID')
            ->setHelp('正在答题的题目ID')
            ->hideOnIndex()
            ->onlyOnDetail()
        ;

        yield DateTimeField::new('currentQuestionStartTime', '当前题目开始时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('开始答当前题目的时间')
            ->hideOnIndex()
            ->onlyOnDetail()
        ;

        yield TextareaField::new('remark', '备注')
            ->setHelp('考试相关的备注信息')
            ->setNumOfRows(3)
            ->hideOnIndex()
        ;

        // 虚拟字段 - 得分百分比
        yield TextField::new('scorePercentage', '得分率')
            ->setVirtual(true)
            ->setHelp('得分占总分的百分比')
            ->hideOnForm()
            ->onlyOnDetail()
            ->formatValue(function ($value, TestSession $entity) {
                $percentage = $entity->getScorePercentage();

                return null !== $percentage ? $percentage . '%' : '未计算';
            })
        ;

        // 虚拟字段 - 剩余时间
        yield TextField::new('remainingTime', '剩余时间')
            ->setVirtual(true)
            ->setHelp('考试剩余时间（秒）')
            ->hideOnForm()
            ->onlyOnDetail()
            ->formatValue(function ($value, TestSession $entity) {
                $remaining = $entity->getRemainingTime();
                if (null === $remaining) {
                    return '无限制';
                }
                $minutes = intval($remaining / 60);
                $seconds = $remaining % 60;

                return $minutes . '分' . $seconds . '秒';
            })
        ;

        // 虚拟字段 - 是否过期
        yield BooleanField::new('isExpired', '是否过期')
            ->setVirtual(true)
            ->setHelp('考试是否已过期')
            ->hideOnForm()
            ->onlyOnDetail()
            ->formatValue(function ($value, TestSession $entity) {
                return $entity->isExpired();
            })
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW)  // 考试会话通常由系统创建，不允许手动新建
            ->disable(Action::DELETE)  // 考试记录不应被删除
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('paper'))
            ->add(EntityFilter::new('user'))
            ->add('status')
            ->add(BooleanFilter::new('passed'))
            ->add(NumericFilter::new('score'))
            ->add(NumericFilter::new('attemptNumber'))
            ->add(DateTimeFilter::new('startTime'))
            ->add(DateTimeFilter::new('endTime'))
            ->add(DateTimeFilter::new('expiresAt'))
            ->add(DateTimeFilter::new('createTime'))
        ;
    }
}
