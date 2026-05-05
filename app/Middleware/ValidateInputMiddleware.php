<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ValidateInputMiddleware implements MiddlewareInterface
{
    private array $rules;

    /**
     * @param array $rules Format: ['field_name' => ['required' => true, 'type' => 'email', ...]]
     */
    public function __construct(array $rules = [])
    {
        $this->rules = $rules;
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $data = json_decode((string) $request->getBody(), true) ?: [];

        $errors = $this->validate($data, $this->rules);

        if (!empty($errors)) {
            return $this->badRequest($errors);
        }

        return $handler->handle($request);
    }

    private function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? '';

            // Check if required
            if ($fieldRules['required'] ?? false) {
                if (is_string($value) && trim($value) === '') {
                    $errors[$field][] = ucfirst($field) . ' wajib diisi';
                    continue;
                }
            }

            // Skip validation jika field tidak ada dan tidak required
            if ((is_string($value) && trim($value) === '') && !($fieldRules['required'] ?? false)) {
                continue;
            }

            // Validate by type
            $type = $fieldRules['type'] ?? null;
            switch ($type) {
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field][] = 'Format email tidak valid';
                    }
                    break;

                case 'username':
                    if (!preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $value)) {
                        $errors[$field][] = 'Username harus 3-20 karakter (hanya huruf, angka, underscore, dash)';
                    }
                    break;

                case 'password':
                    $minLength = $fieldRules['min_length'] ?? 6;
                    if (strlen($value) < $minLength) {
                        $errors[$field][] = "Password minimal $minLength karakter";
                    }
                    if ($fieldRules['require_uppercase'] ?? false && !preg_match('/[A-Z]/', $value)) {
                        $errors[$field][] = 'Password harus mengandung huruf besar';
                    }
                    if ($fieldRules['require_number'] ?? false && !preg_match('/[0-9]/', $value)) {
                        $errors[$field][] = 'Password harus mengandung angka';
                    }
                    if ($fieldRules['require_special'] ?? false && !preg_match('/[!@#$%^&*]/', $value)) {
                        $errors[$field][] = 'Password harus mengandung karakter spesial (!@#$%^&*)';
                    }
                    break;

                case 'string':
                    if (!is_string($value)) {
                        $errors[$field][] = ucfirst($field) . ' harus berupa string';
                    }
                    break;

                case 'integer':
                    if (!is_numeric($value) || (int)$value != $value) {
                        $errors[$field][] = ucfirst($field) . ' harus berupa angka';
                    }
                    break;
            }

            // Validate min length
            if (isset($fieldRules['min_length']) && is_string($value)) {
                if (strlen(trim($value)) < $fieldRules['min_length']) {
                    $errors[$field][] = ucfirst($field) . ' minimal ' . $fieldRules['min_length'] . ' karakter';
                }
            }

            // Validate max length
            if (isset($fieldRules['max_length']) && is_string($value)) {
                if (strlen(trim($value)) > $fieldRules['max_length']) {
                    $errors[$field][] = ucfirst($field) . ' maksimal ' . $fieldRules['max_length'] . ' karakter';
                }
            }
        }

        return $errors;
    }

    private function badRequest(array $errors): Response
    {
        $response = new \Slim\Psr7\Response();
        $payload = json_encode(['error' => 'Validasi gagal', 'errors' => $errors], JSON_UNESCAPED_UNICODE);
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(400);
    }
}
