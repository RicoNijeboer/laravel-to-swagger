<?php

namespace RicoNijeboer\Swagger\Exceptions;

use Exception;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use RicoNijeboer\Swagger\Support\Concerns\HelperMethods;

/**
 * Class MalformedServersException
 *
 * @package RicoNijeboer\Swagger\Exceptions
 */
class MalformedServersException extends Exception
{
    use HelperMethods;

    public function __construct(MessageBag $errors)
    {
        parent::__construct($this->formatErrors($errors));
    }

    /**
     * @param MessageBag $errors
     *
     * @return string
     */
    protected function formatErrors(MessageBag $errors): string
    {
        $message = 'Whoops. Looks like something went wrong while reading your Swagger servers configuration:';
        $servers = array_unique(
            array_map(function (string $key) {
                preg_match('/servers\.([0-9]+)/', $key, $matches);

                return ((int)array_pop($matches));
            }, $errors->keys())
        );

        foreach ($servers as $server) {
            $message .= PHP_EOL;
            $message .= PHP_EOL;
            $message .= "Validation failed with errors for the {$this->ordinal($server + 1)} server:" . PHP_EOL;

            foreach ($errors->get("servers.{$server}.*") as $key => $keyErrors) {
                $formattedKey = str_replace("servers.{$server}.", '', $key);
                $formattedKey = str_replace('variables.', '', $formattedKey);
                $formattedKey = "[{$formattedKey}]";

                if (Str::contains($key, 'variables')) {
                    $formattedKey = "variable {$formattedKey}";
                }

                foreach ($keyErrors as $error) {
                    $message .= ' - ' . str_replace($key, $formattedKey, $error) . PHP_EOL;
                }
            }
        }

        $message = trim($message);

        return $message;
    }
}
