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
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Tourze\TestPaperBundle\Entity\TemplateRule;

/**
 * 模板规则管理控制器
 *
 * @extends AbstractCrudController<TemplateRule>
 */
#[AdminCrud(routePath: '/test-paper/template-rule', routeName: 'test_paper_template_rule')]
final class TemplateRuleEntityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TemplateRule::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('模板规则')
            ->setEntityLabelInPlural('模板规则管理')
            ->setPageTitle('index', '模板规则管理')
            ->setPageTitle('new', '新建模板规则')
            ->setPageTitle('edit', '编辑模板规则')
            ->setPageTitle('detail', '模板规则详情')
            ->setDefaultSort(['sort' => 'ASC', 'id' => 'DESC'])
            ->setPaginatorPageSize(30)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('template')
            ->add('questionType')
            ->add('difficulty')
            ->add('questionCount')
            ->add('scorePerQuestion')
            ->add('excludeUsed')
            ->add('createTime')
            ->add('updateTime')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')
                ->hideOnForm(),

            AssociationField::new('template', '所属模板')
                ->setRequired(true)
                ->setHelp('该规则所属的试卷模板'),

            TextField::new('categoryId', '题目分类ID')
                ->setRequired(false)
                ->setHelp('题目分类ID（来自question-bank-bundle）')
                ->setMaxLength(255),

            TextField::new('questionType', '题目类型')
                ->setRequired(false)
                ->setHelp('题目类型，如单选题、多选题、填空题等')
                ->setMaxLength(255),

            TextField::new('difficulty', '难度等级')
                ->setRequired(false)
                ->setHelp('题目难度等级，如简单、中等、困难')
                ->setMaxLength(255),

            IntegerField::new('questionCount', '题目数量')
                ->setRequired(true)
                ->setHelp('该规则需要抽取的题目数量')
                ->setFormTypeOptions(['attr' => ['min' => 1]]),

            IntegerField::new('scorePerQuestion', '每题分数')
                ->setRequired(true)
                ->setHelp('该规则中每题的分数')
                ->setFormTypeOptions(['attr' => ['min' => 1]]),

            IntegerField::new('sort', '排序')
                ->setRequired(false)
                ->setHelp('规则在模板中的排序，数字越小越靠前')
                ->setFormTypeOptions(['attr' => ['min' => 0]]),

            CodeEditorField::new('tagFilters', '标签过滤条件')
                ->setLanguage('javascript')
                ->setRequired(false)
                ->setHelp('JSON格式的标签过滤条件，例如：{"tags": ["数学", "基础"], "operator": "AND"}')
                ->formatValue(function ($value) {
                    return is_array($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ($value ?? '{}');
                })
                ->setFormTypeOption('data_class', null)
                ->setFormTypeOption('empty_data', null)
                // 添加数据转换器，将JSON字符串转换为数组
                ->setFormTypeOption('by_reference', false)
                ->setFormTypeOption('getter', function (TemplateRule $entity) {
                    $value = $entity->getTagFilters();

                    return is_array($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '{}';
                })
                ->setFormTypeOption('setter', function (TemplateRule $entity, ?string $value) {
                    if (null === $value || '' === $value || '{}' === $value) {
                        $entity->setTagFilters(null);

                        return;
                    }
                    $decoded = json_decode($value, true);
                    if (is_array($decoded)) {
                        /** @var array<string, mixed> $validDecoded */
                        $validDecoded = $decoded;
                        $entity->setTagFilters($validDecoded);
                    } else {
                        $entity->setTagFilters(null);
                    }
                })
                ->hideOnIndex(),

            NumberField::new('minCorrectRate', '最小正确率')
                ->setRequired(false)
                ->setHelp('题目最小正确率（百分比，0-100）')
                ->setNumDecimals(2)
                ->setFormTypeOptions(['attr' => ['min' => 0, 'max' => 100, 'step' => '0.01']]),

            NumberField::new('maxCorrectRate', '最大正确率')
                ->setRequired(false)
                ->setHelp('题目最大正确率（百分比，0-100）')
                ->setNumDecimals(2)
                ->setFormTypeOptions(['attr' => ['min' => 0, 'max' => 100, 'step' => '0.01']]),

            BooleanField::new('excludeUsed', '排除已使用题目')
                ->setRequired(false)
                ->setHelp('是否排除已使用过的题目'),

            DateTimeField::new('createTime', '创建时间')
                ->hideOnForm()
                ->setFormat('yyyy-MM-dd HH:mm:ss'),

            DateTimeField::new('updateTime', '更新时间')
                ->hideOnForm()
                ->setFormat('yyyy-MM-dd HH:mm:ss'),
        ];
    }
}
