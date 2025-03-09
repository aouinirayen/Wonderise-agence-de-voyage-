<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Twilio\Rest\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    private $entityManager;
    private $params;
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager, 
        ParameterBagInterface $params,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->params = $params;
        $this->logger = $logger;
    }

    #[Route('/request', name: 'reset_password_request')]
    public function request(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $recaptchaResponse = $request->request->get('g-recaptcha-response');
            $url = 'https://www.google.com/recaptcha/api/siteverify';
            $data = [
                'secret' => $this->params->get('google_recaptcha_secret_key'),
                'response' => $recaptchaResponse
            ];

            $options = [
                'http' => [
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($data)
                ]
            ];

            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            $resultJson = json_decode($result);

            if (!$resultJson->success || $resultJson->score < 0.5) {
                $this->addFlash('danger', 'Invalid captcha. Please try again.');
                return $this->redirectToRoute('reset_password_request');
            }

            $email = $request->request->get('email');
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $this->addFlash('danger', 'No user found with this email.');
                return $this->redirectToRoute('reset_password_request');
            }

            if (!$user->getNumTel()) {
                $this->addFlash('danger', 'No phone number associated with this account. Please contact support.');
                return $this->redirectToRoute('reset_password_request');
            }

            // Generate a random 6-digit verification code
            $verificationCode = random_int(100000, 999999);
            $user->setVerificationCode((string)$verificationCode);
            $this->entityManager->flush();

            try {
                $this->logger->info('Twilio configuration', [
                    'sid' => $this->params->get('TWILIO_ACCOUNT_SID'),
                    'phone_from' => $this->params->get('TWILIO_PHONE_NUMBER'),
                    'phone_to' => $user->getNumTel()
                ]);

                $this->sendSms($user->getNumTel(), $verificationCode);
                $this->addFlash('success', 'A verification code has been sent to your phone.');
                return $this->redirectToRoute('reset_password_verify', ['id' => $user->getId()]);
            } catch (\Exception $e) {
                $this->logger->error('Twilio error: ' . $e->getMessage(), [
                    'exception' => $e,
                    'trace' => $e->getTraceAsString()
                ]);
                $this->addFlash('danger', 'Error sending SMS: ' . $e->getMessage());
                return $this->redirectToRoute('reset_password_request');
            }
        }

        return $this->render('security/reset_password_request.html.twig', [
            'site_key' => $this->params->get('google_recaptcha_site_key')
        ]);
    }

    #[Route('/verify/{id}', name: 'reset_password_verify')]
    public function verify(Request $request, User $user): Response
    {
        if ($request->isMethod('POST')) {
            $enteredCode = $request->request->get('verification_code');

            if ($enteredCode == $user->getVerificationCode()) {
                return $this->redirectToRoute('reset_password_reset', ['id' => $user->getId()]);
            } else {
                $this->addFlash('danger', 'Invalid verification code.');
            }
        }

        return $this->render('security/reset_password_verify.html.twig', ['user' => $user]);
    }

    #[Route('/reset/{id}', name: 'reset_password_reset')]
    public function reset(
        int $id,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            if (empty($newPassword) || empty($confirmPassword)) {
                $this->addFlash('danger', 'Password fields cannot be empty.');
                return $this->redirectToRoute('reset_password_reset', ['id' => $id]);
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('danger', 'Passwords do not match.');
                return $this->redirectToRoute('reset_password_reset', ['id' => $id]);
            }

            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            // Clear the verification code after successful reset
            $user->setVerificationCode(null);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Password reset successfully.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password_reset.html.twig', [
            'userId' => $id,
        ]);
    }

    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Supprimer tous les caractères non numériques sauf le +
        $number = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // Si le numéro commence déjà par +, le laisser tel quel
        if (str_starts_with($number, '+')) {
            return $number;
        }
        
        // Supprimer tous les + restants pour éviter les doublons
        $number = str_replace('+', '', $number);
        
        // Si le numéro commence par 216, ajouter juste le +
        if (str_starts_with($number, '216')) {
            return '+' . $number;
        }
        
        // Si le numéro commence par 0, le remplacer par 216
        if (str_starts_with($number, '0')) {
            return '+216' . substr($number, 1);
        }
        
        // Dans les autres cas, ajouter +216
        return '+216' . $number;
    }

    private function sendSms(string $phoneNumber, string $verificationCode): void
    {
        // Formater le numéro de téléphone
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        
        $twilioSid = $this->params->get('TWILIO_ACCOUNT_SID');
        $twilioToken = $this->params->get('TWILIO_AUTH_TOKEN');
        $twilioPhoneNumber = $this->params->get('TWILIO_PHONE_NUMBER');

        $this->logger->info('Attempting to send SMS', [
            'to' => $phoneNumber,
            'from' => $twilioPhoneNumber
        ]);

        $client = new \Twilio\Rest\Client($twilioSid, $twilioToken);
        
        $message = $client->messages->create(
            $phoneNumber,
            [
                'from' => $twilioPhoneNumber,
                'body' => "Your verification code is: $verificationCode"
            ]
        );

        $this->logger->info('SMS sent successfully', [
            'message_sid' => $message->sid
        ]);
    }
}
