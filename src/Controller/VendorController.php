<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;

#[IsGranted('ROLE_USER')]
class VendorController extends AbstractController
{
    //Order list
    #[Route('/vendor/orders', name: 'app_vendor_orders')]
    public function orders(OrderRepository $orderRepository, SerializerInterface $serializer): Response
    {   
        /** @var User $user */
        $user = $this->getUser();

        $context = (new ObjectNormalizerContextBuilder())
            ->withGroups('read:order')
            ->toArray();

        $orders = $user->getOrders();

        $orders = $serializer->serialize($orders, 'json', $context);
        
        return $this->render('vendor/orders.html.twig', [
            'orders' => $orders,
        ]);
    }
}
