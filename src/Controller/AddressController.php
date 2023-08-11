<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\PurchaseOrder;
use App\Form\AddressType;
use App\Form\CartType;
use App\Manager\CartManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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


class AddressController extends AbstractController
{
    private $entityManager;
    private $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    /**
     * @Route("/address/create", name="address_create")
     * @IsGranted("ROLE_USER")
     */
    public function create(Request $request, CartManager $cartManager, AddressRepository $addressRepository): Response
    {
        $user = $this->security->getUser();
        $address = $addressRepository->findOneBy(['user' => $user]);

        // Nếu không tìm thấy địa chỉ, tạo một đối tượng Address mới
        if (!$address) {
            $address = new Address();
            $address->setUser($user);
        }

        // Tiếp tục xử lý như trước đó
        $addresses = $addressRepository->findAll();
        $categories = $this->entityManager->getRepository(Category::class)->findAll();
        $cart = $cartManager->getCurrentCart();
        $cartForm = $this->createForm(CartType::class, $cart);
        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Lưu địa chỉ vào cơ sở dữ liệu
            $this->entityManager->persist($address);
            $this->entityManager->flush();

            // Chuyển hướng sau khi lưu thành công
            return $this->redirectToRoute('address_success');
        }


        return $this->render('address/create.html.twig', [
            'form' => $form->createView(),
            'cartForm' => $cartForm->createView(),
            'categories' => $categories,
            'cart' => $cart,
            'address' => $address,
        ]);
    }

    /**
     * @Route("/address/{id}/edit", name="address_edit", methods={"GET"})
     * @IsGranted("ROLE_USER")
     */
    public function edit(Request $request, Address $address): JsonResponse
    {
        // Kiểm tra quyền truy cập đến địa chỉ (nếu cần)
        // ...

        $form = $this->createForm(AddressType::class, $address);


        return $this->json([
            'html' => $this->renderView('address/edit.html.twig', [
                'form' => $form->createView(),
            ]),
        ]);
    }

    /**
     * @Route("/address/list", name="address_list")
     * @IsGranted("ROLE_SUPER_ADMIN", message="Chỉ có Admin mới có thể xem trang này")
     */
    public function list(ManagerRegistry $doctrine, CartManager $cartManager, SessionInterface $session): Response
    {
        // Lấy danh sách địa chỉ từ cơ sở dữ liệu
        $addresses = $this->entityManager->getRepository(Address::class)->findAll();
        $categories = $doctrine->getRepository(Category::class)->findAll();
        $cart = $cartManager->getCurrentCart();

        return $this->render('address/list.html.twig', [
            'addresses' => $addresses,
            'categories' => $categories,
            'cart' => $cart,
        ]);
    }

    /**
     * @Route("/address/success", name="address_success")
     * @IsGranted("ROLE_USER")
     */
    public function success(Request $request, CartManager $cartManager, ManagerRegistry $doctrine): Response
    {
        $addresses = $this->entityManager->getRepository(Address::class)->findAll();
        $categories = $doctrine->getRepository(Category::class)->findAll();
        $cart = $cartManager->getCurrentCart();


        return $this->render('address/success.html.twig', [
            'addresses' => $addresses,
            'categories' => $categories,
            'cart' => $cart,
        ]);
    }

    /**
     * @Route("/play-success/{total}", name="play_success", methods={"POST"})
     * @IsGranted("ROLE_USER")
     */
    public function playSuccess(Request $request, CartManager $cartManager, ManagerRegistry $doctrine, EntityManagerInterface $entityManager, string $total): Response
    {
        // Lưu tổng tiền vào session để truyền qua trang "order_success"
        $request->getSession()->set('total', $total);
        $cart = $cartManager->getCurrentCart();
        $purchaseOrderItems = $cart->getItems();

        /** @var PurchaseOrderRepository $repository */
        $repository = $entityManager->getRepository(PurchaseOrder::class);
        $maxIdOrder = $repository->findMaxIdOrder();

        // Gán giá trị lớn nhất vào biến $currentIdOrder
        $currentIdOrder = $maxIdOrder ?? 0;
        $nextIdOrder = ++$currentIdOrder;

        foreach ($purchaseOrderItems as $purchaseOrderItem) {
            $purchaseOrder = new PurchaseOrder();
            $purchaseOrder->setUser($this->security->getUser());
            $purchaseOrder->setPurchaseImage($purchaseOrderItem->getProduct()->getProductImage());
            $purchaseOrder->setPrice($purchaseOrderItem->getProduct()->getPrice());
            $purchaseOrder->setQuantity($purchaseOrderItem->getQuantity());
            $purchaseOrder->setName($purchaseOrderItem->getProduct()->getName());
            $purchaseOrder->setIdOrder($nextIdOrder);
            $purchaseOrder->setProductId($purchaseOrderItem->getProduct()->getId());
            $purchaseOrder->setStatus(0);

            $entityManager->persist($purchaseOrder);
            $entityManager->flush();
        }
        $cartManager->clearCartItems($cart);

        return $this->redirectToRoute('order_success', ['nextIdOrder' => $nextIdOrder]);
    }


    /**
     * @Route("/order-success", name="order_success")
     */
    public function orderSuccess(Request $request, CartManager $cartManager): Response
    {
        // Xử lý sau khi đặt hàng thành công
        $categories = $this->entityManager->getRepository(Category::class)->findAll();
        $addresses = $this->entityManager->getRepository(Address::class)->findAll();
        $cart = $cartManager->getCurrentCart();

        return $this->render('address/order_success.html.twig', [
            'categories' => $categories,
            'cart' => $cart,
            'addresses' => $addresses,
            'nextIdOrder' => $request->query->get('nextIdOrder'),
        ]);
    }

    /**
     * @Route("/purchase-order", name="purchase_order")
     */

}

