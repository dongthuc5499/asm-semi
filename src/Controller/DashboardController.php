<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\PurchaseOrder;
use App\Entity\User;
use App\Form\AddressType;
use App\Form\CartType;
use App\Form\CategoryType;
use App\Form\ProductType;
use App\Manager\CartManager;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\PurchaseOrderRepository;
use App\Repository\AddressRepository;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;


class DashboardController extends AbstractController
{
    private $entityManager;
    private $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    #[Route('/dashboard ', name: 'dashboard_index')]
    #[IsGranted('ROLE_ADMIN')]
    public function indexDashboard(ManagerRegistry $doctrine, Request $request, CartManager $cartManager, PurchaseOrderRepository $orderRepository,
                                   CategoryRepository $categoryRepository, ProductRepository $productRepository, UserRepository $userRepository,
                                   EntityManagerInterface $entityManager): Response
    {
        $products = $doctrine->getRepository(Product::class)->findAll();
        $categories = $doctrine->getRepository(Category::class)->findAll();
        $cart = $cartManager->getCurrentCart();

        // Lấy tổng số đơn hàng từ database
        $totalOrders = $orderRepository->countAllOrders();
        // Lấy tổng số đơn hàng có status = 0 từ database
        $statusOrders = $orderRepository->countOrdersByStatus(0);
        $totalCategories = $categoryRepository->countAllCategories();
        $totalProducts = $productRepository->countAllProducts();
        $sumQuantityProducts = $productRepository->sumTotalQuantity();
        $totalUsers = $userRepository->countAllUsers();
        $totalSuperAdmins = $userRepository->countUsersByRole('ROLE_SUPER_ADMIN');

        $purchaseOrders = $entityManager->getRepository(PurchaseOrder::class)->findAll();
        $bestSellingProducts = [];

        // Calculate the total quantity for each product_id and unique users
        foreach ($purchaseOrders as $order) {
            $productId = $order->getProductId();
            $quantity = $order->getQuantity();
            $userId = $order->getUser();
            $image = $order->getPurchaseImage();
            $name = $order->getName();

            if (isset($bestSellingProducts[$productId])) {
                $bestSellingProducts[$productId]['quantity'] += $quantity;

                // Add the user only if it's not already in the users array for this product
                if (!in_array($userId, $bestSellingProducts[$productId]['users'], true)) {
                    $bestSellingProducts[$productId]['users'][] = $userId;
                }
            } else {
                $bestSellingProducts[$productId] = [
                    'quantity' => $quantity,
                    'users' => [$userId],
                    'image' => $image,
                    'name' => $name,
                ];
            }
        }

        return $this->render('dashboard/index.html.twig', [
            'products' => $products, 'categories' => $categories, 'cart' => $cart, 'totalOrders' => $totalOrders, 'statusOrders' => $statusOrders,
            'totalCategories' => $totalCategories, 'totalProducts' => $totalProducts, 'sumQuantityProducts' => $sumQuantityProducts,
            'totalUsers' => $totalUsers, 'totalSuperAdmins' => $totalSuperAdmins, 'bestSellingProducts' => $bestSellingProducts,
        ]);
    }



    #[Route('/dashboard/category/create', name: 'dashboard_category_create')]
    #[IsGranted('ROLE_ADMIN')]
    public function createCategoryDashboard(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger, CartManager $cartManager)
    {
        $categories = new Category();
        $form = $this->createForm(CategoryType::class, $categories);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // upload file
            $logoImage = $form->get('logoImage')->getData();
            if ($logoImage) {
                $originalFilename = pathinfo($logoImage->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $logoImage->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $logoImage->move(
                        $this->getParameter('logoImage_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash(
                        'error',
                        'Cannot upload'
                    );// ... handle exception if something happens during file upload
                }
                $categories->setLogo($newFilename);
            } else {
                $this->addFlash(
                    'error',
                    'Cannot upload'
                );// ... handle exception if something happens during file upload
            }

            $em = $doctrine->getManager();
            $em->persist($categories);
            $em->flush();

            return $this->redirectToRoute('dashboard_category_details');
        }
        return $this->renderForm('dashboard/dashboard_category_create.html.twig', ['form' => $form]);
    }
    #[Route('/dashboard/category/details', name: 'dashboard_category_details')]
    #[IsGranted('ROLE_ADMIN')]
    public function detailsCategory(ManagerRegistry $doctrine, CartManager $cartManager): Response
    {
        $categories = $doctrine->getRepository(Category::class)->findAll();

        return $this->render('dashboard/dashboard_category_details.html.twig', [
            'categories' => $categories
        ]);
    }

