<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Repository\AdressRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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

        $orders = $serializer->serialize($orders, 'json', $context);
        
        return $this->render('vendor/orders.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/vendor/createShipment', name: 'app_vendor_create_shipment', methods: ['POST'])]
    public function createShipment(
        Request $request, 
        AdressRepository $adressRepository, 
        HttpClientInterface $httpClient
    ): Response
    {
        $shipment = json_decode($request->getContent(), true);

        $adress = $adressRepository->find($shipment['adress']['id']);

        //TODO: Créer l'expédition
        $totalValue = 0;
        foreach ($shipment['orders'] as $order) {
            $totalValue += $order['total'];
        }

        $description = 'test';

        $response = $httpClient->request('GET', 'https://api.boxtal.build/shipping/v3.1/content-category?language=fr',
        [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode('50YRR373ER9SCVCVI25SZTNAV6AO0VHQ83CN45VD:5b13b96d-5a35-472b-bee8-17501033efa9'),
                'Content-Type' => 'application/json',
            ],
        ]);	
        $contentCategory = $response->toArray();
        // dd($contentCategory);

        $shipmentObject = [
            "insured" => false,
            "shipment" => [
                "packages" => [[
                    "type" => "PARCEL",
                    "value" => [
                        "value" => 512.91,
                        "currency" => "EUR"
                    ],
                    "width" => 15,
                    "height" => 11,
                    "length" => 16,
                    "weight" => 1.5,
                    "content" => [
                        "id" => "content:v1:40100",
                        "description" => "test"
                    ],
                    "stackable" => true,
                    "externalId" => "XYZ12345"
                ]],
                
                "toAddress" => [
                    "type" => "RESIDENTIAL",
                    "contact" => [
                        "email" => "theo.michel2@outlook.fr",
                        "phone" => "33761776988",
                        "lastName" => "Michel",
                        "firstName" => "Theo"
                    ],
                    "location" => [
                        "city" => "Perigueux",
                        "number" => "10",
                        "street" => "Rue Eguillerie",
                        "postalCode" => "24000",
                        "countryIsoCode" => "FR"
                    ]
                ],
                "externalId" => "ABC1234",
                "fromAddress" => [
                    "type" => "BUSINESS",
                    "contact" => [
                        "email" => "js@acme.com",
                        "phone" => "33612341234",
                        "company" => "Acme",
                        "lastName" => "Snow",
                        "firstName" => "Jon"
                    ],
                    "location" => [
                        "city" => "Paris",
                        "number" => "4",
                        "street" => "boulevard des Capucines",
                        "postalCode" => "75009",
                        "countryIsoCode" => "FR"
                    ],
                    "additionalInformation" => "Sonner au 2ème étage."
                ],
                "returnAddress" => [
                    "type" => "BUSINESS",
                    "contact" => [
                        "email" => "js@acme.com",
                        "phone" => "33612341234",
                        "company" => "Acme",
                        "lastName" => "Snow",
                        "firstName" => "Jon"
                    ],
                    "location" => [
                        "city" => "Paris",
                        "number" => "4",
                        "street" => "boulevard des Capucines",
                        "postalCode" => "75009",
                        "countryIsoCode" => "FR"
                    ]
                ],
                "pickupPointCode" => $adress->getParcelPointCode(),
                "dropOffPointCode" => "763DA"
            ],
            "shippingOfferCode" => "CHRP-ChronoRelais",
            "labelType" => "PDF_A4",
            "expectedTakingOverDate" => "2025-01-26",
            
        ];
        

        $headers = [
            'Authorization' => 'Basic ' . base64_encode('50YRR373ER9SCVCVI25SZTNAV6AO0VHQ83CN45VD:5b13b96d-5a35-472b-bee8-17501033efa9'),
            'Content-Type' => 'application/json',
        ];

        try {
            // Debug du payload
            // dd([
            //     'Payload complet' => $shipmentObject,
            //     'JSON généré' => json_encode($shipmentObject, JSON_PRETTY_PRINT),
            //     'Erreurs JSON' => json_last_error_msg()
            // ]);
            // return $this->json(['message' => 'Shipment created', 'data' => $shipmentObject]);
            
            $response = $httpClient->request('POST', 'https://api.boxtal.build/shipping/v3.1/shipping-order', [
                'headers' => $headers,
                'json' => $shipmentObject,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->toArray();
            
            if ($statusCode === 200) {
                return $this->json(['message' => 'Shipment created', 'data' => $content]);
            }
            
            return $this->json(['error' => 'Une erreur est survenue'], $statusCode);
        } catch (\Exception $e) {
            
                $response = $e->getResponse();
                $content = $response->getContent(false);
                return $this->json([
                    'error' => $e->getMessage(),
                    'details' => json_decode($content, true)
                ], 500);
            
           
        }

        //Exemple de payload à envoyer
        // {
        //     "insured": false,
        //     "shipment": {
        //       "packages": [
        //         {
        //           "type": "PARCEL",
        //           "value": {
        //             "value": 30,
        //             "currency": "EUR"
        //           },
        //           "width": 15,
        //           "height": 11,
        //           "length": 16,
        //           "weight": 1.5,
        //           "content": {
        //             "id": "content:v1:10150",
        //             "description": "Livre illustré pour enfants"
        //           },
        //           "stackable": true,
        //           "externalId": "XYZ12345"
        //         }
        //       ],
        //       "toAddress": {
        //         "type": "RESIDENTIAL",
        //         "contact": {
        //           "email": "dt@acme.com",
        //           "phone": 33612341234,
        //           "lastName": "Targaryen",
        //           "firstName": "Daenerys"
        //         },
        //         "location": {
        //           "city": "Paris",
        //           "number": 15,
        //           "street": "rue Marsollier",
        //           "postalCode": 75002,
        //           "countryIsoCode": "FR"
        //         }
        //       },
        //       "externalId": "ABC1234",
        //       "fromAddress": {
        //         "type": "BUSINESS",
        //         "contact": {
        //           "email": "js@acme.com",
        //           "phone": 33612341234,
        //           "company": "Acme",
        //           "lastName": "Snow",
        //           "firstName": "Jon"
        //         },
        //         "location": {
        //           "city": "Paris",
        //           "number": 4,
        //           "street": "boulevard des Capucines",
        //           "postalCode": 75009,
        //           "countryIsoCode": "FR"
        //         },
        //         "additionalInformation": "Sonner au 2ème étage."
        //       },
        //       "returnAddress": {
        //         "type": "BUSINESS",
        //         "contact": {
        //           "email": "js@acme.com",
        //           "phone": 33612341234,
        //           "company": "Acme",
        //           "lastName": "Service",
        //           "firstName": "Return"
        //         },
        //         "location": {
        //           "city": "Paris",
        //           "number": 4,
        //           "street": "boulevard des Capucines",
        //           "postalCode": 75009,
        //           "countryIsoCode": "FR"
        //         },
        //         "additionalInformation": "Aller au sous-sol."
        //       },
        //       "pickupPointCode": "99439",
        //       "dropOffPointCode": "99438",
        //       "customsDeclaration": {
        //         "reason": "SALE",
        //         "articles": [
        //           {
        //             "quantity": 2,
        //             "unitValue": {
        //               "value": 15,
        //               "currency": "EUR"
        //             },
        //             "unitWeight": 0.7,
        //             "description": "Illustrated book for children",
        //             "tariffNumber": "49030000",
        //             "originCountry": "EEE",
        //             "packageExternalId": "XYZ12345"
        //           }
        //         ]
        //       }
        //     },
        //     "labelType": "PDF_A4",
        //     "shippingOfferId": "string",
        //     "shippingOfferCode": "MONR-CpourToi",
        //     "expectedTakingOverDate": "2022-10-20"
        //   }
    }
}
