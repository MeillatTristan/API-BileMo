<?php

namespace App\Controller;

use App\Entity\Client;
use App\Service\HateoasService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;

class ClientController extends AbstractController
{
    /**
     * @var HateoasService
     */
    private $hateoasService;
    
    /**
     * ApiProductController constructor.
     *
     * @param HateoasService $hateoasService
     */
    public function __construct(HateoasService $hateoasService)
    {
        $this->hateoasService = $hateoasService;
    }
    /**
     * @Route("/api/clients/post", name="clientAdd")
     * @Method({"POST"})
     */
    public function post(Request $request, SerializerInterface $serializer, EntityManagerInterface $manager, ValidatorInterface $validator){
        $jsonRecu = $request->getContent();

        try {
            $client = $serializer->deserialize($jsonRecu, Client::class, 'json');
            $client->setUser($this->getUser());
            $errors = $validator->validate($client);

            if(count($errors) > 0){
                return $this->json($errors, 400);
            }
        
            $manager->persist($client);
            $manager->flush();

            return $this->json($client, 201, [], ['groups' => 'clientShow']);
        }catch (NotEncodableValueException $e) {
            return $this->json([
                'status' => 400,
                'message' => $e->getMessage()
            ],400);
        }
    }

    /**
     * @Route("/api/clients/showAll", name="clientsShow")
     * @Method({"GET"})
     */
    public function showAll()
    {
        $clients = $this->getDoctrine()->getRepository(Client::class)->findAll();

        $data = $this->hateoasService->serializeHypermedia($clients, 'ClientShow');

        $response = new JsonResponse($data, 200, ['Content-Type' => 'application/json'], true);
        return $response;
    }

    /**
     * @Route("/api/clients/{id}/show", name="clientShow")
     * @Method({"GET"})
     */
    public function showDetail(string $id){
        if(empty($client = $this->getDoctrine()->getRepository(Client::class)->find($id))){
            return $this->json(['message' => 'Clients not found'], 404, [], []);
        }

        $data = $this->hateoasService->serializeHypermedia($client, 'ClientShow');

        $response = new JsonResponse($data, 200, ['Content-Type' => 'application/json'], true);
        return $response;

        return $response;
    }

    /**
     * @Route("/api/clients/{id}/put", name="client_update")
     * @Method({"PUT"})
     */
    public function update(String $id, Request $request, EntityManagerInterface $manager){
        $client = $this->getDoctrine()->getRepository(Client::class)->find($id);
        if(empty($client)){
            return $this->json(['message' => 'Client not found'], 404, [], []);
        }
        $data = json_decode($request->getContent(), 'json');
        try {
            if(array_key_exists('email', $data) && array_key_exists('name', $data)){
                $client->setEmail($data['email']);
                $client->setName($data['name']);
                $manager->flush();
                return $this->json(["message" => "Clients has been update", "client" => $client], 200, [], ['groups' => 'clientShow']);
            }
            else{
                return $this->json([
                    'status' => 400,
                    'message' => "data doesn't valid"
                ],400);
            }
        }catch (NotEncodableValueException $e) {
            return $this->json([
                'status' => 400,
                'message' => $e->getMessage()
            ],400);
        }
    }

    /**
     * @Route("/api/clients/{id}/delete", name="client_delete")
     * @Method({"DELETE"})
     */
    public function delete(String $id, EntityManagerInterface $manager){
        $client = $this->getDoctrine()->getRepository(Client::class)->find($id);

        if(!empty($client)){
            $manager->remove($client);
            $manager->flush();
            return $this->json(["message" => "client has been deleting"], 204, [], []);
        }
        else{
            return $this->json([
                'status' => 400,
                'message' => "No client with this id"
            ],400);
        }
    }
}
