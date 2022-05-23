<?php
//We use this instead of the session_start we used.
//To avoid using that function in every call of our application
//We create a middleware that will start the session for us.
namespace Salle\PixSalle\Middleware;

use Salle\PixSalle\Cookie;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

final class StartSessionMiddleware
{
    public function __contruct($settings = []){
        $defaults = [
            'lifetime' => '20 minutes',
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => false,
            'samesite' => 'Lax',
            'name' => 'slim_session',
            'autorefresh' => false,
            'handler' => null,
            'ini_settings' => [],
        ];

        $settings = array_merge($defaults, $settings);

        if (is_string($lifetime = $settings['lifetime'])) {
            $settings['lifetime'] = strtotime($lifetime) - time();
        }
        $this->settings = $settings;

        $this->iniSet($settings['ini_settings']);
        // Just override this, to ensure package is working
        if (ini_get('session.gc_maxlifetime') < $settings['lifetime']) {
            $this->iniSet([
                'session.gc_maxlifetime' => $settings['lifetime'] * 2,
            ]);
        }
    }

    public function __invoke(Request $request, RequestHandler $next): Response
    {
        if(!isset($_SESSION)) 
        { 
            session_start(); 
        } 
        return $next->handle($request);
    }

    public function logout(Request $request, Response $response): Response
    {
        $status = session_status();
        if($status == PHP_SESSION_NONE){
            //There is no active session
        }else if($status == PHP_SESSION_DISABLED){
            //Sessions are not available
        }else if($status == PHP_SESSION_ACTIVE){
            //Destroy current and start new one
            session_destroy();
        }
        
        return $response->withHeader('Location','/')->withStatus(302);
    }

    protected function startSession()
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $settings = $this->settings;
        $name = $settings['name'];

        Cookie::setup($settings);

        // Refresh session cookie when "inactive",
        // else PHP won't know we want this to refresh
        if ($settings['autorefresh'] && isset($_COOKIE[$name])) {
            Cookie::set(
                $name,
                $_COOKIE[$name],
                time() + $settings['lifetime'],
                $settings
            );
        }

        session_name($name);

        $handler = $settings['handler'];
        if ($handler) {
            if (!($handler instanceof \SessionHandlerInterface)) {
                $handler = new $handler();
            }
            session_set_save_handler($handler, true);
        }

        session_cache_limiter('');
        session_start();
    }

    protected function iniSet($settings)
    {
        foreach ($settings as $key => $val) {
            if (strpos($key, 'session.') === 0) {
                ini_set($key, $val);
            }
        }
    }
}