<?php

namespace App\Controller;

use App\Entity\Advice;
use App\Repository\AdviceRepository;
use App\Repository\MonthRepository;
use App\Repository\UserRepository;
use App\Service\MonthService;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

final class AdviceController extends AbstractController
{
    #[Route('/conseil/', name: 'conseilsDuMoisEnCours', methods: ['GET'])]
    public function getAllAdvice(
        AdviceRepository $adviceRepository,
        SerializerInterface $serializer,
        MonthService $monthService
    ): JsonResponse {

        $currentMonthName = $monthService->getCurrentMonthName() ?? null;

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
    public function getAdviceByMonth(string $mois, 
    AdviceRepository $adviceRepository, 
    SerializerInterface $serializer,
    MonthService $monthService): JsonResponse
    {
        $monthName = $monthService->getMonthName($mois) ?? null;

        if ($monthName === null) {
            throw new InvalidArgumentException("Le mois '$mois' n'est pas valide.");
        }

        $advice = $adviceRepository->findByMonth($monthName);

        if (empty($advice)) {
            throw new NotFoundHttpException("Aucun conseil trouvé pour le mois de $monthName.");
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

    #[Route('/conseil/view/{id}', name: 'conseilParId', methods: ['GET'])]
    public function getAdviceById(int $id, AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
    {
        $advice = $adviceRepository->find($id);

        if (!$advice) {
            throw new NotFoundHttpException("Conseil avec l'ID $id non trouvé.");
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
            throw new NotFoundHttpException("Conseil avec l'ID $id non trouvé.");
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
            throw new InvalidArgumentException('L\'ID de l\'utilisateur est requis.');
        }

        if (!isset($content['month_ids']) || empty($content['month_ids'])) {
            throw new InvalidArgumentException('Le mois du conseil est requis.');
        }

        if (!isset($content['description']) || empty($content['description'])) {
            throw new InvalidArgumentException('La description du conseil est requis.');
        }

        $userId = $content['user_id'];

        if (!$userRepository->find($userId)) {
            throw new NotFoundHttpException('Utilisateur non trouvé.');
        }

        $advice->setUser($userRepository->find($userId));

        $monthIdArrays = $content['month_ids'];

        foreach ($monthIdArrays as $monthId) {
            if (!$monthRepository->find($monthId)) {
                throw new NotFoundHttpException('Mois non trouvé.');
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

        $updatedAdvice = $serializer->deserialize(
            $request->getContent(),
            Advice::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAdvice]
        );

        $content = $request->toArray();

        if (!isset($content['user_id']) || empty($content['user_id'])) {
            throw new InvalidArgumentException('L\'ID de l\'utilisateur est requis.');
        }

        if (!isset($content['month_ids']) || empty($content['month_ids'])) {
            throw new InvalidArgumentException('Le mois du conseil est requis.');
        }

        if (!isset($content['description']) || empty($content['description'])) {
            throw new InvalidArgumentException('La description du conseil est requis.');
        }

        $userId = $content['user_id'];

        if (!$userRepository->find($userId)) {
            throw new NotFoundHttpException('Utilisateur non trouvé.');
        }

        $updatedAdvice->setUser($userRepository->find($userId));

        $monthIdArrays = $content['month_ids'];

        foreach ($monthIdArrays as $monthId) {
            if (!$monthRepository->find($monthId)) {
                throw new NotFoundHttpException('Mois non trouvé.');
            }
            $month = $monthRepository->find($monthId);
            $updatedAdvice->addMonth($month);
        }

        $entityManager->persist($updatedAdvice);
        $entityManager->flush();

        return new JsonResponse(
            null, 
            Response::HTTP_NO_CONTENT 
        );
    }
}
