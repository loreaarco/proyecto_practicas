<?php

/**
 * Controller - Clase base para todos los controladores
 *
 * Proporciona render de vistas, redirección y helpers comunes.
 */
abstract class Controller
{
    /**
     * Renderiza una vista pasándole variables.
     *
     * @param string $view  Ruta relativa a /views (sin .php), ej: 'clientes/index'
     * @param array  $data  Variables que estarán disponibles en la vista
     */
    protected function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $viewFile = BASE_PATH . '/views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new RuntimeException("Vista no encontrada: {$view}");
        }

        // Carga el layout con la vista incrustada
        $content = $viewFile; // La variable $content será incluida por el layout
        include BASE_PATH . '/views/layouts/app.php';
    }

    /**
     * Renderiza solo la vista sin layout (para fragmentos AJAX, por ejemplo).
     */
    protected function renderPartial(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        include BASE_PATH . '/views/' . $view . '.php';
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . $path);
        exit;
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Obtiene y sanitiza un campo POST.
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        return isset($_POST[$key]) ? $this->sanitize($_POST[$key]) : $default;
    }

    /**
     * Obtiene y sanitiza un campo GET.
     */
    protected function query(string $key, mixed $default = null): mixed
    {
        return isset($_GET[$key]) ? $this->sanitize($_GET[$key]) : $default;
    }

    private function sanitize(mixed $value): mixed
    {
        if (is_string($value)) {
            return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        return $value;
    }

    /**
     * Valida que los campos requeridos estén presentes y no vacíos en POST.
     * Devuelve array de errores (vacío si todo OK).
     */
    protected function validateRequired(array $fields): array
    {
        $errors = [];
        foreach ($fields as $field => $label) {
            if (empty($_POST[$field])) {
                $errors[$field] = "El campo {$label} es obligatorio.";
            }
        }
        return $errors;
    }
}
