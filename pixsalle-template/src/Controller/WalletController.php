<?php

declare(strict_types=1);

namespace Salle\PixSalle\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Flash\Messages;
use Slim\Views\Twig;
use Slim\Routing\RouteContext;
use Salle\PixSalle\Repository\UserRepository;

final class WalletController
{
    private Twig $twig;
    private Messages $flash;
    private UserRepository $userRepository;

    public function __construct(Twig $twig, Messages $flash, UserRepository $userRepository)
    {
        $this->twig = $twig;
        $this->flash = $flash;
        $this->userRepository = $userRepository;
    }

    public function showWallet(Request $request, Response $response): Response
    {
        if(isset($_SESSION['user_id'])){
            $user = $this->userRepository->getUserByEmail($_SESSION['email']);
            $current_balance = $user->balance;
            $messages = $this->flash->getMessages();
            $notifications = $messages['notifications'] ?? [];
    
            return $this->twig->render($response, 'wallet.twig', 
            [
              'notifications' => $notifications,
              'session'   => $_SESSION,
              'current_balance' => $current_balance,
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

    public function verifyAmount(string $message)
    {
        $errors = false;
        
        if($message < '0'){
            $errors = true;
        }
        return $errors;
    }

    public function addToWallet(Request $request, Response $response): Response
    {
        if(isset($_SESSION['user_id'])){
            $messages = $this->flash->getMessages();
            $notifications = $messages['notifications'] ?? [];

            $data = $request->getParsedBody();
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            if($data['amount'] > 32760){
                $this->flash->addMessage('notifications', 'The amount introduced is too big.');
            }else{
                if($this->verifyAmount($data['amount']) == false){
                    $this->userRepository->updateBalance($_SESSION['email'], $data['amount']);
                }
            }
            $user = $this->userRepository->getUserByEmail($_SESSION['email']);
            return $this->twig->render($response, 'wallet.twig', 
            [
                'notifications' => $notifications,
                'session' => $_SESSION,
                'current_balance' => $user->balance,
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