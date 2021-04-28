<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

class ProductController extends AbstractController
{
    /**
     * @Route("/", name="product_create")
     * @Method({"POST"})
     */
    public function createAction(Request $request)
    {
        echo "yo";
        // $data = $request->getContent();
        // $article = $this->get('serializer')->deserialize($data, 'App\Entity\Product', 'json');
        // dump($article);
        // die;
    }
}
