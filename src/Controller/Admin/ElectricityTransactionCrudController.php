<?php
//<?php
//
//namespace App\Controller\Admin;
//
//use App\Entity\ElectricityTransaction;
//use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
//use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
//use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
//use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
//use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
//use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
//
//class ElectricityTransactionCrudController extends AbstractCrudController
//{
//    public static function getEntityFqcn(): string
//    {
//        return ElectricityTransaction::class;
//    }
//
//    public function configureFields(string $pageName): iterable
//    {
//        return [
//            TextField::new('transID'),
//            TextField::new('meterNumber'),
//            MoneyField::new('amount')->setCurrency('USD'),
//            TextField::new('status'),
//            TextField::new('token')->hideOnForm(),
//            TextField::new('receiptNo')->hideOnForm(),
//            IntegerField::new('units')->hideOnForm(),
//            TextField::new('provider')->hideOnForm(),
//            AssociationField::new('user'),
//            DateTimeField::new('createdAt')->hideOnForm(),
//            DateTimeField::new('updatedAt')->hideOnForm(),
//        ];
//    }
//}
namespace App\Controller\Admin;

use App\Entity\ElectricityTransaction;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;

class ElectricityTransactionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ElectricityTransaction::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Transaction')
            ->setEntityLabelInPlural('Transactions')
            ->setSearchFields(['transID', 'meterNumber', 'receiptNo'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(50);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('transID'))
            ->add(TextFilter::new('meterNumber'))
            ->add(ChoiceFilter::new('status')
                ->setChoices([
                    'Pending' => 'pending',
                    'Processing' => 'processing',
                    'Success' => 'success',
                    'Failed' => 'failed'
                ]))
            ->add(ChoiceFilter::new('provider')
                ->setChoices([
                    'PrepaidPlus' => 'prepaidplus',
                    'CraftAPI' => 'craftapi'
                ]))
            ->add(NumericFilter::new('amount'))
            ->add(DateTimeFilter::new('createdAt'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnDetail();
        yield TextField::new('transID');
        yield TextField::new('meterNumber');
        yield NumberField::new('amount');
        yield ChoiceField::new('status')
            ->setChoices([
                'Pending' => 'pending',
                'Processing' => 'processing',
                'Success' => 'success',
                'Failed' => 'failed'
            ])
            ->renderAsBadges([
                'pending' => 'warning',
                'processing' => 'info',
                'success' => 'success',
                'failed' => 'danger'
            ]);
        yield ChoiceField::new('provider')
            ->setChoices([
                'PrepaidPlus' => 'prepaidplus',
                'CraftAPI' => 'craftapi'
            ])
            ->renderAsBadges();
        yield AssociationField::new('user');
        yield TextField::new('receiptNo')->onlyOnDetail();
        yield NumberField::new('units')->onlyOnDetail();
        yield TextField::new('token')->onlyOnDetail();
        yield ArrayField::new('details')->onlyOnDetail();
        yield DateTimeField::new('createdAt')
            ->setFormTypeOption('disabled', true);
        yield DateTimeField::new('updatedAt')
            ->onlyOnDetail()
            ->setFormTypeOption('disabled', true);
    }
}
