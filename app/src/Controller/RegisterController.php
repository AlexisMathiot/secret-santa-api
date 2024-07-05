<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegisterController extends AbstractController
{
    #[Route('/api/signin', name: "api_signin", methods: ['POST'])]
    public function createUser(Request                     $request,
                               SerializerInterface         $serializer,
                               UserPasswordHasherInterface $passwordHasher,
                               EntityManagerInterface      $em,
                               ValidatorInterface          $validator
    ): JsonResponse
    {

        /** @var User $user */
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');
        $errors = $validator->validate($user);

        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
        $user->setRoles(["ROLE_USER"]);

        $em->persist($user);
        $em->flush();

        $jsonUser = $serializer->serialize($user, 'json');

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
    }


    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/api/send-email-confirmation-inscription/{email}', name: "api_send_confirmation_inscription_email", methods: ['GET'])]
    public function sendConfirmationInsriptionEmail(
        MailerService  $mail,
        string         $email,
        UserRepository $userRepository
    ): JsonResponse
    {
        $user = $userRepository->findOneBy(['email' => $email]);
        if ($user !== null) {
            $baseurl = $this->getParameter('app.front_base_url');
            $url = $baseurl . '/login';
            $context = compact('user', 'url');
            $mail->send(
                'no-reply@domain.fr',
                $user->getEmail(),
                'Bienvenue sur SecretSanta',
                'confirmation_inscription',
                $context
            );
            return new JsonResponse("Invitation envoyé", Response::HTTP_OK, [], true);
        }

        return new JsonResponse("Email non valide", Response::HTTP_NOT_FOUND, [], true);
    }
}
