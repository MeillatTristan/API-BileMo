<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use App\Service\HateoasService;
use App\Service\PaginatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\Model as Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class SecurityController extends AbstractController
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
   * Add a new user
   * 
   * @Route("/api/register", name="userRegister", methods="POST")
   * 
   * @OA\Response(
     *     response=201,
     *     description="Return client added",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"UserShow"}))
     *     )
     * )
     * 
     * @OA\Response(
     *     response=400,
     *     description="data doesn't valid",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"UserShow"}))
     *     )
     * )
     * 
     * @OA\Parameter(
     *     name="username",
     *     in="query",
     *     description="username of client",
     *     required=true,
     *     @OA\Schema(type="string")
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
     * @OA\Tag(name="Users")
     * @Security(name="Bearer")
   */
  public function register(Request $request, UserPasswordEncoderInterface $encoder, EntityManagerInterface $manager, ValidatorInterface $validator, SerializerInterface $serializer)
  {
    $jsonRecu = $request->getContent();

    try {
      $user = $serializer->deserialize($jsonRecu, User::class, 'json');
      $errors = $validator->validate($user);
      $user->setPassword($encoder->encodePassword($user, $user->getPassword(['password'])));

      if (count($errors) > 0) {
        return $this->json($errors, 400);
      }

      $manager->persist($user);
      $manager->flush();

      return $this->json(["success" => $user->getUsername()], 201, [], []);
    } catch (NotEncodableValueException $e) {
      return $this->json([
        'status' => 400,
        'message' => $e->getMessage()
      ], 400);
    }
  }

  /**
   * List all users
   * 
   * @Route("/api/users/showAll/{page}", name="usersShow", methods="GET")
   * 
   * @OA\Response(
   *     response=200,
   *     description="List all users",
   *     @OA\JsonContent(
   *        type="array",
   *        @OA\Items(ref=@Model(type=User::class, groups={"UserShow"}))
   *     )
   * )
   * 
   * @OA\Parameter(
     *     name="page",
     *     in="path",
     *     description="page number",
     *     @OA\Schema(type="integer")
     * )
   * @OA\Tag(name="Users")
   * @Security(name="Bearer")
   */
  public function showAll(UserRepository $productRepository, String $page, PaginatorService $paginator)
  {
    $query = $productRepository->findPageByProduct();

    $usersPaginate = $paginator->paginate($query, '10', $page);

    $data = $this->hateoasService->serializeHypermedia($usersPaginate, 'UserShow');

    $response = new JsonResponse($data, 200, ['Content-Type' => 'application/json'], true);
    return $response;

  }

  /**
   * Return user detail with ID params
   * 
   * @Route("/api/users/{id}/show", name="userShow", methods="GET")
   * 
   * @OA\Response(
   *     response=200,
   *     description="Return the user requested",
   *     @OA\JsonContent(
   *        type="array",
   *        @OA\Items(ref=@Model(type=User::class, groups={"UserShow"}))
   *     )
   * )
   * 
   * @OA\Response(
   *     response=404,
   *     description="User not found",
   *     @OA\JsonContent(example = "User not found")
   * )
   * 
   * @OA\Parameter(
   *     name="id",
   *     in="path",
   *     description="resource ID",
   *     @OA\Schema(type="integer")
   * )
   * @OA\Tag(name="Users")
   * @Security(name="Bearer")
   */
  public function showDetail(string $id)
  {
    if (empty($user = $this->getDoctrine()->getRepository(User::class)->find($id))) {
      return $this->json(['message' => 'User not found'], 404, [], []);
    }

    $data = $this->hateoasService->serializeHypermedia($user, 'UserShow');

    $response = new JsonResponse($data, 200, ['Content-Type' => 'application/json'], true);
    return $response;

    return $response;
  }

  /**
   * List all clients link with the user connected
   * 
   * @Route("/api/users/showClients/{page}", name="userShowClients", methods="GET")
   * 
   * @OA\Response(
   *     response=200,
   *     description="List all client link with the user connected",
   *     @OA\JsonContent(
   *        type="array",
   *        @OA\Items(ref=@Model(type=Client::class, groups={"UserShow"}))
   *     )
   * )
   * @OA\Tag(name="Users")
   * @Security(name="Bearer")
   */
  public function showClients(ClientRepository $ClientRepo, String $page, PaginatorService $paginator)
  {
    $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

    $query = $ClientRepo->findByUser($this->getUser());

    $clientsPaginate = $paginator->paginate($query, '10', $page);
    $data = $this->hateoasService->serializeHypermedia($clientsPaginate, 'UserShow');

    $response = new JsonResponse($data, 200, ['Content-Type' => 'application/json'], true);

    return $response;
  }

  /**
   * update a user
   * 
   * @Route("/api/users/{id}/put", name="user_update", methods="PUT")
   * 
   * @OA\Response(
   *     response=200,
   *     description="Return the user requested",
   *     @OA\JsonContent(
   *        type="array",
   *        @OA\Items(ref=@Model(type=User::class, groups={"UserShow"}))
   *     )
   * )
   * 
   * @OA\Response(
   *     response=404,
   *     description="User not found",
   *     @OA\JsonContent(example = "User not found")
   * )
   * 
   * @OA\Parameter(
   *     name="id",
   *     in="path",
   *     description="resource ID",
   *     @OA\Schema(type="integer")
   * )
   * @OA\Tag(name="Users")
   * @Security(name="Bearer")
   */
  public function update(String $id, Request $request, EntityManagerInterface $manager, UserPasswordEncoderInterface $encoder, ValidatorInterface $validator)
  {
    $user = $this->getDoctrine()->getRepository(User::class)->find($id);
    if (empty($user)) {
      return $this->json(['message' => 'User not found'], 404, [], []);
    }
    $data = json_decode($request->getContent(), 'json');
    try {
      if (array_key_exists('email', $data) && array_key_exists('username', $data) && array_key_exists('password', $data)) {
        $user->setEmail($data['email']);
        $user->setUsername($data['username']);
        $user->setPassword($encoder->encodePassword($user, $data['password']));

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
          return $this->json($errors, 400);
        }

        $manager->flush();
        return $this->json(["message" => "Users has been update", "user" => $user], 200, [], ['groups' => 'userShow']);
      } else {
        return $this->json([
          'status' => 400,
          'message' => "data doesn't valid"
        ], 400);
      }
    } catch (NotEncodableValueException $e) {
      return $this->json([
        'status' => 400,
        'message' => $e->getMessage()
      ], 400);
    }
  }

  /**
   * Delete a user
   * 
   * @Route("/api/users/{id}/delete", name="user_delete", methods="DELETE")
   * 
   * @OA\Response(
     *     response=400,
     *     description="User not found",
     *     @OA\JsonContent(example = "User not found")
     * )
     * 
     * @OA\Response(
     *     response=204,
     *     description="user has been deleting",
     *     @OA\JsonContent(example = "user has been deleting")
     * )
     * 
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="resource ID",
     *     @OA\Schema(type="integer")
     * )
     * 
     * @OA\Tag(name="Users")
     * @Security(name="Bearer")
   */
  public function delete(String $id, EntityManagerInterface $manager)
  {
    $user = $this->getDoctrine()->getRepository(User::class)->find($id);

    if (!empty($user)) {
      $manager->remove($user);
      $manager->flush();
      return $this->json(["message" => "user has been deleting"], 204, [], []);
    } else {
      return $this->json([
        'status' => 400,
        'message' => "No user with this id"
      ], 400);
    }
  }
}
