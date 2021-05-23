<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\HateoasService;
use App\Service\PaginatorService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use GuzzleHttp\Psr7\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Nelmio\ApiDocBundle\Annotation\Model as Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ApiProductController
 *
 * @package App/Controller
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
     * List all the product
     * @Route("/api/products/showAll/{page}", name="productsShow", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="List all the product",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Product::class, groups={"default"}))
     *     )
     * )
     * 
     * @OA\Parameter(
     *     name="page",
     *     in="path",
     *     description="page number",
     *     @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="products")
     * @Security(name="Bearer")
     */
    public function showAll(ProductRepository $productRepository, String $page, PaginatorService $paginator)
    {
        $query = $productRepository->findPageByProduct();

        $productsPaginate = $paginator->paginate($query, '10', $page);

        $data = $this->hateoasService->serializeHypermedia($productsPaginate, 'default');

        $response = new JsonResponse($data, 200, ['Content-Type' => 'application/json'], true);
        return $response;
    }

    /**
     * Return one product with the id
     * 
     * @Route("/api/products/{id}/show", name="productShow", methods={"GET"})
     * @Method({"GET"})
     * 
     * @OA\Response(
     *     response=200,
     *     description="Returns on product",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Product::class, groups={"default"}))
     *     )
     * )
     * 
     * @OA\Response(
     *     response=404,
     *     description="Product not found",
     *     @OA\JsonContent(example="Product not found")
     * )
     * 
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="resource ID",
     *     @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="products")
     * @Security(name="Bearer")
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
     * Create an product
     * @Route("/api/products/post", name="product_create", methods={"POST"})
     * 
     * @OA\Response(
     *     response=201,
     *     description="Returns client added",
     *     @Model(type=Product::class)
     * )
     * @OA\Response(
     *     response=400,
     *     description="data doesn't valid",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Product::class, groups={"default"}))
     *     )
     * )
     * 
     * @OA\Parameter(
     *     name="model",
     *     in="query",
     *     description="model of product",
     *     required=true,
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Parameter(
     *     name="brand",
     *     in="query",
     *     description="brand of product",
     *     required=true,
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Parameter(
     *     name="price",
     *     in="query",
     *     description="price of product",
     *     required=true,
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="products")
     * @Security(name="Bearer")
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
     * Update the product
     * @Route("/api/products/{id}/put", name="product_update", methods={"PUT"})
     * 
     * @OA\Response(
     *     response=404,
     *     description="not product found",
     *     @OA\JsonContent(example="Product not found")
     * )
     * @OA\Response(
     *     response=200,
     *     description="product has been update",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Product::class, groups={"default"}))
     *     )
     * )
     * 
     * @OA\Parameter(
     *     name="model",
     *     in="query",
     *     description="model of product",
     *     required=true,
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Parameter(
     *     name="brand",
     *     in="query",
     *     description="brand of product",
     *     required=true,
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Parameter(
     *     name="price",
     *     in="query",
     *     description="price of product",
     *     required=true,
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="products")
     * @Security(name="Bearer")
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
     * Delete the product with id params
     * @Route("/api/products/{id}/delete", name="product_delete", methods={"DELETE"})
     * 
     * @OA\Response(
     *     response=204,
     *     description="Delete the product",
     *     @OA\JsonContent(example="Product has been deleting")
     * )
     * 
     * @OA\Response(
     *     response=400,
     *     description="Product not found",
     *     @OA\JsonContent(example="Product not found")
     * )
     * 
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="resource ID",
     *     @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="products")
     * @Security(name="Bearer")
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
