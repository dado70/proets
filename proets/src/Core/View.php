<?php
declare(strict_types=1);

namespace ProETS\Core;

class View
{
    /**
     * Renderizza un template dentro il layout base.
     * $template = path relativo a templates/ senza .php
     */
    public static function render(string $template, array $data = [], string $layout = 'layout/base'): void
    {
        extract($data, EXTR_SKIP);

        // Cattura il contenuto del template interno
        ob_start();
        $templateFile = PROETS_ROOT . "/templates/{$template}.php";
        if (!file_exists($templateFile)) {
            throw new \RuntimeException("Template non trovato: {$template}");
        }
        require $templateFile;
        $content = ob_get_clean();

        // Renderizza il layout con il contenuto
        $layoutFile = PROETS_ROOT . "/templates/{$layout}.php";
        if (!file_exists($layoutFile)) {
            echo $content;
            return;
        }
        require $layoutFile;
    }

    /**
     * Renderizza template senza layout (es. per login, pagine pubbliche)
     */
    public static function renderRaw(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require PROETS_ROOT . "/templates/{$template}.php";
    }

    /**
     * Partial (include parziale senza layout)
     */
    public static function partial(string $partial, array $data = []): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require PROETS_ROOT . "/templates/partials/{$partial}.php";
        return ob_get_clean();
    }

    public static function e(mixed $val): string
    {
        return htmlspecialchars((string)$val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function money(float $amount, bool $showSign = false): string
    {
        $str = '€ ' . number_format(abs($amount), 2, ',', '.');
        if ($showSign && $amount > 0) $str = '+' . $str;
        if ($showSign && $amount < 0) $str = '-' . $str;
        return $str;
    }

    public static function date(string $date): string
    {
        return $date ? date('d/m/Y', strtotime($date)) : '';
    }

    public static function datetime(string $dt): string
    {
        return $dt ? date('d/m/Y H:i', strtotime($dt)) : '';
    }
}
