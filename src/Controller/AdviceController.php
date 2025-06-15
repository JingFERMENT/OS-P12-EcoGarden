<?php

namespace App\Controller;

use App\Entity\Advice;
use App\Repository\AdviceRepository;
use App\Repository\MonthRepository;
use App\Repository\UserRepository;
use App\Service\MonthService;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class AdviceController extends AbstractController
{
    #[Route('/conseil/', name: 'conseilsDuMoisEnCours', methods: ['GET'])]
    /**
     * Retrieves all advice for the current month.
     *
     * @param AdviceRepository $adviceRepository
     * @param SerializerInterface $serializer
     * @param MonthService $monthService
     * @return JsonResponse
     */
    public function getAllAdvice(
        AdviceRepository $adviceRepository,
        SerializerInterface $serializer,
        MonthService $monthService,
        Request $request,
        TagAwareCacheInterface $cachePool
    ): JsonResponse {

        $requestPage = $request->get('page', 1);

        if (!is_numeric($requestPage) || $requestPage < 1) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, 'Le paramètre page doit être un nombre entier positif.');
        }

        $requestLimit = $request->get('limit', 10);

        if (!is_numeric($requestLimit) || $requestLimit < 1) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, 'Le paramètre limit doit être un nombre entier positif.');
        }

        $currentMonthName = $monthService->getCurrentMonthName() ?? null;

        if ($currentMonthName === null) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, 'Le mois actuel n\'est pas valide.');
        }

        // create a cache key 
        $idCache = 'GetAllAdviceOfCurrentMonth-' . $requestPage . "-" . $requestLimit;

        // Use the cache pool to get the cached advice or fetch it if not cached
        $jsonadviceList = $cachePool->get(
            $idCache,
            // ItemInterface : interface que Symfony utilise pour manipuler une entrée dans le cache.
            function (ItemInterface $item) use (
                $adviceRepository,
                $requestPage,
                $requestLimit,
                $monthService,
                $serializer
            ) {
                // echo("ELEMENT PAS EN CACHE, ON LE RECUPERE DEPUIS LA BASE DE DONNEES");
                $item->expiresAfter(3600); // Cache for 1 hour
                $item->tag('adviceOfCurrentMonth'); // Tag the cache item for invalidation

                $cachedAdvice = $adviceRepository->findWithPaginationByMonth($requestPage, $requestLimit, $monthService->getCurrentMonthName());

                if (empty($cachedAdvice)) {
                    throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, 'Aucun conseil trouvé pour le mois de ' . $monthService->getCurrentMonthName() . '.');
                }

                $context = SerializationContext::create()
                    ->setGroups(['getAdvice']); // Specify the serialization group

                //lazy-load the advice data
                return $serializer->serialize($cachedAdvice, 
                'json', 
                $context, // Specify the serialization group
                );
            }
        );


        return new JsonResponse(
            $jsonadviceList, // The serialized data
            Response::HTTP_OK, // HTTP status code
            [], // Headers can be added here if needed
            true // This tells Symfony to not encode the data again
        );
    }

    #[Route('/conseil/{mois}', name: 'conseilsParMois', methods: ['GET'])]
    public function getAdviceByMonth(
        string $mois,
        AdviceRepository $adviceRepository,
        SerializerInterface $serializer,
        MonthService $monthService,
        Request $request
    ): JsonResponse {

        $requestPage = $request->get('page', 1);

        if (!is_numeric($requestPage) || $requestPage < 1) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, 'Le paramètre page doit être un nombre entier positif.');
        }

        $requestLimit = $request->get('limit', 10);

        if (!is_numeric($requestLimit) || $requestLimit < 1) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, 'Le paramètre limit doit être un nombre entier positif.');
        }

        $monthName = $monthService->getMonthName($mois);
        

        if ($monthName === null) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "Le mois '$mois' n'est pas valide.");
        }

        $monthName = $monthService->getMonthName($mois);
        

        $advice = $adviceRepository->findWithPaginationByMonth($requestPage, $requestLimit, $monthName);

        if (empty($advice)) {
            throw new NotFoundHttpException("Aucun conseil trouvé pour le mois de $monthName.");
        }

        $context = SerializationContext::create()
            ->setGroups(['getAdvice']); // Specify the serialization group

        $jsonadviceList = $serializer->serialize($advice, 'json', $context);

        return new JsonResponse(
            $jsonadviceList, // The serialized data
            Response::HTTP_OK, // HTTP status code
            [], // Headers can be added here if needed
            true // This tells Symfony to not encode the data again
        );
    }

    #[Route('/conseil/{id}', name: 'supprimerUnConseil', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits nécessaires pour supprimer un conseil.')]
    public function deleteAdvice(
        int $id,
        AdviceRepository $adviceRepository,
        EntityManagerInterface $entityManager,
        TagAwareCacheInterface $cachePool
    ): JsonResponse {
        // invalidate the cache for the current month's advice
        $cachePool->invalidateTags(["adviceOfCurrentMonth"]);
        
        $advice = $adviceRepository->find($id);

        if (!$advice) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "Conseil avec l'ID $id non trouvé.");
        }

        $entityManager->remove($advice);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Conseil supprimé avec succès.'], Response::HTTP_NO_CONTENT);
    }

     #[Route('/conseil/view/{id}', name: 'conseilParId', methods: ['GET'])]
    public function getAdviceById(int $id, AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
    {
        $advice = $adviceRepository->find($id);

        if (!$advice) {
            throw new NotFoundHttpException("Conseil avec l'ID $id non trouvé.");
        }

        $context = SerializationContext::create()
            ->setGroups(['getAdvice']); // Specify the serialization group

        $jsonadvice = $serializer->serialize($advice, 'json', $context);

        return new JsonResponse(
            $jsonadvice, // The serialized data
            Response::HTTP_OK, // HTTP status code
            [], // Headers can be added here if needed
            true // This tells Symfony to not encode the data again
        );
    }

    #[Route('/conseil', name: 'ajouterUnConseil', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits nécessaires pour ajouter un conseil.')]
    public function createAdvice(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        MonthRepository $monthRepository,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator
    ): JsonResponse {
        $content = $request->toArray();

        if (!isset($content['user_id']) || empty($content['user_id'])) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, 'L\'ID de l\'utilisateur est requis.');
        }

        if (!isset($content['month_ids']) || empty($content['month_ids'])) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, 'Le mois du conseil est requis.');
        }

        $user = $userRepository->find($content['user_id']);

        if (!$user) {
            throw new NotFoundHttpException('Utilisateur non trouvé.');
        }

        // Deserialize the JSON request content into an Advice entity
        $advice = $serializer->deserialize($request->getContent(), Advice::class, 'json');
        
        $newAdvice = new Advice();
        $newAdvice->setDescription($advice->getDescription());
        // Set the user from the repository

        $newAdvice->setUser($user);
    
        $monthIdArrays = $content['month_ids'];
        
        foreach ($monthIdArrays as $monthId) {
            if (!$monthRepository->find($monthId)) {
                throw new NotFoundHttpException('Mois non trouvé.');
            }
            $month = $monthRepository->find($monthId);
            $newAdvice->addMonth($month);
        }

        // Now validate, since user and months are set
        $errors = $validator->validate($newAdvice);

        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, implode(', ', $messages));
        }

        $entityManager->persist($newAdvice);
        $entityManager->flush();

        $context = SerializationContext::create()
            ->setGroups(['getAdvice']); // Specify the serialization group

        $jsonadviceList = $serializer->serialize($newAdvice, 'json', $context); 

        $location = $urlGenerator->generate('conseilParId', ['id' => $newAdvice->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse(
            $jsonadviceList, // The serialized data
            Response::HTTP_CREATED, // HTTP status code
            ["Location" => $location], // Headers can be added here if needed
            true // This tells Symfony to not encode the data again
        );
    }

    #[Route('/conseil/{id}', name: 'mettreAJourUnConseil', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits nécessaires pour mettre à jour un conseil.')]
    public function updateAdvice(
        int $id,
        AdviceRepository $adviceRepository,
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        MonthRepository $monthRepository,
        ValidatorInterface $validator
    ): JsonResponse {

        $currentAdvice = $adviceRepository->find($id);

        if (!$currentAdvice) {
            throw new HttpException(JsonResponse::HTTP_NOT_FOUND, 'Conseil introuvable pour cet ID.');
        }

        $content = $request->toArray();

        if (!isset($content['user_id']) || empty($content['user_id'])) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, 'L\'ID de l\'utilisateur est requis.');
        }

        if (!isset($content['month_ids']) || empty($content['month_ids'])) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, 'Le mois du conseil est requis.');
        }

        if(!isset($content['description']) || empty($content['description'])) {
            $currentAdvice->getDescription();
        } else {
            $currentAdvice->setDescription($content['description']);
        }

        $userId = $content['user_id'];
        $currentAdvice->setUser($userRepository->find($userId));

        if (!$userRepository->find($userId)) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, 'Utilisateur non trouvé.');
        }

        $monthIdArrays = $content['month_ids'];

        foreach ($monthIdArrays as $monthId) {
            if (!$monthRepository->find($monthId)) {
                throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, 'Mois non trouvé.');
            }
            $month = $monthRepository->find($monthId);
            $currentAdvice->addMonth($month);
        }

        // Now validate, since user and months are set
        $errors = $validator->validate($currentAdvice);

        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, implode(', ', $messages));
        }

        $entityManager->persist($currentAdvice);
        $entityManager->flush();

        return new JsonResponse(
            null,
            Response::HTTP_NO_CONTENT
        );
    }
}
