<?php
declare(strict_types=1);

namespace Got;

/** Matches "GET /admin/enquiries/{id}" style routes to handlers. */
final class Router
{
    /** @var array<int, array{0: string, 1: string, 2: array|\Closure}> */
    private array $routes = [];

    public function get(string $pattern, array|\Closure $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, array|\Closure $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function add(string $method, string $pattern, array|\Closure $handler): void
    {
        $regex = preg_replace_callback('/\{(\w+)\}/', static function ($m) {
            return $m[1] === 'id' ? '(?P<id>\d+)' : '(?P<' . $m[1] . '>[a-z0-9\-_.]+)';
        }, $pattern);
        $this->routes[] = [$method, '#^' . $regex . '$#', $handler];
    }

    public function dispatch(string $method, string $path): void
    {
        $allowed = [];
        foreach ($this->routes as [$routeMethod, $regex, $handler]) {
            if (!preg_match($regex, $path, $m)) {
                continue;
            }
            $verb = $method === 'HEAD' ? 'GET' : $method;
            if ($routeMethod !== $verb) {
                $allowed[] = $routeMethod;
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            if (isset($params['id'])) {
                $params['id'] = (int) $params['id'];
            }
            if (is_array($handler)) {
                [$class, $action] = $handler;
                $class::$action(...$params);
            } else {
                $handler(...$params);
            }
            return;
        }
        if ($allowed) {
            http_response_code(405);
            header('Allow: ' . implode(', ', array_unique($allowed)));
            echo 'Method not allowed';
            return;
        }
        Controllers\SiteController::notFound();
    }
}
