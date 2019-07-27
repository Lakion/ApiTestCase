<?php

declare(strict_types=1);

/*
 * This file is part of the ApiTestCase package.
 *
 * (c) Łukasz Chruściel
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiTestCase\Test\Controller;

use ApiTestCase\Test\Entity\Product;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class SampleController extends Controller
{
    /**
     * @return JsonResponse|Response
     */
    public function helloWorldAction(Request $request)
    {
        $acceptFormat = $request->headers->get('Accept');

        if (
            false !== strpos($acceptFormat, 'application')
            && false !== strpos($acceptFormat, 'json')
        ) {
            return new JsonResponse([
                'message' => 'Hello ApiTestCase World!',
                'unicode' => '€ ¥ 💰',
                'path' => '/p/a/t/h',
            ], 200, [
                'Content-Type' => $acceptFormat,
            ]);
        }

        $content = '<?xml version="1.0" encoding="UTF-8"?><greetings>Hello world!</greetings>';

        $response = new Response($content);
        $response->headers->set('Content-Type', 'application/xml');

        return $response;
    }

    /**
     * @return JsonResponse|Response
     */
    public function useThirdPartyApiAction(Request $request)
    {
        $acceptFormat = $request->headers->get('Accept');
        $content = $this->get('app.third_party_api_client')->getInventory();

        if ('application/json' === $acceptFormat) {
            return new JsonResponse($content);
        }

        $content = sprintf('<?xml version="1.0" encoding="UTF-8"?><message>%s</message>', $content['message']);

        $response = new Response($content);
        $response->headers->set('Content-Type', 'application/xml');

        return $response;
    }

    /**
     * @return JsonResponse|Response
     */
    public function productIndexAction(Request $request)
    {
        $productRepository = $this->getDoctrine()->getRepository('ApiTestCase:Product');
        $products = $productRepository->findAll();

        return $this->respond($request, $products);
    }

    /**
     * @return JsonResponse|Response
     */
    public function categoryIndexAction(Request $request)
    {
        $categoryRepository = $this->getDoctrine()->getRepository('ApiTestCase:Category');
        $categories = $categoryRepository->findAll();

        return $this->respond($request, $categories);
    }

    /**
     * @return JsonResponse|Response
     */
    public function showAction(Request $request)
    {
        $productRepository = $this->getDoctrine()->getRepository('ApiTestCase:Product');
        $product = $productRepository->find($request->get('id'));

        if (!$product) {
            throw $this->createNotFoundException();
        }

        return $this->respond($request, $product);
    }

    public function createAction(Request $request): Response
    {
        $product = new Product();
        $product->setName($request->request->get('name'));
        $product->setPrice($request->request->get('price'));
        $product->setUuid($request->request->get('uuid'));

        /** @var ObjectManager $productManager */
        $productManager = $this->getDoctrine()->getManager();
        $productManager->persist($product);
        $productManager->flush();

        return $this->respond($request, $product, Response::HTTP_CREATED);
    }

    private function respond(Request $request, $data, int $statusCode = Response::HTTP_OK): Response
    {
        $serializer = $this->createSerializer();
        $acceptFormat = $request->headers->get('Accept');

        if ('application/xml' === $acceptFormat) {
            $content = $serializer->serialize($data, 'xml');

            $response = new Response($content, $statusCode);
            $response->headers->set('Content-Type', 'application/xml');

            return $response;
        }

        if ('application/json' === $acceptFormat) {
            $content = $serializer->serialize($data, 'json');
            $response = new Response($content, $statusCode);
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
    }

    private function createSerializer(): Serializer
    {
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        return new Serializer($normalizers, $encoders);
    }
}
