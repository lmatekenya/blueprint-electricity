<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            EmailField::new('email'),
            TextField::new('name'),
            ArrayField::new('roles'),
            DateTimeField::new('createdAt')->hideOnForm(),
            DateTimeField::new('updatedAt')->hideOnForm(),
        ];
    }
}

//namespace App\Controller\Admin;
//
//use App\Entity\User;
//use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
//use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
//use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
//use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
//use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
//use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
//use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
//use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
//use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
//use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
//use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
//use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
//use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
//
//class UserCrudController extends AbstractCrudController
//{
//    public static function getEntityFqcn(): string
//    {
//        return User::class;
//    }
//
//    public function configureCrud(Crud $crud): Crud
//    {
//        return $crud
//            ->setEntityLabelInSingular('User')
//            ->setEntityLabelInPlural('Users')
//            ->setSearchFields(['name', 'email'])
//            ->setDefaultSort(['createdAt' => 'DESC'])
//            ->setPaginatorPageSize(50);
//    }
//
//    public function configureActions(Actions $actions): Actions
//    {
//        return $actions
//            ->add(Crud::PAGE_INDEX, Action::DETAIL)
//            ->remove(Crud::PAGE_INDEX, Action::NEW)
//            ->remove(Crud::PAGE_INDEX, Action::EDIT)
//            ->remove(Crud::PAGE_DETAIL, Action::EDIT);
//    }
//
//    public function configureFilters(Filters $filters): Filters
//    {
//        return $filters
//            ->add(TextFilter::new('email'))
//            ->add(TextFilter::new('name'))
//            ->add(DateTimeFilter::new('createdAt'));
//    }
//
//    public function configureFields(string $pageName): iterable
//    {
//        yield IdField::new('id')->onlyOnDetail();
//        yield TextField::new('name');
//        yield EmailField::new('email');
//        yield ArrayField::new('roles');
//        yield AssociationField::new('transactions')
//            ->onlyOnDetail();
//        yield DateTimeField::new('createdAt')
//            ->setFormTypeOption('disabled', true);
//        yield DateTimeField::new('updatedAt')
//            ->onlyOnDetail()
//            ->setFormTypeOption('disabled', true);
//        yield DateTimeField::new('apiTokenExpiresAt')
//            ->onlyOnDetail();
//    }
//}
