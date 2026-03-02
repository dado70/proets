<?php
declare(strict_types=1);

namespace ProETS\Core;

abstract class Controller
{
    protected function view(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $templateFile = PROETS_ROOT . "/templates/{$template}.php";
        if (!file_exists($templateFile)) {
            throw new \RuntimeException("Template non trovato: {$template}");
        }
        require $templateFile;
    }

    protected function json(mixed $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    protected function redirect(string $url, int $code = 302): never
    {
        Router::redirect($url, $code);
    }

    protected function back(): never
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($ref);
    }

    protected function csrfCheck(): void
    {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Session::verifyCsrf($token)) {
            Router::abort(403, 'Token CSRF non valido.');
        }
    }

    protected function requireAuth(string $permission = 'read'): void
    {
        Auth::require($permission);
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function inputInt(string $key, int $default = 0): int
    {
        return (int)($this->input($key, $default));
    }

    protected function inputFloat(string $key, float $default = 0.0): float
    {
        return (float)str_replace(',', '.', (string)$this->input($key, $default));
    }

    protected function inputString(string $key, string $default = ''): string
    {
        return trim((string)($this->input($key, $default)));
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function company(): ?array
    {
        return Auth::company();
    }

    protected function companyId(): int
    {
        return Auth::companyId() ?? 0;
    }

    protected function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }

    protected function e(mixed $val): string
    {
        return htmlspecialchars((string)$val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    protected function formatMoney(float $amount): string
    {
        return number_format($amount, 2, ',', '.');
    }

    protected function formatDate(string $date): string
    {
        return $date ? date('d/m/Y', strtotime($date)) : '';
    }
}
