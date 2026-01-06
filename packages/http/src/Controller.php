<?php

declare(strict_types=1);

namespace Forjix\Http;

use Forjix\Core\Application;
use Forjix\View\Engine;

abstract class Controller
{
    /**
     * Return a view response
     */
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        $app = Application::getInstance();

        if ($app && $app->has(Engine::class)) {
            $engine = $app->make(Engine::class);
        } else {
            // Fallback: create engine with default paths
            $basePath = defined('BASE_PATH') ? BASE_PATH : (function_exists('base_path') ? base_path() : getcwd());
            $engine = new Engine(
                [$basePath . '/resources/views'],
                $basePath . '/storage/framework/views'
            );
        }

        $content = $engine->render($template, $data);

        return new Response($content, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * Return a JSON response
     */
    protected function json(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * Return a redirect response
     */
    protected function redirect(string $url, int $status = 302): Response
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * Return a response with the given content
     */
    protected function response(string $content = '', int $status = 200, array $headers = []): Response
    {
        return new Response($content, $status, $headers);
    }
}
