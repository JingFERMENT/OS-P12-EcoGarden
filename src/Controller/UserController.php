<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UserController extends AbstractController
{
    #[Route('/user', name: 'creerUser', methods: ['POST'])]
    public function createUser(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ): JsonResponse {

        $content = $request->toArray();

        // Check if the request content is empty
        foreach (['firstName', 'email', 'plainPassword', 'lastName', 'city'] as $field) {
            if (!isset($content[$field])) {
                throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, ucfirst($field) . ' est requis.');
            }
        }

        $user = new User();
        $user->setFirstName($content['firstName']);
        $user->setLastName($content['lastName']);
        $user->setEmail($content['email']);
        $user->setCity($content['city']);
        $user->setPlainPassword($content['plainPassword']);
        $user->setRoles(['ROLE_USER']);

        // Validate the user entity
        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, implode(', ', $errorMessages));
        }

        // Hash the password
        $hashedPassword = $passwordHasher->hashPassword($user, $user->getPlainPassword());
        $user->setPassword($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        $context = SerializationContext::create()
            ->setGroups(['getUser']);

        $jsonuserList = $serializer->serialize($user, 'json', $context);

        $location = $this->generateUrl(
            'utilisateurParId',
            ['id' => $user->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse(
            $jsonuserList, // The serialized data
            Response::HTTP_CREATED, // HTTP status code
            ["Location" => $location], // Headers can be added here if needed
            true
        );
    }

    #[Route('/user/{id}', name: 'utilisateurParId', methods: ['GET'])]
    public function getUserById(
        ?User $user,
        SerializerInterface $serializer
    ): JsonResponse {
        if (!$user) {
            throw new HttpException(JsonResponse::HTTP_NOT_FOUND, 'Utilisateur non trouvé.');
        }

        // Serialize the user entity to JSON
        $context = SerializationContext::create()
            ->setGroups(['getUser']); // Specify the serialization group


        $jsonUser = $serializer->serialize($user, 'json', $context);

        return new JsonResponse(
            $jsonUser, // The serialized data
            Response::HTTP_OK, // HTTP status code
            [], // Headers can be added here if needed
            true // This tells Symfony to not encode the data again
        );
    }

    #[Route('/user/{id}', name: 'supprimerUnUser', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits nécessaires pour supprimer un utilisateur.')]
    public function deleteUser(
        User $user,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Remove the user entity from the database
        $entityManager->remove($user);
        $entityManager->flush();

        // Return a 204 No Content response
        return new JsonResponse(
            ['message' => 'Utilisateur supprimé avec succès.'],
            Response::HTTP_NO_CONTENT
        );
    }

    #[Route('/user/{id}', name: 'mettreAJourUnUser', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits nécessaires pour mettre à jour un utilisateur.')]
    public function updateUser(
        int $id,
        UserRepository $userRepository,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ): JsonResponse {

        // Find the user by ID
        $currentUser = $userRepository->find($id);

        if (!$currentUser) {
            throw new HttpException(JsonResponse::HTTP_NOT_FOUND, 'Utilisateur non trouvé.');
        }

        $content = $request->toArray();

        // Update fields dynamically
        $fieldsMap = [
            'firstName' => 'setFirstName',
            'lastName' => 'setLastName',
            'email' => 'setEmail',
            'city' => 'setCity',
        ];

        foreach ($fieldsMap as $field => $setter) {
            if (!empty($content[$field])) {
                $currentUser->$setter($content[$field]);
            }
        }

        // Handle password separately
        if (!empty($content['password'])) {
            $currentUser->setPlainPassword($content['password']);
            $hashedPassword = $passwordHasher->hashPassword($currentUser, $content['password']);
            $currentUser->setPassword($hashedPassword);
        } 

        $currentUser->getPlainPassword();
        
        $errors = $validator->validate($currentUser);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, implode(', ', $errorMessages));
        }

        // Persist the updated user entity
        $entityManager->persist($currentUser);
        $entityManager->flush();

        return new JsonResponse(
            null, // The serialized data
            Response::HTTP_NO_CONTENT // HTTP status code
        );
    }
}
