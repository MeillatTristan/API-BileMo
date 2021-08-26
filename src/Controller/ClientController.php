<?php

namespace App\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;
use App\Service\HateoasService;
use App\Service\PaginatorService;
use App\Service\CacheService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\Model as Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

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
     * Create a client
     * @Route("/api/clients/post", name="clientAdd", methods="POST")
     * 
     * @OA\Response(
     *     response=201,
     *     description="Return client added",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Client::class, groups={"ClientShow"}))
     *     )
     * )
     * 
     * @OA\Response(
     *     response=400,
     *     description="data doesn't valid",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Client::class, groups={"ClientShow"}))
     *     )
     * )
     * 
     * @OA\Parameter(
     *     name="email",
     *     in="query",
     *     description="email of client",
     *     required=true,
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Parameter(
     *     name="name",
     *     in="query",
     *     description="name of client",
     *     required=true,
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="Clients")
     * @Security(name="Bearer")
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
     * List all Clients
     * 
     * @Route("/api/clients/{page}", name="clientsShow", methods="GET")
     * 
     * @OA\Response(
     *     response=200,
     *     description="List all clients",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Client::class, groups={"ClientShow"}))
     *     )
     * )
     * 
     * @OA\Parameter(
     *     name="page",
     *     in="path",
     *     description="page number",
     *     @OA\Schema(type="integer")
     * )
     * 
     * @OA\Tag(name="Clients")
     * @Security(name="Bearer")
     */
    public function showAll(ClientRepository $clientRepository, String $page, PaginatorService $paginator, CacheService $cacheService, Request $request)
    {
        $query = $clientRepository->findByUser($this->getUser());

        $clientsPaginate = $paginator->paginate($query, '10', $page);

        $data = $this->hateoasService->serializeHypermedia($clientsPaginate, 'ClientShow');

        $response = new JsonResponse($data, 200, ['Content-Type' => 'application/json'], true);
        return $cacheService->addToCache($request, $response);
    }

    /**
     * Return data of a client with a ID params
     * @Route("/api/clients/{id}", name="clientShow", methods="GET")
     * 
     * @OA\Response(
     *     response=200,
     *     description="Return the client requested",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Client::class, groups={"ClientShow"}))
     *     )
     * )
     * 
     * @OA\Response(
     *     response=404,
     *     description="Client not found",
     *     @OA\JsonContent(example = "Client not found")
     * )
     * 
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="resource ID",
     *     @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="Clients")
     * @Security(name="Bearer")
     */
    public function showDetail(string $id, CacheService $cacheService, Request $request){
        if(empty($client = $this->getDoctrine()->getRepository(Client::class)->find($id))){
            $response = $this->json(['message' => 'Clients not found'], 404, [], []);
            return $cacheService->addToCache($request, $response);
        }

        $data = $this->hateoasService->serializeHypermedia($client, 'ClientShow');

        $response = new JsonResponse($data, 200, ['Content-Type' => 'application/json'], true);
        
        return $cacheService->addToCache($request, $response);
    }

    /**
     * Update a client with ID in params
     * 
     * @Route("/api/clients/{id}/put", name="client_update", methods="PUT")
     * 
     * @OA\Response(
     *     response=200,
     *     description="Client has been updated",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Client::class, groups={"ClientShow"}))
     *     )
     * )
     * 
     * @OA\Response(
     *     response=404,
     *     description="Client not found",
     *     @OA\JsonContent(example = "Client not found")
     * )
     * 
     * @OA\Response(
     *     response=400,
     *     description="Data doesn't valid",
     *     @OA\JsonContent(example = "Data doesn't valid")
     * )
     * 
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="resource ID",
     *     @OA\Schema(type="integer")
     * )
     * 
     * @OA\Parameter(
     *     name="name",
     *     in="query",
     *     description="name of Client",
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Parameter(
     *     name="email",
     *     in="query",
     *     description="email of Client",
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="Clients")
     * @Security(name="Bearer")
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
     * Delete the user match with ID params
     * 
     * @Route("/api/clients/{id}/delete", name="client_delete", methods="DELETE")
     * 
     * @OA\Response(
     *     response=400,
     *     description="Client not found",
     *     @OA\JsonContent(example = "Client not found")
     * )
     * 
     * @OA\Response(
     *     response=204,
     *     description="client has been deleting",
     *     @OA\JsonContent(example = "client has been deleting")
     * )
     * 
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="resource ID",
     *     @OA\Schema(type="integer")
     * )
     * 
     * @OA\Tag(name="Clients")
     * @Security(name="Bearer")
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
