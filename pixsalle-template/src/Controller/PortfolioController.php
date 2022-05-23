<?php

declare(strict_types=1);

namespace Salle\PixSalle\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Flash\Messages;
use Slim\Views\Twig;
use Slim\Routing\RouteContext;
use Salle\PixSalle\Repository\UserRepository;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

final class PortfolioController
{
    private Twig $twig;
    private Messages $flash;
    private UserRepository $userRepository;

    public function __construct(Twig $twig, Messages $notifications, UserRepository $userRepository)
    {
        $this->twig = $twig;
        $this->flash = $notifications;
        $this->userRepository = $userRepository;
    }

    public function showPortfolio(Request $request, Response $response): Response
    {
        # A portfolio is a compilation of materials that exemplifies your beliefs, skills, qualifications, education, 
        # training and experiences.
        if(isset($_SESSION['user_id'])){
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();

            $messages = $this->flash->getMessages();
            $notifications = $messages['notifications'] ?? [];
            
            $portfolio['created'] = false;
            $portfolio['portfolio-title'] = $this->userRepository->getPortfolioTitle();
            $user = $this->userRepository->getUser();

            if(!empty($portfolio['portfolio-title'])){
               $portfolio['created'] = true;
            }

            if($portfolio['created'] == true){
                $albums = $this->userRepository->getAlbumsById();
            }else{
                $albums = false;
            }

            return $this->twig->render($response, 'portfolio.twig', 
            [
                'notifications' => $notifications,
                'session' => $_SESSION,
                'formAction' => $routeParser->urlFor('portfolio'),
                'albums' => $albums,
                'portfolio' => $portfolio,
                'membership' => $user->membership
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
    public function albumPost(Request $request, Response $response, array $args): Response {
        $messages = $this->flash->getMessages();
        $notifications = $messages['notifications'] ?? [];
        
        if(isset($_SESSION['user_id'])){
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            $data = $request->getParsedBody();
            
            if(strcmp($data['upload'], 'Upload Photo') == 0){
                # Upload new Album Photo.
                $this->userRepository->uploadAlbumPhoto($data['choose-file'], $args['id']);
            }else if(strcmp($data['upload'], 'Generate QR Barcode') == 0){
                # Generate Album QR Barcode.
                $code = 'http://localhost:8030/portfolio/album/' . $args['id'];
                $client = new \GuzzleHttp\Client();
                $response_http_request = $client->request('POST', 'http://barcode/barcodegenerator', 
                    [   'headers' => 
                        [
                            'Cache-control' => 'no-cache',
                            'Content-Type' => 'application/json',
                            'Content-Length' => 'calculated',
                            'Host' => 'calculated',
                            'Accept' => 'image/png',
                            'Accept-Encoding' => 'gzip, deflate, br',
                            'Connection' => 'keep-alive'
                        ],
                         
                           'json' => 
                            [
                                'symbology' => 'QRCode',
                                'code' => $code
                            ]
                        
                    ]);
                //$response_http_request->getStatusCode();
                //echo $response_http_request->getHeaderLine('content-type'); // 'application/json; charset=utf8'
                $response_http_request_body = $response_http_request->getBody(); // '{"id": 1420053, "name": "guzzle", ...}'
                //var_dump($response_http_request_body);
                //echo $response_http_request_body;
                # The filename of the picture will be the following.
                $filename = 'assets/album_pictures/album_id_' . $args['id'] . '.png';
                file_put_contents($filename, $response_http_request_body);
                $filename = '/' . $filename;
                $this->userRepository->uploadQRCodeByID($filename, $args['id']);                
            }
            $photos = $this->userRepository->getAlbumPhotosById($args['id']);
            $album = $this->userRepository->getAlbum($args['id']);

            return $this->twig->render($response, 'album.twig', 
            [
                'notifications' => $notifications,
                'session' => $_SESSION,
                'photos' => $photos,
                'album' => $album,
                'album_name' => $album->name,
                'qr_code' => $album->qr_code
            ]);
        }else{
            $this->flash->addMessage('notifications', 'You need to login to acces this feature.');
            return $response->withHeader('Location','/sign-in', 
            [
              'notifications' => $notifications,
            ])
            ->withStatus(302);
        }        
    }
    public function portfolio(Request $request, Response $response): Response
    {
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        if(isset($_SESSION['user_id'])){
            $messages = $this->flash->getMessages();
            $notifications = $messages['notifications'] ?? [];
            $data = $request->getParsedBody();

            $portfolio['created'] = false;
            $user = $this->userRepository->getUser();
            if($user->membership == 'Active'){
                if($user->portfolio == ""){
                    # Create a new Portfolio.
                    $albums = $this->userRepository->getAlbumsById();
                    $this->userRepository->updateTitle($data['portfolio-title']);
                    $portfolio['portfolio-title'] = $data['portfolio-title'];
                    if(!empty($portfolio['portfolio-title'])){
                        $portfolio['created'] = true;
                    }
                    return $this->twig->render($response, 'portfolio.twig', 
                    [
                        'notifications' => $notifications,
                        'session' => $_SESSION,
                        'portfolio' => $portfolio,
                        'albums' => $albums,
                        'membership'  => $user->membership,
                    ]);
                }else{
                    # Create a new Album.
                    if(!empty($data['album-title'])){
                        if($this->userRepository->updateWalletAmount() == true){
                            $this->userRepository->createNewAlbum($data['album-title']);
                        }else{
                            $this->flash->addMessage('notifications', 'You cannot create an Album due to your wallet ammount is insuficient.');
                        }      
                        $albums = $this->userRepository->getAlbumsById();
                        $portfolio['created'] = true;
                        $portfolio['portfolio-title'] = $this->userRepository->getPortfolioTitle();
                        return $this->twig->render($response, 'portfolio.twig', 
                        [
                            'notifications' => $notifications,
                            'session' => $_SESSION,
                            'portfolio' => $portfolio,
                            'albums' => $albums,
                            'membership'  => $user->membership,
                            'formAction' => $routeParser->urlFor('portfolio'),
                        ]);          
                    }else if($data['upload'] == 'Delete Album'){  
                        # Delete an Album. To delete an Album we will have to delete all the pictures related as well.
                        $this->userRepository->deleteAlbumByID($data['album-to-delete']);

                        $albums = $this->userRepository->getAlbumsById();
                        $portfolio['created'] = true;
                        $portfolio['portfolio-title'] = $this->userRepository->getPortfolioTitle();
                        return $this->twig->render($response, 'portfolio.twig', 
                        [
                            'notifications' => $notifications,
                            'session' => $_SESSION,
                            'portfolio' => $portfolio,
                            'albums' => $albums,
                            'membership'  => $user->membership,
                        ]);
                    }else{
                        $albums = $this->userRepository->getAlbumsById();
                        $portfolio['created'] = true;
                        $portfolio['portfolio-title'] = $this->userRepository->getPortfolioTitle();
                        return $this->twig->render($response, 'portfolio.twig', 
                        [
                            'notifications' => $notifications,
                            'session' => $_SESSION,
                            'portfolio' => $portfolio,
                            'albums' => $albums,
                            'membership'  => $user->membership,
                        ]);
                    }

                }


            }else{
                $albums = $this->userRepository->getAlbumsById();
                $this->flash->addMessage('notifications', 'You must upgrade to Active membership to create a new portfolio');
                return $this->twig->render($response, 'portfolio.twig', 
                [
                    'notifications' => $notifications,
                    'session' => $_SESSION,
                    'portfolio' => $portfolio,
                    'albums' => $albums,
                    'membership'  => $user->membership,
                ]);
            }
        }else{
            return $response->withHeader('Location','/sign-in', 
            [
              'notifications' => $notifications,
            ])
            ->withStatus(302);     
        }
    }
    public function getAlbum(Request $request, Response $response, array $args): Response
    {
        if(isset($_SESSION['user_id'])){
            # Get the pictures of the Album.
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            $messages = $this->flash->getMessages();
            $notifications = $messages['notifications'] ?? [];
            $album = $this->userRepository->getAlbum($args['id']);
            $photos = $this->userRepository->getAlbumPhotosById($args['id']);
            $id = $_SESSION['user_id'];

            return $this->twig->render($response, 'album.twig', 
            [
                'notifications' => $notifications,
                'session' => $_SESSION,
                'qr_code' => $album->qr_code,
                'album_name' => $album->name,
                'album_id' => $album->id,
                'photos' => $photos,
                'formActionAlbum' => $routeParser->urlFor('portfolioAlbum', ['id' => $args['id']]),
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
    public function deleteAlbumPhoto(Request $request, Response $response, $args): Response
    {
        $this->userRepository->deleteAlbum($args['id']);
    }
}