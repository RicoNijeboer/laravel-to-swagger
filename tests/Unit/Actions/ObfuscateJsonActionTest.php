<?php

namespace RicoNijeboer\Swagger\Tests\Unit\Actions;

use RicoNijeboer\Swagger\Actions\ObfuscateJsonAction;
use RicoNijeboer\Swagger\Tests\TestCase;

/**
 * Class ObfuscateJsonActionTest
 *
 * @package RicoNijeboer\Swagger\Tests\Unit\Actions
 */
class ObfuscateJsonActionTest extends TestCase
{
    private ObfuscateJsonAction $action;

    private array $jsonArray;

    public function setUp(): void
    {
        parent::setUp();

        $this->action = resolve(ObfuscateJsonAction::class);
        $json = file_get_contents(implode(DIRECTORY_SEPARATOR, [
            __DIR__,
            '..',
            '..',
            'stubs',
            'obfuscatable.json',
        ]));
        $this->jsonArray = json_decode(preg_replace('/\s*/', '', $json), true);
    }

    /**
     * @test
     */
    public function it_can_obfuscate_a_json_object()
    {
        $out = $this->action->obfuscateArray($this->jsonArray);

        foreach ($this->recursively($this->jsonArray) as [$item, $key]) {
            $this->assertArrayDoesntHaveKeys([$key => $item], $out);
        }
    }
}
