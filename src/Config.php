<?php

declare(strict_types=1);

namespace App;

final class Config
{
    /** @var array<string, string> */
    private array $fileValues = [];

    private function __construct()
    {
    }

    public static function load(string $projectRoot): self
    {
        $config = new self();
        $envPath = rtrim($projectRoot, '/') . '/.env';

        if (is_readable($envPath)) {
            $config->parseEnvFile($envPath);
        }

        return $config;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $value = $this->resolve($key);

        if ($value === null || $value === '') {
            return $default;
        }

        return $value;
    }

    public function require(string $key): string
    {
        $value = $this->resolve($key);

        if ($value === null || $value === '') {
            throw new \RuntimeException('Missing required configuration: ' . $key);
        }

        return $value;
    }

    public function isDebug(): bool
    {
        $value = $this->get('APP_DEBUG');

        if ($value === null) {
            return false;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes'], true);
    }

    public function getDbHost(): string
    {
        return $this->require('DB_HOST');
    }

    public function getDbPort(): int
    {
        $port = $this->get('DB_PORT');

        if ($port === null || $port === '') {
            return 3306;
        }

        return (int) $port;
    }

    public function getDbName(): string
    {
        return $this->require('DB_NAME');
    }

    public function getDbUser(): string
    {
        return $this->require('DB_USER');
    }

    public function getDbPassword(): string
    {
        return $this->require('DB_PASSWORD');
    }

    private function parseEnvFile(string $path): void
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $separator = strpos($line, '=');

            if ($separator === false) {
                continue;
            }

            $key = trim(substr($line, 0, $separator));
            $value = trim(substr($line, $separator + 1));

            if ($key === '') {
                continue;
            }

            $this->fileValues[$key] = $this->stripQuotes($value);
        }
    }

    private function stripQuotes(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        $first = $value[0];
        $last = $value[strlen($value) - 1];

        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    private function resolve(string $key): ?string
    {
        $env = getenv($key);

        if ($env !== false && $env !== '') {
            return $env;
        }

        if (array_key_exists($key, $this->fileValues)) {
            return $this->fileValues[$key];
        }

        return null;
    }
}
