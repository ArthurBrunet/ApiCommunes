<?php

namespace App\Controller;

use App\Entity\Commune;
use App\Entity\Media;
use App\Repository\CommuneRepository;
use App\Repository\MediaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class CommuneController extends AbstractController
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
     * @Route("/api/communes", name="communes", methods={"GET"})
     * @param CommuneRepository $communeRepository
     * @param Request $request
     * @return JsonResponse
     */
    public function communes(CommuneRepository $communeRepository,Request $request)
    {
        $filter = [];
        $em = $this->getDoctrine()->getManager();
        $metadata = $em->getClassMetadata(Commune::class)->getFieldNames();
        foreach($metadata as $value){
            if ($request->query->get($value)){
                $filter[$value] = $request->query->get($value);
            }
        }
        return JsonResponse::fromJsonString($this->serializeJson($communeRepository->findBy($filter)));
    }

    /**
     * @Route("/api/commune/create", name="communeCreate", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function communeCreate(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $commune = new Commune();
        $data = json_decode($request->getContent(),true);
        $commune
            ->setNom($data['nom'])
            ->setCode($data['code'])
            ->setcodeDepartement($data['codeDepartement'])
            ->setcodeRegion($data['codeRegion'])
            ->setpopulation($data['population'])
            ->setcodesPostaux($data['codesPostaux']);
        if ($data['media']) {
            $arrayMedia = $data['media'];
            for ($i = 0;$i < count($arrayMedia);$i++){
                $dataMedia = $arrayMedia[$i];
                $media = new Media();
                $media->setCommune($commune)
                    ->setUrl($dataMedia['url']);
                $em->persist($media);
            }
        }
        $em->persist($commune);
        $em->flush();
        return JsonResponse::fromJsonString($this->serializeJson($commune));
    }

    /**
     * @Route("/api/commune/update", name="communeUpdate", methods={"PATCH"})
     * @param Request $request
     * @param CommuneRepository $communeRepository
     * @param MediaRepository $mediaRepository
     * @return JsonResponse
     */
    public function communeUpdate(Request $request, CommuneRepository $communeRepository, MediaRepository $mediaRepository)
    {
        $em = $this->getDoctrine()->getManager();
        $item = json_decode($request->getContent(),true);
        $commune = $communeRepository->findOneBy(['id' => $item['id']]);

        isset($item["nom"]) && $commune->setNom($item['nom']);
        isset($item["code"]) && $commune->setCode($item['code']);
        isset($item["codeDepartement"]) && $commune->setcodeDepartement($item['codeDepartement']);
        isset($item["codeRegion"]) && $commune->setcodeRegion($item['codeRegion']);
        isset($item["population"]) && $commune->setpopulation($item['population']);
        isset($item["codesPostaux"]) && $commune->setcodesPostaux($item['codesPostaux']);
        if ($item["media"]){
            for ($i = 0;$i < count($item["media"]);$i++){
                $data = $item["media"][$i];
                $media = $mediaRepository->findOneBy(['id' => $data['id']]);
                $media->setUrl($data['url']);
                $em->persist($media);
            }
        }
        $em->persist($commune);
        $em->flush();
        return JsonResponse::fromJsonString($this->serializeJson($commune));
    }

    /**
     * @Route("/api/commune/delete", name="communeDelete", methods={"DELETE"})
     * @param Request $request
     * @param CommuneRepository $communeRepository
     * @return Response
     */
    public function communeDelete(Request $request, CommuneRepository $communeRepository)
    {
        $em = $this->getDoctrine()->getManager();
        $item = json_decode($request->getContent(),true);
        $commune = $communeRepository->find($item['id']);
        $em->remove($commune);
        $em->flush();
        return new Response("ok");
    }
}
