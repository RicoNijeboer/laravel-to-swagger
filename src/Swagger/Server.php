<?php

namespace Rico\Swagger\Swagger;

/**
 * Class Server
 *
 * @package Rico\Swagger\Swagger
 */
class Server
{

    private string $url;

    private ?string $description;

    public function __construct(string $url, ?string $description = null)
    {
        $this->url = $url;
        $this->description = $description;
    }

    /**
     * Convert the server to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_filter([
            'url'         => $this->url,
            'description' => $this->description,
        ], fn($val) => ! empty($val));
    }
}