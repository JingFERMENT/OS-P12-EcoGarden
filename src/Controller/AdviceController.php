<?php

namespace App\Controller;

use App\Entity\Advice;
use App\Repository\AdviceRepository;
use App\Repository\MonthRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

final class AdviceController extends AbstractController
{
    private const MONTHS = [
        '01' => 'janvier',
        '02' => 'février',
        '03' => 'mars',
        '04' => 'avril',
        '05' => 'mai',
        '06' => 'juin',
        '07' => 'juillet',
        '08' => 'août',
        '09' => 'septembre',
        '10' => 'octobre',
        '11' => 'novembre',
        '12' => 'décembre',
    ];

    #[Route('/conseil/', name: 'conseilsDuMoisEnCours', methods: ['GET'])]
    public function getAllAdvice(AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
    {
        $currentMonth = (new \DateTime())->format('m');

        $currentMonthName = self::MONTHS[$currentMonth];

        $advice = $adviceRepository->findByMonth($currentMonthName);

        $jsonadviceList = $serializer->serialize($advice, 'json', [
            'groups' => ['getAdvice'], // Specify the serialization group
        ]);

        return new JsonResponse(
            $jsonadviceList, // The serialized data
            Response::HTTP_OK, // HTTP status code
            [], // Headers can be added here if needed
            true // This tells Symfony to not encode the data again
        );
    }

    #[Route('/conseil/{mois}', name: 'conseilsParMois', methods: ['GET'])]
    public function getAdviceByMonth(string $mois, AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
    {
        $monthName = self::MONTHS[$mois] ?? null;

        if ($monthName === null) {
            return new JsonResponse(['error' => "Le mois '$mois' n'existe pas."], Response::HTTP_BAD_REQUEST);
        }

        $advice = $adviceRepository->findByMonth($monthName);

        if (empty($advice)) {
            return new JsonResponse(['error' => "Aucun conseil trouvé pour le mois de $monthName."], Response::HTTP_NOT_FOUND);
        }

        $jsonadviceList = $serializer->serialize($advice, 'json', [
            'groups' => ['getAdvice'], // Specify the serialization group
        ]);

        return new JsonResponse(
            $jsonadviceList, // The serialized data
            Response::HTTP_OK, // HTTP status code
            [], // Headers can be added here if needed
            true // This tells Symfony to not encode the data again
        );
    }

    #[Route('/conseil/{id}', name: 'conseilParId', methods: ['GET'])]
    public function getAdviceById(int $id, AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
    {
        $advice = $adviceRepository->find($id);

        if (!$advice) {
            return new JsonResponse(['error' => 'Conseil non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        $jsonadvice = $serializer->serialize($advice, 'json', [
            'groups' => ['getAdvice'], // Specify the serialization group
        ]);

        return new JsonResponse(
            $jsonadvice, // The serialized data
            Response::HTTP_OK, // HTTP status code
            [], // Headers can be added here if needed
            true // This tells Symfony to not encode the data again
        );
    }

    #[Route('/conseil/{id}', name: 'supprimerUnConseil', methods: ['DELETE'])]
    public function deleteAdvice(int $id, AdviceRepository $adviceRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $advice = $adviceRepository->find($id);

        if (!$advice) {
            return new JsonResponse(['error' => 'Conseil non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($advice);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Conseil supprimé avec succès.'], Response::HTTP_NO_CONTENT);
    }

    #[Route('/conseil', name: 'ajouterUnConseil', methods: ['POST'])]
    public function createAdvice(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        MonthRepository $monthRepository,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse {
        // Deserialize the JSON request content into an Advice entity
        $advice = $serializer->deserialize($request->getContent(), Advice::class, 'json');

        $content = $request->toArray();

        if (!isset($content['user_id']) || empty($content['user_id'])) {
            return new JsonResponse(['error' => 'L\'ID de l\'utilisateur est requis.'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($content['month_ids']) || empty($content['month_ids'])) {
            return new JsonResponse(['error' => 'Le mois du conseil est requis.'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($content['description']) || empty($content['description'])) {
            return new JsonResponse(['error' => 'La description du conseil est requis.'], Response::HTTP_BAD_REQUEST);
        }

        $userId = $content['user_id'];

        if (!$userRepository->find($userId)) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        $advice->setUser($userRepository->find($userId));

        $monthIdArrays = $content['month_ids'];

        foreach ($monthIdArrays as $monthId) {
            if (!$monthRepository->find($monthId)) {
                return new JsonResponse(['error' => 'Mois non trouvé.'], Response::HTTP_NOT_FOUND);
            }
            $month = $monthRepository->find($monthId);
            $advice->addMonth($month);
        }

        $entityManager->persist($advice);
        $entityManager->flush();

        $jsonadviceList = $serializer->serialize($advice, 'json', [
            'groups' => ['getAdvice'], // Specify the serialization group
        ]);

        $location = $urlGenerator->generate('conseilParId', ['id' => $advice->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse(
            $jsonadviceList, // The serialized data
            Response::HTTP_CREATED, // HTTP status code
            ["Location" => $location], // Headers can be added here if needed
            true // This tells Symfony to not encode the data again
        );
    }

    #[Route('/conseil/{id}', name: 'mettreAJourUnConseil', methods: ['PUT'])]
    public function updateAdvice(
        Request $request,
        SerializerInterface $serializer,
        Advice $currentAdvice,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        MonthRepository $monthRepository,
    ): JsonResponse {
       
        $updatedAdvice = $serializer->deserialize($request->getContent(), 
            Advice::class, 
            'json', 
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAdvice]);
        
        $content = $request->toArray();

        if (!isset($content['user_id']) || empty($content['user_id'])) {
            return new JsonResponse(['error' => 'L\'ID de l\'utilisateur est requis.'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($content['month_ids']) || empty($content['month_ids'])) {
            return new JsonResponse(['error' => 'Le mois du conseil est requis.'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($content['description']) || empty($content['description'])) {
            return new JsonResponse(['error' => 'La description du conseil est requis.'], Response::HTTP_BAD_REQUEST);
        }

        $userId = $content['user_id'];

        if (!$userRepository->find($userId)) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        $updatedAdvice->setUser($userRepository->find($userId));

        $monthIdArrays = $content['month_ids'];

        foreach ($monthIdArrays as $monthId) {
            if (!$monthRepository->find($monthId)) {
                return new JsonResponse(['error' => 'Mois non trouvé.'], Response::HTTP_NOT_FOUND);
            }
            $month = $monthRepository->find($monthId);
            $updatedAdvice->addMonth($month);
        }

        $entityManager->persist($updatedAdvice);
        $entityManager->flush();

        return new JsonResponse(
            null, // The serialized data
            Response::HTTP_NO_CONTENT // HTTP status code 
        );
    }
}