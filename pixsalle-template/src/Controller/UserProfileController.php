<?php

declare(strict_types=1);

namespace Salle\PixSalle\Controller;

use Salle\PixSalle\Repository\UserRepository;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Flash\Messages;
use Slim\Views\Twig;
use Slim\Routing\RouteContext;
use Salle\PixSalle\Service\ValidatorService;
use Psr\Http\Message\UploadedFileInterface;
use DI\ContainerBuilder;

final class UserProfileController
{
    private Twig $twig;
    private Messages $flash;
    private UserRepository $userRepository;
    private ValidatorService $validator;

    public function __construct(Twig $twig, Messages $flash, UserRepository $userRepository)
    {
        $this->twig = $twig;
        $this->flash = $flash;
        $this->userRepository = $userRepository;
        $this->validator = new ValidatorService();
    }

    public function ShowUserProfile(Request $request, Response $response): Response
    {
        if(isset($_SESSION['user_id'])){
          $messages = $this->flash->getMessages();
          $notifications = $messages['notifications'] ?? [];
          $pathUserProfilePicture = $this->userRepository->getUser()->userProfile;

          return $this->twig->render($response, 'profile.twig', [
              'notifications' => $notifications,
              'session'   => $_SESSION,
              'pathUserProfilePicture' => $pathUserProfilePicture,
          ]);
        }else{
          $this->flash->addMessage('notifications', 'You need to login to acces this feature.');
          $messages = $this->flash->getMessages();
          $notifications = $messages['notifications'] ?? [];

          return $response->withHeader('Location','/sign-in', 
          [
            'notifications' => $notifications,
          ])
          ->withStatus(302);
        }
    }
    function moveUploadedFile(string $directory, UploadedFileInterface $uploadedFile)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    
        // see http://php.net/manual/en/function.random-bytes.php
        $basename = bin2hex(random_bytes(8));
        $filename = sprintf('%s.%0.8s', $basename, $extension);
    
        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
    
        return $filename;
    }
    public function isAllowedFormat(string $filename){
        $allowed = true;

        return $allowed;
    }
    public function userProfile(Request $request, Response $response): Response {
        $messages = $this->flash->getMessages();
        $notifications = $messages['notifications'] ?? [];
        $session_user_id = $_SESSION['user_id'];

        $data = $request->getParsedBody();
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        $user = $this->userRepository->getUser();
        $filename = $user->userProfile;
        
        if(array_key_exists('save-profile-picture', $data)){
          # Update the User Profile Picture.
          $containerBuilder = new ContainerBuilder();
          $container = $containerBuilder->build();
          $container->set('upload_directory', __DIR__ . '/../../public/assets/uploads');
          $directory = $container->get('upload_directory');
          $uploadedFiles = $request->getUploadedFiles();
          $uploadedFile = $uploadedFiles['user-profile-image'];
          $errors = false;

          # The file as maximum can have a size of 1MB.
          $size = $uploadedFile->getSize();
          if($size > 1000000){
            $errors = true;
            $this->flash->addMessage('notifications', 'The file can have as maximum a size of 1MB.');
          }
          $ext = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
          if($ext == 'ico' || $ext == 'jpg' || $ext == 'jpeg'){
              $errors = true;
              $this->flash->addMessage('notifications', 'The file extension is not allowed.');
          }
          if($errors == false){
            $filename = $this->moveUploadedFile($directory, $uploadedFile);
            $filename = '/assets/uploads/' . $filename;
            $this->userRepository->updateProfile($data['username'], $data['phone'], $_SESSION['user_id'], $filename);
          }
        }else{
          # Update the User Profile Data.
          $this->userRepository->updateProfile($data['username'], $data['phone'], $_SESSION['user_id'], $filename);

          if($this->userRepository->verifyPhoneNumber($data['phone']) == true){
            $this->flash->addMessage('notifications', 'The phone number is not following the Spanish numbering plan.');
          }
        }

        return $response->withHeader('Location','/profile', 
        [
            'notifications' => $notifications,
            'session'   => $_SESSION,
            'formAction' => $routeParser->urlFor('userProfile'),
            'filename' => $filename,
        ])
        ->withStatus(302);
    }

    public function showChangePassword(Request $request, Response $response): Response
    {
      if(isset($_SESSION['user_id'])){
        $messages = $this->flash->getMessages();
        $notifications = $messages['notifications'] ?? [];

        $data = $request->getParsedBody();
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        return $this->twig->render($response, 'changePassword.twig', 
        [
          'notifications' => $notifications,
          'session'   => $_SESSION,
        ]);

      }else{
        $messages = $this->flash->getMessages();
        $notifications = $messages['notifications'] ?? [];
        $this->flash->addMessage('notifications', 'You need to login to acces this feature.');
        return $response->withHeader('Location','/sign-in', 
        [
          'notifications' => $notifications,
        ])
        ->withStatus(302);
      }  
    }

    public function changePassword(Request $request, Response $response): Response 
    {    
        if(isset($_SESSION['user_id'])){
          $messages = $this->flash->getMessages();
          $notifications = $messages['notifications'] ?? [];
          $data = $request->getParsedBody();
          $routeParser = RouteContext::fromRequest($request)->getRouteParser();
          $user =  (array) $this->userRepository->getPasswordByEmail();
          $errors = FALSE;

          $validator = $this->validator->validatePassword($data['new-password-1']);
          if($validator){
              $this->flash->addMessage('notifications', $validator);
              $errors = TRUE;
          }
  
          $hash = md5($data['old-password']);
          
          if($hash != $user['password']){
              $this->flash->addMessage('notifications', 'The old password inserted is incorrect.');
              $errors = TRUE;
          }
          if($data['new-password-1'] != $data['new-password-2']){  
              $this->flash->addMessage('notifications', 'The passwords are not the same.');
              $errors = TRUE;
          }
          if($errors == FALSE){
              $hash = md5($data['new-password-1']);
              $this->userRepository->updatePassword($hash, $_SESSION['user_id']);
              $this->flash->addMessage('notifications', 'The password has been successfuly updated.');
          }        

          return $this->twig->render($response, 'changePassword.twig', 
          [
            'notifications' => $notifications,
            'session'   => $_SESSION,
          ]);
        }else{
          $messages = $this->flash->getMessages();
          $notifications = $messages['notifications'] ?? [];
          $this->flash->addMessage('notifications', 'You need to login to acces this feature.');
          return $response->withHeader('Location','/sign-in', 
          [
            'notifications' => $notifications,
          ])
          ->withStatus(302);
        }
    }
}