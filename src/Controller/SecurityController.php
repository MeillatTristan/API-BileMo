<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use App\Service\HateoasService;
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
   * @Route("/api/register")
   * @Method({"POST"})
   * 
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
   * @Route("/api/users/showAll", name="usersShow")
   * @Method({"GET"})
   */
  public function showAll()
  {
    $users = $this->getDoctrine()->getRepository(User::class)->findAll();

    $data = $this->hateoasService->serializeHypermedia($users, 'UserShow');

    $response = new JsonResponse($data, 200, ['Content-Type' => 'application/json'], true);
    return $response;

    return $response;
  }

  /**
   * @Route("/api/users/{id}/show", name="userShow")
   * @Method({"GET"})
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
   * @Route("/api/users/showClients", name="userShowClients")
   * @Method({"GET"})
   */
  public function showClients(ClientRepository $ClientRepo)
  {
    $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
    $clients = $ClientRepo->findByUser($this->getUser());
    $data = $this->hateoasService->serializeHypermedia($clients, 'UserShow');

    $response = new JsonResponse($data, 200, ['Content-Type' => 'application/json'], true);

    return $response;
  }

  /**
   * @Route("/api/users/{id}/put", name="user_update")
   * @Method({"PUT"})
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
   * @Route("/api/users/{id}/delete", name="user_delete")
   * @Method({"DELETE"})
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
