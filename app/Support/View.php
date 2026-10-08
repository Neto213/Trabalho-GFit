<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'main'): void
    {
        $viewFile = __DIR__ . '/../Views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewFile)) {
            throw new RuntimeException("View [{$view}] não encontrada no caminho: {$viewFile}");
        }

        // Variáveis disponíveis na view
        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutFile = __DIR__ . '/../Views/layouts/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            throw new RuntimeException("Layout [{$layout}] não encontrado em: {$layoutFile}");
        }

        require $layoutFile;
    }
}
