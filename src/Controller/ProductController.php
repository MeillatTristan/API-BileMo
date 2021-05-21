<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\HateoasService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation as Doc;
use Symfony\Component\HttpFoundation\JsonResponse;
use UsingInterfaces\ProductInterface;

/**
 * Class ApiProductController
 *
 * @package App/Controller
 * 
 * @Security(name="Bearer")
 * @OA\Tag(name="Product")
 */
class ProductController extends AbstractController
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
     * @Route("/api/products/showAll", name="productsShow")
     * @Method({"GET"})
     */
    public function showAll(ProductRepository $productRepository)
    {
        $products = $productRepository->findAll();
        $data = $this->hateoasService->serializeHypermedia($products, 'default');

        $response = new JsonResponse($data, 200, ['Content-Type' => 'application/json'], true);
        return $response;
    }

    /**
     * @Route("/api/products/{id}/show", name="productShow")
     * @Method({"GET"})
     */
    public function showDetail(string $id){
        if(empty($product = $this->getDoctrine()->getRepository(Product::class)->find($id))){
            return $this->json(['message' => 'Product not found'], 404, [], []);
        }

        $data = $this->hateoasService->serializeHypermedia($product, 'default');

        $response = new JsonResponse($data, 200, ['Content-Type' => 'application/json'], true);

        return $response;
    }

    /**
     * @Route("/api/products/post", name="product_create")
     * @Method({"POST"})
     */
    public function post(Request $request, SerializerInterface $serializer, EntityManagerInterface $manager, ValidatorInterface $validator){
        $jsonRecu = $request->getContent();

        try {
            $post = $serializer->deserialize($jsonRecu, Product::class, 'json');
            $errors = $validator->validate($post);

            if(count($errors) > 0){
                return $this->json($errors, 400);
            }
        
            $manager->persist($post);
            $manager->flush();

            return $this->json($post, 201, [], []);
        }catch (NotEncodableValueException $e) {
            return $this->json([
                'status' => 400,
                'message' => $e->getMessage()
            ],400);
        }
    }

    /**
     * @Route("/api/products/{id}/put", name="product_update")
     * @Method({"PUT"})
     */
    public function update(String $id, Request $request, EntityManagerInterface $manager){
        $product = $this->getDoctrine()->getRepository(Product::class)->find($id);
        if(empty($product)){
            return $this->json(['message' => 'Product not found'], 404, [], []);
        }
        $data = json_decode($request->getContent(), 'json');
        try {
            if(array_key_exists('model', $data) && array_key_exists('brand', $data) && array_key_exists('price', $data)){
                $product->setModel($data['model']);
                $product->setBrand($data['brand']);
                $product->setPrice($data['price']);
                $manager->flush();
                return $this->json(["message" => "Product has been update", "product" => $product], 200, [], []);
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
     * @Route("/api/products/{id}/delete", name="product_delete")
     * @Method({"DELETE"})
     */
    public function delete(String $id, EntityManagerInterface $manager){
        $product = $this->getDoctrine()->getRepository(Product::class)->find($id);

        if(!empty($product)){
            $manager->remove($product);
            $manager->flush();
            return $this->json(["message" => "product has been deleting"], 204, [], []);
        }
        else{
            return $this->json([
                'status' => 400,
                'message' => "No product with this id"
            ],400);
        }
    }
}
