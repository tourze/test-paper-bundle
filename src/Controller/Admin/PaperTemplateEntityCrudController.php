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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Tourze\TestPaperBundle\Entity\PaperTemplate;

/**
 * 试卷模板管理控制器
 *
 * @extends AbstractCrudController<PaperTemplate>
 */
#[AdminCrud(routePath: '/test-paper/template', routeName: 'test_paper_template')]
final class PaperTemplateEntityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PaperTemplate::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('试卷模板')
            ->setEntityLabelInPlural('试卷模板管理')
            ->setPageTitle('index', '试卷模板管理')
            ->setPageTitle('new', '新建试卷模板')
            ->setPageTitle('edit', '编辑试卷模板')
            ->setPageTitle('detail', '试卷模板详情')
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(30)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('name')
            ->add('totalQuestions')
            ->add('totalScore')
            ->add('passScore')
            ->add('isActive')
            ->add('createTime')
            ->add('updateTime')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')
                ->hideOnForm(),

            TextField::new('name', '模板名称')
                ->setRequired(true)
                ->setHelp('试卷模板的名称，最多120个字符')
                ->setMaxLength(120),

            TextareaField::new('description', '模板描述')
                ->setRequired(false)
                ->setHelp('对该试卷模板的详细描述')
                ->setFormTypeOptions(['attr' => ['rows' => 3]]),

            IntegerField::new('totalQuestions', '总题数')
                ->setRequired(true)
                ->setHelp('模板生成试卷的总题目数量')
                ->setFormTypeOptions(['attr' => ['min' => 0]]),

            IntegerField::new('totalScore', '总分')
                ->setRequired(true)
                ->setHelp('模板生成试卷的总分数')
                ->setFormTypeOptions(['attr' => ['min' => 0]]),

            IntegerField::new('passScore', '及格分数')
                ->setRequired(true)
                ->setHelp('及格分数，范围0-100')
                ->setFormTypeOptions(['attr' => ['min' => 0, 'max' => 100]]),

            IntegerField::new('timeLimit', '考试时长（分钟）')
                ->setRequired(false)
                ->setHelp('考试时长限制，单位为分钟，留空表示无时长限制')
                ->setFormTypeOptions(['attr' => ['min' => 0]]),

            BooleanField::new('shuffleQuestions', '打乱题目顺序')
                ->setRequired(false)
                ->setHelp('是否随机排序试卷中的题目'),

            BooleanField::new('shuffleOptions', '打乱选项顺序')
                ->setRequired(false)
                ->setHelp('是否随机排序题目的选项'),

            BooleanField::new('isActive', '是否启用')
                ->setRequired(false)
                ->setHelp('是否启用该试卷模板'),

            CodeEditorField::new('difficultyDistribution', '难度分布配置')
                ->setLanguage('javascript')
                ->setRequired(false)
                ->setHelp('JSON格式的难度分布配置，例如：{"easy": 30, "medium": 50, "hard": 20}')
                ->formatValue(function ($value) {
                    return is_array($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ($value ?? '{}');
                })
                ->setFormTypeOption('data_class', null)
                ->setFormTypeOption('empty_data', null)
                // 添加数据转换器，将JSON字符串转换为数组
                ->setFormTypeOption('by_reference', false)
                ->setFormTypeOption('getter', function (PaperTemplate $entity) {
                    $value = $entity->getDifficultyDistribution();

                    return is_array($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '{}';
                })
                ->setFormTypeOption('setter', function (PaperTemplate $entity, ?string $value) {
                    if (null === $value || '' === $value || '{}' === $value) {
                        $entity->setDifficultyDistribution(null);

                        return;
                    }
                    $decoded = json_decode($value, true);
                    if (is_array($decoded)) {
                        /** @var array<string, mixed> $validDecoded */
                        $validDecoded = $decoded;
                        $entity->setDifficultyDistribution($validDecoded);
                    } else {
                        $entity->setDifficultyDistribution(null);
                    }
                })
                ->hideOnIndex(),

            CodeEditorField::new('questionTypeDistribution', '题型分布配置')
                ->setLanguage('javascript')
                ->setRequired(false)
                ->setHelp('JSON格式的题型分布配置，例如：{"single_choice": 40, "multiple_choice": 30, "essay": 30}')
                ->formatValue(function ($value) {
                    return is_array($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ($value ?? '{}');
                })
                ->setFormTypeOption('data_class', null)
                ->setFormTypeOption('empty_data', null)
                // 添加数据转换器，将JSON字符串转换为数组
                ->setFormTypeOption('by_reference', false)
                ->setFormTypeOption('getter', function (PaperTemplate $entity) {
                    $value = $entity->getQuestionTypeDistribution();

                    return is_array($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '{}';
                })
                ->setFormTypeOption('setter', function (PaperTemplate $entity, ?string $value) {
                    if (null === $value || '' === $value || '{}' === $value) {
                        $entity->setQuestionTypeDistribution(null);

                        return;
                    }
                    $decoded = json_decode($value, true);
                    if (is_array($decoded)) {
                        /** @var array<string, mixed> $validDecoded */
                        $validDecoded = $decoded;
                        $entity->setQuestionTypeDistribution($validDecoded);
                    } else {
                        $entity->setQuestionTypeDistribution(null);
                    }
                })
                ->hideOnIndex(),

            AssociationField::new('rules', '模板规则')
                ->setRequired(false)
                ->setHelp('该模板包含的规则列表')
                ->hideOnIndex(),

            AssociationField::new('paper', '关联试卷')
                ->setRequired(false)
                ->setHelp('该模板关联的试卷')
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
