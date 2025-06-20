<?php
// src/Controller/GestionUser/FaceAuthController.php

namespace App\Controller\GestionUser;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FaceAuthController extends AbstractController
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    #[Route('/face/register', name: 'face_register')]
    public function registerFace(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'User not authenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        // Expecting the face image uploaded via a file input named 'face_image'
        $file = $request->files->get('face_image');
        if (!$file) {
            return $this->json(['error' => 'No image file provided.'], Response::HTTP_BAD_REQUEST);
        }
        error_log(print_r($request->files->all(), true));

        // Save the uploaded file to a temporary location
        $tempDir = sys_get_temp_dir();
        $tempFile = tempnam($tempDir, 'face_') . '.' . $file->guessExtension();
        $file->move($tempDir, basename($tempFile));

        // Build the path to the Python script in the /scripts folder at the project root.
        $pythonScript = $this->getParameter('kernel.project_dir') . '/scripts/face_recognition_script.py';

        $userId = $user->getId();
        $command = [
            'python',
            $pythonScript,
            'register',
            '--user_id=' . $userId,
            '--image=' . $tempFile
        ];

        $process = new Process($command);
        $process->run();

        // Remove the temporary file
        unlink($tempFile);

        if (!$process->isSuccessful()) {
            $errorOutput = $process->getErrorOutput();
            error_log("Python process error: " . $errorOutput);
            return $this->json(['error' => 'Face registration failed: ' . $errorOutput], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $output = json_decode($process->getOutput(), true);
        if (isset($output['error'])) {
            return $this->json(['error' => $output['error']], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($output);
    }

    #[Route('/face/login', name: 'face_login')]
    public function loginFace(Request $request, ManagerRegistry $doctrine): Response
    {
        // Expecting the face image uploaded via a file input named 'face_image'
        $file = $request->files->get('face_image');
        if (!$file) {
            return $this->json(['error' => 'No image file provided.'], Response::HTTP_BAD_REQUEST);
        }

        // Save the uploaded file to a temporary location
        $tempDir = sys_get_temp_dir();
        $tempFile = tempnam($tempDir, 'face_') . '.' . $file->guessExtension();
        $file->move($tempDir, basename($tempFile));

        // Build the path to the Python script in the /scripts folder at the project root.
        $pythonScript = $this->getParameter('kernel.project_dir') . '/scripts/face_recognition_script.py';

        $command = [
            'python',
            $pythonScript,
            'compare',
            '--image=' . $tempFile
        ];

        $process = new Process($command);
        $process->run();

        // Remove the temporary file
        unlink($tempFile);

        if (!$process->isSuccessful()) {
            return $this->json(['error' => 'Face login failed.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $output = json_decode($process->getOutput(), true);
        if (isset($output['error'])) {
            return $this->json(['error' => $output['error']], Response::HTTP_BAD_REQUEST);
        }

        // Retrieve the recognized user ID from the Python script's output
        $recognizedUserId = $output['user_id'];

        // Retrieve the user entity from your database
        $user = $doctrine->getRepository(User::class)->find($recognizedUserId);
        if (!$user) {
            return $this->json(['error' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        // Programmatically log in the user.
        // For Symfony versions where UsernamePasswordToken expects 3 parameters:
        $firewallName = 'main'; // Adjust if your firewall is named differently
        $token = new UsernamePasswordToken($user, $firewallName, $user->getRoles());
        $this->tokenStorage->setToken($token);
        $request->getSession()->set('_security_' . $firewallName, serialize($token));

        return $this->json([
            'success' => true,
            'message' => 'Face recognized, user logged in.',
            'user_id' => $recognizedUserId
        ]);
    }
}
