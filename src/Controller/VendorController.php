<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Order;
use App\Entity\Adress;
use App\Entity\Contact;
use App\Entity\Shipment;
use App\Entity\OrderStatut;
use App\Entity\ShipmentStatut;
use App\Repository\OrderRepository;
use App\Repository\AdressRepository;
use App\Service\BoxtalShippingService;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\BoxtalShippingServiceFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\Exception\ClientException;
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

        //On ordonne les commandes par date de création
        $orders = $orders->toArray();
        usort($orders, function($a, $b) {
            return $b->getCreatedAt()->getTimestamp() - $a->getCreatedAt()->getTimestamp();
        });

        $orders = $serializer->serialize($orders, 'json', $context);

        $context = (new ObjectNormalizerContextBuilder())
            ->withGroups('read:user')
            ->toArray();

        $user = $serializer->serialize($user, 'json', $context);
        
        return $this->render('vendor/orders.html.twig', [
            'orders' => $orders,
            'user' => $user,
        ]);
    }

    #[Route('/vendor/createShipment', name: 'app_vendor_create_shipment', methods: ['POST'])]
    public function createShipment(
        Request $request, 
        AdressRepository $adressRepository, 
        EntityManagerInterface $entityManager,
        BoxtalShippingServiceFactory $boxtalFactory
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $shipment = json_decode($request->getContent(), true);

        $adress = $adressRepository->find($shipment['adress']['id']);

        $totalValue = 0;
        foreach ($shipment['orders'] as $order) {
            $totalValue += $order['total'];
        }
        

        $shipmentObject = [
            "insured" => false,
            "shipment" => [
                "packages" => [[
                    "type" => $shipment['packages'][0]['type'],
                    "value" => [
                        "value" => $totalValue,
                        "currency" => "EUR"
                    ],
                    "width" => $shipment['packages'][0]['dimension']['width'],
                    "height" => $shipment['packages'][0]['dimension']['height'] ? $shipment['packages'][0]['dimension']['height'] : 0,
                    "length" => $shipment['packages'][0]['dimension']['length'],
                    "weight" => $shipment['packages'][0]['dimension']['weight'],
                    "content" => [
                        "id" => $shipment['packages'][0]['content']['id'],
                        "description" => $shipment['packages'][0]['content']['description']
                    ],
                    // "stackable" => true,
                    // "externalId" => "XYZ12345"
                ]],
                
                "toAddress" => [
                    "type" => "RESIDENTIAL",
                    "contact" => [
                        "email" => $adress->getContact()->getEmail(),
                        "phone" => $adress->getContact()->getPhone(),
                        "lastName" => $adress->getContact()->getLastName(),
                        "firstName" => $adress->getContact()->getFirstName()
                    ],
                    "location" => [
                        "city" => $adress->getCity(),
                        //on récupère le numéro de la rue
                        "number" => substr($adress->getAdressLine(), 0, strpos($adress->getAdressLine(), ' ')),
                        "street" => substr($adress->getAdressLine(), strpos($adress->getAdressLine(), ' ') + 1),
                        "postalCode" => $adress->getPostalCode(),
                        "countryIsoCode" => $adress->getCountryCode()
                    ]
                ],
                // "externalId" => "ABC1234",
                "fromAddress" => [
                    "type" => "BUSINESS",
                    "contact" => [
                        "email" => $shipment['vendorAdress']['email'],
                        "phone" => $shipment['vendorAdress']['phone'],
                        "lastName" => $shipment['vendorAdress']['lastName'],
                        "firstName" => $shipment['vendorAdress']['firstName']
                    ],
                    "location" => [
                        "city" => $shipment['vendorAdress']['city'],
                        "number" => substr($shipment['vendorAdress']['street'], 0, strpos($shipment['vendorAdress']['street'], ' ')),
                        "street" => substr($shipment['vendorAdress']['street'], strpos($shipment['vendorAdress']['street'], ' ') + 1),
                        "postalCode" => $shipment['vendorAdress']['zipCode'],
                        "countryIsoCode" => $shipment['vendorAdress']['countryCode']
                    ],
                ],
                "returnAddress" => [
                    "type" => "BUSINESS",
                    "contact" => [
                        "email" => $shipment['vendorAdress']['email'],
                        "phone" => $shipment['vendorAdress']['phone'],
                        "lastName" => $shipment['vendorAdress']['lastName'],
                        "firstName" => $shipment['vendorAdress']['firstName']
                    ],
                    "location" => [
                        "city" => $shipment['vendorAdress']['city'],
                        "number" => substr($shipment['vendorAdress']['street'], 0, strpos($shipment['vendorAdress']['street'], ' ')),
                        "street" => substr($shipment['vendorAdress']['street'], strpos($shipment['vendorAdress']['street'], ' ') + 1),
                        "postalCode" => $shipment['vendorAdress']['zipCode'],
                        "countryIsoCode" => $shipment['vendorAdress']['countryCode']
                    ]
                ],
                "pickupPointCode" => $adress->getParcelPointCode(),
                "dropOffPointCode" => $shipment['vendorAdress']['parcelPoint']['code'],
            ],
            "shippingOfferCode" => "CHRP-ChronoRelais",
            "labelType" => "PDF_A4",
            //Aujourdhui + 4jours
            "expectedTakingOverDate" => date('Y-m-d', strtotime('+4 days')),
            
        ];

        try {
            // Création du service avec les credentials utilisateur
            $boxtalShippingService = $boxtalFactory->createForUser(
                $user->getBoxtalApiKey(),
                $user->getBoxtalApiSecret()
            );

            $response = $boxtalShippingService->createShipment($shipmentObject);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray();
            
            if ($statusCode === 200) {

                $this->updateOrdersStatus($shipment['orders'], $entityManager,'batched');
                $this->createShipmentObject($data, $entityManager);

                $boxtalShippingService->createSubscription('TRACKING_CHANGED');
                return $this->json(['message' => 'Shipment created', 'data' => $data]);

            }
            
            return $this->json(['error' => 'Une erreur est survenue'], $statusCode);
        } catch (\Exception $e) {
            
                $content = $response->getContent(false);
                return $this->json([
                    'error' => $e->getMessage(),
                    'details' => json_decode($content, true)
                ], 500);
        }
    }

    public function createShipmentObject(array $data, EntityManagerInterface $entityManager)
    {
        $shipment = new Shipment();
        $shipment->setShipmentId($data['content']['id']);
        $shipment->setDeliveryPrice($data['content']['deliveryPriceExclTax']['value']);
        $shipment->setCreatedAt(new \DateTimeImmutable());
        $shipmentStatut = $entityManager->getRepository(ShipmentStatut::class)->findOneBy(['statut' => 'pending']);
        $shipment->setStatut($shipmentStatut);
        $entityManager->persist($shipment);
        $entityManager->flush();
    }

    #[Route('/vendor/getProductCategories', name: 'app_vendor_get_product_categories', methods: ['GET'])]
    public function getProductCategories(HttpClientInterface $httpClient, BoxtalShippingServiceFactory $boxtalFactory): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $boxtalShippingService = $boxtalFactory->createForUser($user->getBoxtalApiKey(), $user->getBoxtalApiSecret());
        $productCategories = $boxtalShippingService->getProductCategories();
        return $this->json($productCategories);
    }

    #[Route('/vendor/registerAdress', name: 'app_vendor_register_adress', methods: ['POST'])]
    public function registerAdress(Request $request, EntityManagerInterface $entityManager): Response
    {   
        /** @var User $user */
        $user = $this->getUser();
        
        $adressInfo = json_decode($request->getContent(), true);
        $adress = new Adress();
        $contact = new Contact();
        $contact->setFirstName($adressInfo['firstName']);
        $contact->setLastName($adressInfo['lastName']);
        $contact->setEmail($adressInfo['email']);
        $contact->setPhone($adressInfo['phone']);
        $entityManager->persist($contact);
        //On récupère les deux premiers caractères du pays et on les met en majuscule
        $countryCode = strtoupper(substr($adressInfo['country'], 0, 2));
        $adress->setCountryCode($countryCode);
        $adress->setParcelPointCode($adressInfo['parcelPoint']['code']);
        $adress->setType("RESIDENTIAL");
        $adress->setUser($user);
        $adress->setContact($contact);
        $adress->setAdressLine($adressInfo['street']);
        $adress->setCity($adressInfo['city']);
        $adress->setPostalCode($adressInfo['zipCode']);
        $adress->setParcelPointName($adressInfo['parcelPoint']['name']);
        $entityManager->persist($adress);
        $entityManager->flush();
        
        return $this->json(['message' => 'Adresse enregistrée']);
    }

    public function updateOrdersStatus(array $orders, EntityManagerInterface $entityManager, string $status): void
    {   
        $status = $entityManager->getRepository(OrderStatut::class)->findOneBy(['statut' => $status]);
        foreach ($orders as $order) {
            $registeredOrder = $entityManager->getRepository(Order::class)->find($order['id']);
            $registeredOrder->setStatut($status);
            $entityManager->persist($registeredOrder);
        }
        $entityManager->flush();
    }

    #[Route('/vendor/shipment', name: 'app_vendor_shipment', methods: ['GET'])]
    public function shipment(EntityManagerInterface $entityManager, SerializerInterface $serializer): Response
    {
        $context = (new ObjectNormalizerContextBuilder())
            ->withGroups('read:shipment')
            ->toArray();

        $shipments = $entityManager->getRepository(Shipment::class)->findAll();

        //On ordonne les expéditions par date de création
        usort($shipments, function($a, $b) {
            return $b->getCreatedAt()->getTimestamp() - $a->getCreatedAt()->getTimestamp();
        });

        $shipments = $serializer->serialize($shipments, 'json', $context);

        $context = (new ObjectNormalizerContextBuilder())
            ->withGroups('read:user')
            ->toArray();

        $user = $serializer->serialize($this->getUser(), 'json', $context);
        
        return $this->render('vendor/shipment.html.twig', [
            'shipments' => $shipments,
            'user' => $user,
        ]);
    }

    #[Route('/vendor/shipment/{id}/document', name: 'app_vendor_shipment_document', methods: ['GET'])]
    public function shipmentDocument(string $id, HttpClientInterface $httpClient, BoxtalShippingServiceFactory $boxtalFactory): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $boxtalShippingService = $boxtalFactory->createForUser($user->getBoxtalApiKey(), $user->getBoxtalApiSecret());
        $document = $boxtalShippingService->getShipmentDocument($id);
        return $this->json($document);
    }

    #[Route('/vendor/getSubscription', name: 'app_vendor_get_subscription')]
    public function getSubscription(BoxtalShippingServiceFactory $boxtalFactory): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $boxtalShippingService = $boxtalFactory->createForUser($user->getBoxtalApiKey(), $user->getBoxtalApiSecret());
        return new JsonResponse(['subscription' => $boxtalShippingService->getSubscription()]);
    }

    #[Route('/vendor/deleteSubscription/{id}', name: 'app_vendor_delete_subscription')]
    public function deleteSubscription(string $id, BoxtalShippingServiceFactory $boxtalFactory): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $boxtalShippingService = $boxtalFactory->createForUser($user->getBoxtalApiKey(), $user->getBoxtalApiSecret());
        $response = $boxtalShippingService->deleteSubscription($id);
        return new JsonResponse(['subscription' => $response]);
    }
}