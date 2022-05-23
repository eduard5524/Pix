<?php

declare(strict_types=1);

use Salle\PixSalle\Middleware\StartSessionMiddleware;
use Salle\PixSalle\Controller\API\BlogAPIController;
use Salle\PixSalle\Controller\SignUpController;
use Salle\PixSalle\Controller\UserSessionController;
use Salle\PixSalle\Controller\HomeController;
use Salle\PixSalle\Controller\FlashController;
use Salle\PixSalle\Controller\UserProfileController;
use Salle\PixSalle\Controller\WalletController;
use Salle\PixSalle\Controller\MembershipController;
use Salle\PixSalle\Controller\ExploreController;
use Salle\PixSalle\Controller\PortfolioController;
use Salle\PixSalle\Controller\BlogController;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

function addRoutes(App $app): void
{
    # Make available the $_SESSION superglobal variable.
    session_start();

    /** @var TYPE_NAME $app */   
    $app->add(StartSessionMiddleware::class);
    $app->get('/logout', StartSessionMiddleware::class . ':logout')->setName('logout');
    
    # 0. Register and Login
    $app->get('/sign-up', SignUpController::class . ':showSignUpForm')->setName('signUp');
    $app->post('/sign-up', SignUpController::class . ':signUp');
    $app->get('/sign-in', UserSessionController::class . ':showSignInForm')->setName('signIn');
    $app->post('/sign-in', UserSessionController::class . ':signIn');
    
    # 1. Landing page
    $app->get('/', HomeController::class . ':home')->setName('home');
    
    # 2. User Profile
    $app->get('/profile', UserProfileController::class . ':showUserProfile')->setName('userProfile');
    $app->post('/profile', UserProfileController::class . ':userProfile');
    $app->get('/profile/changepassword', UserProfileController::class . ':showChangePassword')->setName('changePassword');
    $app->post('/profile/changepassword', UserProfileController::class . ':changePassword');

    # 3. Wallet
    $app->get('/user/wallet', WalletController::class . ':showWallet')->setName('wallet');
    $app->post('/user/wallet', WalletController::class . ':addToWallet')->setName('addWallet');

    # 4. Memberships
    $app->get('/user/membership', MembershipController::class . ':showMembership')->setName('membership');
    $app->post('/user/membership', MembershipController::class . ':membership');
    
    # 5. Explore
    $app->get('/explore', ExploreController::class . ':explore')->setName('explore');

    # 6. Portfolio
    $app->get('/portfolio', PortfolioController::class . ':showPortfolio')->setName('portfolio');
    $app->post('/portfolio', PortfolioController::class . ':portfolio');
    $app->group('/portfolio', function(RouteCollectorProxy $group) {
        $group->get('/album/{id}', PortfolioController::class . ':getAlbum')->setName('portfolio');
        $group->post('/album/{id}', PortfolioController::class . ':albumPost')->setName('portfolioAlbum');
        $group->delete('/album/{id}', PortfolioController::class . ':deleteAlbumPhoto')->setName('deletePortfolioAlbum');
    });

    # 7. Blogs
    $app->get('/api/blog', BlogController::class . ':getBlogs')->setName('getBlogs');
    $app->post('/api/blog', BlogController::class . ':addBlog')->setName('addBlog');
    $app->group('/api/blog', function(RouteCollectorProxy $group) {
        $group->get('/{id}', BlogController::class . ':getBlog')->setName('getBlog');
        $group->put('/{id}', BlogController::class . ':putBlog')->setName('putBlog');
        $group->delete('/{id}', BlogController::class . ':deleteBlog')->setName('delteBlog');
    });

    # 8. Blog Webpages
    $app->get('/blog', BlogController::class . ':showBlogs')->setName('showBlogs');
    $app->get('/blog/{id}', BlogController::class . ':showSpecificblog')->setName('showSpecificBlog');
}