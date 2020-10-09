<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class UserController extends AbstractController
{

    private function serializeJson($objet){
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return $object->getNom();
            },
        ];
        $normalizer = new ObjectNormalizer(null, null, null, null, null, null, $defaultContext);
        $serializer = new Serializer([$normalizer], [new JsonEncoder()]);

        return $serializer->serialize($objet, 'json');
    }

    /**
     * @Route("/api/login", name="login", methods={"POST"})
     *
     * @param AuthenticationUtils $authenticationUtils
     *
     * @return JsonResponse
     */
    public function login(AuthenticationUtils $authenticationUtils)
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return new JsonResponse([$lastUsername, $error]);
    }


    /**
     * @Route("user/create", name="userCreate", methods={"POST"})
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return JsonResponse
     */
    public function userCreate(Request $request,UserPasswordEncoderInterface $passwordEncoder)
    {
        $em = $this->getDoctrine()->getManager();
        $user = new User();
        $data = json_decode($request->getContent(),true);
        $user
            ->setEmail($data["email"])
            ->setRoles(['ROLE_USER'])
            ->setPassword($passwordEncoder->encodePassword($user,$data["password"]));
        $em->persist($user);
        $em->flush();
        return JsonResponse::fromJsonString($this->serializeJson($user));
    }

    /**
     * @Route("/api/user/update", name="userUpdate", methods={"PATCH"})
     * @param Request $request
     * @param UserRepository $userRepository
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return JsonResponse
     */
    public function userUpdate(Request $request, UserRepository $userRepository,UserPasswordEncoderInterface $passwordEncoder)
    {
        $em = $this->getDoctrine()->getManager();
        $item = json_decode($request->getContent(),true);
        $user = $userRepository->findOneBy(['id' => $item['id']]);

        isset($item["email"]) && $user->setEmail($item['email']);
        isset($item["password"]) && $user->setPassword($passwordEncoder->encodePassword($user,$item["password"]));

        $em->persist($user);
        $em->flush();

        return JsonResponse::fromJsonString($this->serializeJson($user));
    }

    /**
     * @Route("/api/user/delete", name="userDelete", methods={"DELETE"})
     * @param Request $request
     * @param UserRepository $userRepository
     * @return Response
     */
    public function userDelete(Request $request, UserRepository $userRepository)
    {
        $em = $this->getDoctrine()->getManager();
        $item = json_decode($request->getContent(),true);
        $user = $userRepository->find($item['id']);
        $response = new Response();
        if ($user){
            $em->remove($user);
            $em->flush();
            $response
                ->setContent('ok')
                ->setStatusCode(Response::HTTP_OK);
        }else{
            $response
                ->setContent('bad request')
                ->setStatusCode(Response::HTTP_BAD_REQUEST);
        }
        return $response;
    }
}
