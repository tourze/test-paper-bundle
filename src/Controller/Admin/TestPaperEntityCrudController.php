<?php

declare(strict_types=1);

namespace Tourze\TestPaperBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\TestPaperBundle\Entity\TestPaper;
use Tourze\TestPaperBundle\Enum\PaperGenerationType;
use Tourze\TestPaperBundle\Enum\PaperStatus;

/**
 * 试卷管理控制器
 *
 * @extends AbstractCrudController<TestPaper>
 */
#[AdminCrud(routePath: '/test-paper/paper', routeName: 'test_paper_paper')]
final class TestPaperEntityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TestPaper::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('试卷')
            ->setEntityLabelInPlural('试卷管理')
            ->setPageTitle('index', '试卷管理')
            ->setPageTitle('new', '创建试卷')
            ->setPageTitle('edit', '编辑试卷')
            ->setPageTitle('detail', '试卷详情')
            ->setHelp('index', '管理考试试卷，支持手动组卷、模板组卷和智能组卷')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['title', 'description'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('title')
            ->add('status')
            ->add('generationType')
            ->add('totalScore')
            ->add('passScore')
            ->add('allowRetake')
            ->add('createTime')
            ->add('updateTime')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')
                ->hideOnForm(),

            TextField::new('title', '试卷标题')
                ->setRequired(true)
                ->setHelp('请输入试卷标题，最多120个字符')
                ->setMaxLength(120),

            TextareaField::new('description', '试卷描述')
                ->setRequired(false)
                ->setHelp('试卷的详细描述信息')
                ->setFormTypeOptions(['attr' => ['rows' => 4]]),

            (function () {
                $statusField = EnumField::new('status', '试卷状态');
                $statusField->setEnumCases(PaperStatus::cases());

                return $statusField
                    ->setRequired(true)
                    ->setHelp('试卷当前状态')
                    ->setFormTypeOption('placeholder', '请选择状态')
                    ->setFormTypeOption('empty_data', PaperStatus::DRAFT)
                    ->renderAsBadges([
                        PaperStatus::DRAFT->value => 'secondary',
                        PaperStatus::PUBLISHED->value => 'success',
                        PaperStatus::ARCHIVED->value => 'warning',
                        PaperStatus::CLOSED->value => 'danger',
                    ])
                ;
            })(),

            (function () {
                $generationTypeField = EnumField::new('generationType', '组卷方式');
                $generationTypeField->setEnumCases(PaperGenerationType::cases());

                return $generationTypeField
                    ->setRequired(true)
                    ->setHelp('试卷的组卷方式')
                    ->setFormTypeOption('placeholder', '请选择组卷方式')
                    ->setFormTypeOption('empty_data', PaperGenerationType::MANUAL)
                    ->renderAsBadges([
                        PaperGenerationType::MANUAL->value => 'primary',
                        PaperGenerationType::TEMPLATE->value => 'info',
                        PaperGenerationType::RANDOM->value => 'warning',
                        PaperGenerationType::INTELLIGENT->value => 'success',
                        PaperGenerationType::ADAPTIVE->value => 'dark',
                    ])
                ;
            })(),

            IntegerField::new('totalScore', '总分')
                ->setRequired(true)
                ->setHelp('试卷总分')
                ->setFormTypeOptions(['attr' => ['min' => 0]]),

            IntegerField::new('passScore', '及格分数')
                ->setRequired(true)
                ->setHelp('试卷及格分数')
                ->setFormTypeOptions(['attr' => ['min' => 0]]),

            IntegerField::new('timeLimit', '考试时长（秒）')
                ->setRequired(false)
                ->setHelp('考试时长限制，单位为秒，留空表示无时长限制')
                ->setFormTypeOptions(['attr' => ['min' => 0]]),

            IntegerField::new('questionCount', '题目总数')
                ->setRequired(true)
                ->setHelp('试卷包含的题目总数')
                ->setFormTypeOptions(['attr' => ['min' => 0]]),

            BooleanField::new('randomizeQuestions', '随机排序题目')
                ->setRequired(false)
                ->setHelp('是否随机排序试卷中的题目'),

            BooleanField::new('randomizeOptions', '随机排序选项')
                ->setRequired(false)
                ->setHelp('是否随机排序题目的选项'),

            BooleanField::new('allowRetake', '允许重做')
                ->setRequired(false)
                ->setHelp('是否允许考生重新参加考试'),

            IntegerField::new('maxAttempts', '最大重做次数')
                ->setRequired(false)
                ->setHelp('最大允许的重做次数，留空表示无限制')
                ->setFormTypeOptions(['attr' => ['min' => 0]]),

            AssociationField::new('paperQuestions', '试卷题目')
                ->setRequired(false)
                ->setHelp('试卷包含的题目列表')
                ->hideOnIndex(),

            AssociationField::new('templates', '模板列表')
                ->setRequired(false)
                ->setHelp('试卷关联的模板列表')
                ->hideOnIndex(),

            AssociationField::new('sessions', '考试会话')
                ->setRequired(false)
                ->setHelp('基于此试卷的考试会话列表')
                ->hideOnIndex(),

            DateTimeField::new('createTime', '创建时间')
                ->hideOnForm()
                ->setFormat('yyyy-MM-dd HH:mm:ss'),

            DateTimeField::new('updateTime', '更新时间')
                ->hideOnForm()
                ->setFormat('yyyy-MM-dd HH:mm:ss'),
        ];
    }
}
