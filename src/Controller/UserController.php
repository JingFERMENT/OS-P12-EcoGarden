<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
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

        $jsonuserList = $serializer->serialize($user, 'json', [
            'groups' => ['getUser'], // Specify the serialization group
        ]);

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
        $jsonUser = $serializer->serialize($user, 'json', [
            'groups' => ['getUser'], // Specify the serialization group
        ]);

        return new JsonResponse(
            $jsonUser, // The serialized data
            Response::HTTP_OK, // HTTP status code
            [], // Headers can be added here if needed
            true // This tells Symfony to not encode the data again
        );
    }

    #[Route('/user/{id}', name: 'supprimerUnUser', methods: ['DELETE'])]
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
    public function updateUser(
        Request $request,
        SerializerInterface $serializer,
        User $currentUser,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ): JsonResponse {
        // Deserialize the JSON request content into a User entity
        $user = $serializer->deserialize($request->getContent(), User::class, 'json', [
            'object_to_populate' => $currentUser, // Populate the existing user entity
        ]);

        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, implode(', ', $errorMessages));
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $user->getPlainPassword());
        $user->setPassword($hashedPassword);


        // Persist the updated user entity
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(
            null, // The serialized data
            Response::HTTP_NO_CONTENT // HTTP status code
        );
    }
}