    #[Route('/dashboard/category/delete/{id}', name: 'dashboard_category_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteCategoryDashboard(ManagerRegistry $doctrine, $id)
    {
        $em = $doctrine->getManager();
        $categories = $em->getRepository(Category::class)->find($id);
        $em->remove($categories);
        $em->flush();

        return $this->redirectToRoute('dashboard_category_details');
    }

    #[Route('/dashboard/category/edit/{id}', name: 'dashboard_category_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function editActionDashboard(ManagerRegistry $doctrine, int $id, Request $request, SluggerInterface $slugger, CartManager $cartManager): Response
    {
        $entityManager = $doctrine->getManager();
        $categories = $entityManager->getRepository(Category::class)->find($id);
        $form = $this->createForm(CategoryType::class, $categories);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // upload file
            $logoImage = $form->get('logoImage')->getData();
            if ($logoImage) {
                $originalFilename = pathinfo($logoImage->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $logoImage->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $logoImage->move(
                        $this->getParameter('logoImage_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash(
                        'error',
                        'Cannot upload'
                    );// ... handle exception if something happens during file upload
                }
                $categories->setLogo($newFilename);
            } else {
                $this->addFlash(
                    'error',
                    'Cannot upload'
                );// ... handle exception if something happens during file upload
            }

            $em = $doctrine->getManager();
            $em->persist($categories);
            $em->flush();

            return $this->redirectToRoute('dashboard_category_details');
        }

        return $this->renderForm('dashboard/dashboard_category_edit.html.twig', ['form' => $form, 'categories' => $categories]);
    }

    #[Route('/dashboard/purchase-order', name: 'dashboard_purchase-order')]
    #[IsGranted('ROLE_ADMIN')]
    public function detailsPurchaseOrderDashboard(ManagerRegistry $doctrine, CartManager $cartManager): Response
    {
        $categories = $doctrine->getRepository(Category::class)->findAll();
        $purchaseOrders = $doctrine->getRepository(PurchaseOrder::class)->findAll();
        $addresses = $doctrine->getRepository(Address::class)->findAll();

        $groupedOrders = [];
        foreach ($purchaseOrders as $purchaseOrder) {
            $orderId = $purchaseOrder->getIdOrder();
            $groupedOrders[$orderId][] = $purchaseOrder;
        }

        return $this->render('dashboard/dashboard_purchase-order.html.twig', [
            'categories' => $categories,
            'groupedOrders' => $groupedOrders,
            'addresses' => $addresses,
        ]);
    }

    #[Route('/dashboard/purchase-order/approve', name: 'approve_orders', methods: ['PUT', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approveAllPurchaseOrders(Request $request): Response
    {
        $orderId = $request->request->get('orderId');
        $purchaseOrders = $this->entityManager->getRepository(PurchaseOrder::class)->findBy(['id_order' => $orderId, 'status' => 0]);

        foreach ($purchaseOrders as $purchaseOrder) {
            $purchaseOrder->setStatus(1);
        }

        $this->entityManager->flush();

        // Chuyển hướng người dùng về trang danh sách đơn hàng
        return $this->redirectToRoute('dashboard_purchase-order');
    }

    #[Route('/dashboard/purchase-order/all-orders', name: 'all-orders')]
    #[IsGranted('ROLE_ADMIN')]
    public function dashboardAllPurchaseOrders(ManagerRegistry $doctrine, CartManager $cartManager): Response
    {
        $purchaseOrders = $doctrine->getRepository(PurchaseOrder::class)->findAll();
        $addresses = $doctrine->getRepository(Address::class)->findAll();


        return $this->render('dashboard/dashboard_all-orders.html.twig', [
            'purchaseOrders' => $purchaseOrders,
            'addresses' => $addresses,
        ]);
    }

    #[Route('/dashboard/product/create', name: 'dashboard_product_create')]
    #[IsGranted('ROLE_ADMIN')]
    public function createProductDashboard(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger, CartManager $cartManager)
    {
        $categories = $doctrine->getRepository(Category::class)->findAll();
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $cart = $cartManager->getCurrentCart();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // upload file
            $productImage = $form->get('productImage')->getData();
            if ($productImage) {
                $originalFilename = pathinfo($productImage->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $productImage->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $productImage->move(
                        $this->getParameter('productImages_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash(
                        'error',
                        'Cannot upload'
                    );// ... handle exception if something happens during file upload
                }
                $product->setProductImage($newFilename);
            } else {
                $this->addFlash(
                    'error',
                    'Cannot upload'
                );// ... handle exception if something happens during file upload
            }
            $em = $doctrine->getManager();
            $em->persist($product);
            $em->flush();

            $this->addFlash(
                'notice',
                'Product Added'
            );
            return $this->redirectToRoute('dashboard_product_list');
        }
        return $this->renderForm('dashboard/dashboard_product_create.html.twig', ['form' => $form, 'categories' => $categories, 'cart' => $cart]);
    }

