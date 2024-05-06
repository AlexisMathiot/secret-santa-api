<?php

namespace App\Controller;

use App\Form\ResetPasswordType;
use App\Repository\UserRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class SecurityController extends AbstractController
{
    /**
     * @throws TransportExceptionInterface
     */
    #[Route(path: '/forgot-password', name: 'forgot_password')]
    public function forgotPassword(
        Request                 $request,
        UserRepository          $userRepository,
        TokenGeneratorInterface $tokenGenerator,
        EntityManagerInterface  $entityManager,
        MailerService           $mail
    ): JsonResponse
    {
        $email = json_decode($request->getContent(), true)['email'];
        $user = $userRepository->findOneBy(['email' => $email]);

        if ($user !== null) {
            $token = $tokenGenerator->generateToken();
            $user->setResetToken($token);
            $entityManager->persist($user);
            $entityManager->flush();

            $url = $this->generateUrl('reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

            $context = compact('url', 'user');

            $mail->send(
                'no-reply@domain.fr',
                $user->getEmail(),
                'Réinitialisation de mot de passe',
                'reset_password',
                $context
            );

            return new JsonResponse('Mail de changement de mot de passe envoyé', Response::HTTP_OK, [], true);
        }

        return new JsonResponse('Un problème est survenu', Response::HTTP_BAD_REQUEST, [], true);
    }

    #[Route(path: '/forgot-password/{token}', name: 'reset_password')]
    public function resetPassword(
        string                      $token,
        Request                     $request,
        UserRepository              $userRepository,
        EntityManagerInterface      $entityManager,
        UserPasswordHasherInterface $userPasswordHasher,
    ): Response
    {
        $user = $userRepository->findOneBy(['resetToken' => $token]);
        if ($user !== null) {
            $form = $this->createForm(ResetPasswordType::class);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $user->setResetToken('');
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('password')->getData()
                    )
                );
                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Mot de passe changé avec succès');
                $this->redirectToRoute('app_login');
            }

            return $this->render('security/reset_password.html.twig', [
                'passForm' => $form->createView()
            ]);
        }

        $this->addFlash('danger', 'Jeton invalide');
        return $this->redirectToRoute('app_home');
    }
}
