<?php

declare(strict_types=1);

namespace Salle\PixSalle\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Flash\Messages;
use Slim\Views\Twig;
use Salle\PixSalle\Repository\UserRepository;

final class BlogController
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
    public function showBlogs(Request $request, Response $response): Response {
        if(isset($_SESSION['user_id'])){
            $blog['id'] = false;
            $messages = $this->flash->getMessages();
            $notifications = $messages['notifications'] ?? [];
            $blogs = $this->userRepository->getBlogs();

            return $this->twig->render($response, 'blogs.twig', 
            [
                'notifications' => $notifications,
                'session' => $_SESSION,
                'blogs' => $blogs,
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
    public function showSpecificBlog(Request $request, Response $response, array $args): Response {
        # For each blog entry, you must at least show the title, content, and the author. The content may have multiple paragraphs.
        if(isset($_SESSION['user_id'])){
            $messages = $this->flash->getMessages();
            $notifications = $messages['notifications'] ?? [];
            $data = $request->getParsedBody();
            $blog = $this->userRepository->getBlogByID($args['id']);
            $blog['id'] = true;

            return $this->twig->render($response, 'blogs.twig', 
            [
                'notifications' => $notifications,
                'session' => $_SESSION,
                'album_id' => $args['id'],
                'blog' => $blog,
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
    public function blog(Request $request, Response $response): Response
    { 
        if(isset($_SESSION['user_id'])){
            $messages = $this->flash->getMessages();
            $notifications = $messages['notifications'] ?? [];
            $blog['id'] = false;
            return $this->twig->render($response, 'blogs.twig', 
            [
                'notifications' => $notifications,
                'session' => $_SESSION,
                'blog'  => $blog,
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
    public function getBlogs(Request $request, Response $response): Response
    {
        if(isset($_SESSION['user_id'])){
            # Return all the blog entries in JSON.
            $data = $this->userRepository->getAllBlogs();
            $response->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(200);
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
    public function addBlog(Request $request, Response $response): Response
    {
        if(isset($_SESSION['user_id'])){
            $data = (array) $request->getParsedBody();
        
            if(isset($data['title']) && isset($data['content']) && isset($data['userID'])) {
                $data_response = array('id' => 0, 'title' => $data['title'], 'content' => $data['content'], 'userID' => $data['userID']);
                $data_response['id'] = $this->userRepository->insertBlog($data_response);
                $response->getBody()->write(json_encode($data_response));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            }else{
                $this->flash->addMessage('notifications', 'title and/or content and/or userID key missing.');
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);     
            }
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
    public function getBlog(Request $request, Response $response, $args): Response 
    {
        if(isset($_SESSION['user_id'])){
            $blog = $this->userRepository->getBlogByID($args['id']);

            if(isset($blog['id'])) {
                $response->getBody()->write(json_encode($blog));
                $response->withHeader('Content-Type', 'application/json');
                return $response->withStatus(200);
            }else{
                $this->flash->addMessage('notifications', 'Blog entry with id {id} does not exist.');
                $response->getBody()->write(json_encode($blog));
                $response->withHeader('Content-Type', 'application/json');
                return $response->withStatus(404);
            }
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
    public function putBlog(Request $request, Response $response, $args): Response
    {
        if(isset($_SESSION['user_id'])){
            $data = (array) $request->getParsedBody();
            $data_response = array('id' => 0, 'title' => $data['title'], 'content' => $data['content'], 'userID' => $_SESSION['user_id']);
            $data_response['id'] = $args['id'];
            $result = $this->userRepository->putBlog($data_response);

            if(isset($data_response['title']) && isset($data_response['content'])) {
                if(($result > "0")){
                    $response->getBody()->write(json_encode($data_response));
                    $response->withHeader('Content-Type', 'application/json');
                    return $response->withStatus(200);
                }else{
                    $this->flash->addMessage('notifications', 'Blog entry with id {id} does not exist.');
                    $response->withHeader('Content-Type', 'application/json');
                    return $response->withStatus(404);
                } 
            }else{
                $this->flash->addMessage('notifications', 'The title and/or content cannot be empty.');
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
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
    public function deleteBlog(Request $request, Response $response, $args): Response
    {
        if(isset($_SESSION['user_id'])){
            $blog = $this->userRepository->getBlogByID($args['id']);
            $result = $this->userRepository->deleteBlogByID($args['id']);
            if($result > 0){    
                $response->getBody()->write(json_encode($blog));
                $response->withHeader('Content-Type', 'application/json');
                return $response->withStatus(200);
            }else{
                $this->flash->addMessage('notifications', 'Blog entry with id {id} does not exist.');
                return $response->withStatus(404);
            }
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
}