    #[Route('/dashboard/product', name: 'dashboard_product_list')]
    #[IsGranted('ROLE_ADMIN')]
    public function listAction(ManagerRegistry $doctrine, Request $request, CartManager $cartManager): Response
    {
        $products = $doctrine->getRepository(Product::class)->findAll();
        $categories = $doctrine->getRepository(Category::class)->findAll();
        $cart = $cartManager->getCurrentCart();

        return $this->render('dashboard/dashboard_product_list.html.twig', [
            'products' => $products, 'categories' => $categories, 'cart' => $cart,
        ]);
    }

    #[Route('/dashboard/product/edit/{id}', name: 'dashboard_product_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function editProductDashboard(ManagerRegistry $doctrine, int $id, Request $request, SluggerInterface $slugger, CartManager $cartManager): Response
    {
        $entityManager = $doctrine->getManager();
        $product = $entityManager->getRepository(Product::class)->find($id);
        $form = $this->createForm(ProductType::class, @$product);
        $form->handleRequest($request);
        $categories = $doctrine->getRepository(Category::class)->findAll();
        $cart = $cartManager->getCurrentCart();

        if ($form->isSubmitted() && $form->isValid()) {
            //upload file
            $productImage = $form->get('productImage')->getData();
            if ($productImage) {
                $originalFilename = pathinfo($productImage->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $productImage->guessExtension();
                // Move the file to the directory where brochures are stored
                try {
                    $productImage->move(
                        $this->getParameter('productImages_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash(
                        'error',
                        'Cannot upload'
                    );// ... handle exception if something happens during file upload
                }
                $product->setProductImage($newFilename);
            } else {
                $this->addFlash(
                    'error',
                    'Cannot upload'
                );// ... handle exception if something happens during file upload
            }

            $em = $doctrine->getManager();
            $em->persist($product);
            $em->flush();
            return $this->redirectToRoute('dashboard_product_list');

        }
        return $this->renderForm('dashboard/dashboard_product_edit.html.twig', ['form' => $form, 'categories' => $categories, 'cart' => $cart]);
    }

    #[Route('/dashboard/product/delete/{id}', name: 'dashboard_product_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteProductDashboard(ManagerRegistry $doctrine, $id)
    {
        $em = $doctrine->getManager();
        $product = $em->getRepository(Product::class)->find($id);
        $em->remove($product);
        $em->flush();

        $this->addFlash(
            'error',
            'Product deleted'
        );
        return $this->redirectToRoute('dashboard_product_list');
    }

    #[Route('/dashboard/user', name: 'dashboard_user')]
    #[IsGranted('ROLE_ADMIN')]
    public function listUserDashboard(ManagerRegistry $doctrine, Request $request, CartManager $cartManager): Response
    {
        $products = $doctrine->getRepository(Product::class)->findAll();
        $categories = $doctrine->getRepository(Category::class)->findAll();
        $users = $doctrine->getRepository(User::class)->findAll();
        $cart = $cartManager->getCurrentCart();
        $addresses = $doctrine->getRepository(Address::class)->findAll();

        return $this->render('dashboard/dashboard_user.html.twig', [
            'products' => $products, 'categories' => $categories, 'cart' => $cart, 'users' => $users, 'addresses' => $addresses,
        ]);
    }

    #[Route('/dashboard/user/make-admin/{id}', name: 'dashboard_make_admin')]
    #[IsGranted('ROLE_ADMIN')]
    public function makeUserAdmin(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository, $id): Response
    {
        // Tìm người dùng dựa vào ID
        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        // Ghi đè danh sách roles của người dùng và đặt chỉ một quyền ROLE_SUPER_ADMIN
        $user->setRoles(['ROLE_SUPER_ADMIN']);

        // Lưu thay đổi vào database
        $entityManager->flush();

        // Chuyển hướng người dùng về trang danh sách người dùng
        return $this->redirectToRoute('dashboard_user');
    }

}

