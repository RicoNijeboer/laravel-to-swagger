<?php

namespace RicoNijeboer\Swagger\Actions;

use Faker\Generator;
use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use RicoNijeboer\Swagger\Support\Concerns\HelperMethods;

/**
 * Class ObfuscateJsonAction
 *
 * @package RicoNijeboer\Swagger\Actions
 */
class ObfuscateJsonAction
{
    use HelperMethods;

    protected Generator $faker;

    public function __construct()
    {
        $this->faker = Container::getInstance()->make(Generator::class);
    }

    public function obfuscateArray(array $array): array
    {
        $result = [];

        foreach ($this->recursively($array) as [$item, $key]) {
            Arr::set($result, $key, $this->obfuscateValue($item));
        }

        return $result;
    }

    public function obfuscateValue($value)
    {
        if (is_numeric($value)) {
            if (is_int($value)) {
                return $this->faker->randomNumber(0);
            }

            $decimals = strlen(explode('.', (string)$value)[1] ?? '');

            return $this->faker->randomNumber($decimals);
        }

        if (is_string($value)) {
            $time = strtotime($value);

            if ($time !== false) {
                $format = $this->getDateFormat($time);

                $dateTime = $this->faker->dateTime();

                if ($format !== false) {
                    return (new Carbon($dateTime))->format($format);
                }

                return (new Carbon($dateTime))->toString();
            }

            return $this->faker->word();
        }

        ray($value);
        ray()->pause();

        return $value;
    }

    /**
     * @param string $json
     *
     * @return string
     */
    public function obfuscateJson(string $json): string
    {
        return json_encode(
            $this->obfuscateArray(
                json_decode($json, true)
            )
        );
    }
}
