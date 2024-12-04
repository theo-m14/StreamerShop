<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\OrderItem;
use App\Entity\OrderStatut;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;

class ShopController extends AbstractController
{
    #[Route('/boutique/{username}', name: 'app_shop')]
    public function index(string $username, EntityManagerInterface $entityManager, SerializerInterface $serializer): Response
    {
        $context = (new ObjectNormalizerContextBuilder())
            ->withGroups('read:product')
            ->toArray();

        $user = $entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        $products = $entityManager->getRepository(Product::class)->findBy(['user' => $user, 'isVisible' => true]);

        foreach ($products as $product) {
            $product->setStock($this->calculateAvailableStock($product, 0, $entityManager));
        }

        $products = $serializer->serialize($products, 'json', $context);

        return $this->render('shop/index.html.twig', [
            'products' => $products,
            'username' => $username,
        ]);
    }

    #[Route('/reserve/{id}', name: 'app_reserve')]
    public function reserve(Product $product, EntityManagerInterface $entityManager, Request $request): Response
    {
        $request = json_decode($request->getContent(), true);
        //On cherche d'abord si une réservation existe déjà pour ce produit et cette session
        $reservation = $entityManager->getRepository(Reservation::class)->findOneBy(['product' => $product, 'sessionId' => $request['cartId']]);
        if ($reservation) {
            $reservation->setQuantity($reservation->getQuantity() + $request['quantity']);
        } else {
            $reservation = new Reservation();
            $reservation->setProduct($product);
            $reservation->setSessionId($request['cartId']);
            $reservation->setQuantity($request['quantity']);
            $reservation->setExpirationTime(new \DateTime('+5 minutes'));
        }
        $entityManager->persist($reservation);
        $entityManager->flush();
        return new JsonResponse(['success' => true]);
    }

    #[Route('/unreserve/{id}', name: 'app_unreserve')]
    public function unreserve(int $id, EntityManagerInterface $entityManager, Request $request): Response
    {
        $request = json_decode($request->getContent(), true);
        $reservation = $entityManager->getRepository(Reservation::class)->findOneBy(['product' => $id, 'sessionId' => $request['cartId']]);
        if ($reservation && $reservation->getQuantity() > 1) {
            $reservation->setQuantity($reservation->getQuantity() - 1);
        } else {
            $entityManager->remove($reservation);
        }
        $entityManager->flush();
        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/availableStock', name: 'app_product_availableStock', methods: ['POST'])]
    public function availableStock(Product $product, Request $request, EntityManagerInterface $entityManager): Response
    {
        $quantity = $request->getPayload()->getInt('quantity');
        return new JsonResponse(['availableStock' => $this->calculateAvailableStock($product, $quantity, $entityManager)]);
    }

    private function calculateAvailableStock(Product $product, int $quantity, EntityManagerInterface $entityManager): int
    {
        $reservationRepository = $entityManager->getRepository(Reservation::class);
        $reservations = $reservationRepository->findBy(['product' => $product]);
        //filtre les reservations qui sont pas encore passées
        $reservations = array_filter($reservations, function ($reservation) {
            return $reservation->getExpirationTime() > new \DateTime();
        });

        $totalReserved = array_reduce($reservations, function ($carry, $reservation) {
            return $carry + $reservation->getQuantity();
        }, 0);
        return $product->getStock() - $totalReserved - $quantity;
    }

    #[Route('/checkout', name: 'app_checkout', methods: ['POST'])]
    public function createCheckoutSession(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $cart = $data['cart'];

        $stripe = new \Stripe\StripeClient($this->getParameter('stripe_api_key'));

        $line_items = [];

        $total = 0;

        $accountId = $this->getAccountIdByProductId($cart[0]['id'], $entityManager);

        foreach ($cart as $item) {
            $product = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => ['name' => $item['name']],
                    'unit_amount' => $item['price'] * 100,
                ],
                'quantity' => $item['quantity'],
            ];
            $line_items[] = $product;
            $total += $item['price'] * $item['quantity'];
        }

        $checkoutSession = $stripe->checkout->sessions->create([
            'line_items' => $line_items,
            'payment_intent_data' => [
                //On prends 0.2% de la transaction
                'application_fee_amount' => 0.2 * $total * 100,
                'transfer_data' => ['destination' => $accountId],
            ],
            'mode' => 'payment',
            'ui_mode' => 'embedded',
            'return_url' => $this->generateUrl('app_checkout_return', ["session_id" => "{CHECKOUT_SESSION_ID}"], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        $this->createOrder($checkoutSession->id, $data['cart'], $total, $entityManager);

        return new JsonResponse(['client_secret' => $checkoutSession->client_secret]);
    }

    #[Route('/checkout/return', name: 'app_checkout_return')]
    public function checkoutReturn(Request $request): Response
    {
        $sessionId = $request->query->get('session_id');
        $stripe = new \Stripe\StripeClient($this->getParameter('stripe_api_key'));
        $checkoutSession = $stripe->checkout->sessions->retrieve($sessionId);
        dd($checkoutSession);
        return $this->render('shop/checkout_success.html.twig', ['checkoutSession' => $checkoutSession]);
    }

    public function getAccountIdByProductId(int $productId, EntityManagerInterface $entityManager): string
    {
        $product = $entityManager->getRepository(Product::class)->find($productId);
        return $product->getUser()->getStripeConnectId();
    }

    public function createOrder(string $checkoutSessionId, array $cart, float $total, EntityManagerInterface $entityManager): void
    {
        $order = new Order();
        $order->setCheckoutSessionId($checkoutSessionId);
        foreach ($cart as $item) {
            $product = $entityManager->getRepository(Product::class)->find($item['id']);
            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity($item['quantity']);
            $entityManager->persist($orderItem);
            $order->addOrderItem($orderItem);
        }
        $order->setTotal($total);
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setStatut($entityManager->getRepository(OrderStatut::class)->findOneBy(['statut' => 'Pending']));
        //TODO:Set user by account Id
        $product = $entityManager->getRepository(Product::class)->find($cart[0]['id']);
        $user = $product->getUser();
        $order->setUser($user);
        $entityManager->persist($order);
        $entityManager->flush();
    }
}
