<?php

declare(strict_types=1);

namespace Tourze\TestPaperBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
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
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use Tourze\QuestionBankBundle\Entity\Question;
use Tourze\TestPaperBundle\Entity\PaperQuestion;
use Tourze\TestPaperBundle\Entity\TestPaper;

/**
 * 试卷题目关联管理控制器
 *
 * @extends AbstractCrudController<PaperQuestion>
 */
#[AdminCrud(routePath: '/test-paper/paper-question', routeName: 'test_paper_paper_question')]
final class PaperQuestionEntityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PaperQuestion::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('试卷题目')
            ->setEntityLabelInPlural('试卷题目管理')
            ->setPageTitle('index', '试卷题目管理')
            ->setPageTitle('new', '新建试卷题目')
            ->setPageTitle('edit', '编辑试卷题目')
            ->setPageTitle('detail', '试卷题目详情')
            ->setDefaultSort(['sortOrder' => 'ASC', 'id' => 'DESC'])
            ->setPaginatorPageSize(30)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('paper')
            ->add('question')
            ->add(NumericFilter::new('sortOrder', '排序顺序'))
            ->add(NumericFilter::new('score', '题目分数'))
            ->add(BooleanFilter::new('isRequired', '是否必答'))
            ->add('createTime')
            ->add('updateTime')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')
                ->hideOnForm(),

            AssociationField::new('paper', '所属试卷')
                ->setRequired(true)
                ->setHelp('选择该题目所属的试卷')
                ->autocomplete()
                ->formatValue(function ($value) {
                    return ($value instanceof TestPaper) ? $value->getTitle() : '';
                }),

            AssociationField::new('question', '关联题目')
                ->setRequired(true)
                ->setHelp('选择要添加到试卷的题目')
                ->autocomplete()
                ->formatValue(function ($value) {
                    return ($value instanceof Question) ? $value->getTitle() : '';
                }),

            IntegerField::new('sortOrder', '排序顺序')
                ->setRequired(true)
                ->setHelp('题目在试卷中的排序位置，数字越小排序越靠前')
                ->setFormTypeOptions(['attr' => ['min' => 0]]),

            IntegerField::new('score', '题目分数')
                ->setRequired(true)
                ->setHelp('该题目在试卷中的分值，必须大于0')
                ->setFormTypeOptions(['attr' => ['min' => 1]]),

            BooleanField::new('isRequired', '是否必答')
                ->setRequired(false)
                ->setHelp('是否为必答题目'),

            CodeEditorField::new('customOptions', '自定义选项')
                ->setLanguage('javascript')
                ->setRequired(false)
                ->setHelp('JSON格式的自定义选项配置，用于随机化选项等功能')
                ->formatValue(function ($value) {
                    return is_array($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ($value ?? '{}');
                })
                ->setFormTypeOption('data_class', null)
                ->setFormTypeOption('empty_data', null)
                ->onlyOnDetail()
                ->hideOnIndex(),

            TextareaField::new('remark', '备注')
                ->setRequired(false)
                ->setHelp('对该题目在试卷中的特殊说明或备注信息')
                ->setFormTypeOptions(['attr' => ['rows' => 3]])
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
