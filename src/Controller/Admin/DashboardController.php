<?php
//// src/Controller/Admin/DashboardController.php
//namespace App\Controller\Admin;
//
//use App\Entity\ElectricityTransaction;
//use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
//use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
//use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
//use Symfony\Component\HttpFoundation\Response;
//use Doctrine\ORM\EntityManagerInterface;
//
//#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
//class DashboardController extends AbstractDashboardController
//{
//    private EntityManagerInterface $em;
//
//    public function __construct(EntityManagerInterface $em)
//    {
//        $this->em = $em;
//    }
//
////    public function index(): Response
////    {
////        // Fetch some stats dynamically
////        $totalTransactions = $this->em->getRepository(ElectricityTransaction::class)->count([]);
////        $pending = $this->em->getRepository(ElectricityTransaction::class)->count(['status' => 'pending']);
////        $success = $this->em->getRepository(ElectricityTransaction::class)->count(['status' => 'success']);
////        $failed = $this->em->getRepository(ElectricityTransaction::class)->count(['status' => 'failed']);
////        // Fetch last 10 transactions
////        $recentTransactions = $this->findBy([], ['createdAt' => 'DESC'], 10);
////
////        return $this->render('admin/dashboard.html.twig', [
////            'totalTransactions' => $totalTransactions,
////            'pending' => $pending,
////            'success' => $success,
////            'failed' => $failed,
////            'recentTransactions' => $recentTransactions,
////        ]);
////    }
//
//    public function index(): Response
//    {
//        $repo = $this->em->getRepository(ElectricityTransaction::class);
//
//        // Fetch stats
//        $totalTransactions = $repo->count([]);
//        $pending = $repo->count(['status' => 'pending']);
//        $success = $repo->count(['status' => 'success']);
//        $failed = $repo->count(['status' => 'failed']);
//
//
//        // Fetch last 10 transactions
//        $recentTransactions = $repo->findBy([], ['createdAt' => 'DESC']);
//
//        return $this->render('admin/dashboard.html.twig', [
//            'totalTransactions' => $totalTransactions,
//            'pending' => $pending,
//            'success' => $success,
//            'failed' => $failed,
//            'recentTransactions' => $recentTransactions, // pass to Twig
//        ]);
//    }
//
//    public function configureDashboard(): Dashboard
//    {
//        return Dashboard::new()->setTitle('Blueprint Electricity Admin');
//    }
//}


namespace App\Controller\Admin;

use App\Entity\ElectricityTransaction;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Admin Panel');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Users', 'fa fa-user', User::class);
        yield MenuItem::linkToCrud('Electricity Transactions', 'fa fa-bolt', ElectricityTransaction::class);
    }
}
