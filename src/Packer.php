<?php

namespace Fonil\Cache;

use Fonil\Cache\Exceptions\WrongPackerSchemaException;

final class Packer
{
    /**
     * @param array<int,array<string,mixed>> $source
     * @return array<string,array<mixed>>
     */
    public static function pack(array $source): array
    {
        $generator = function (array $data) {
            foreach ($data as $entry) {
                yield $entry;
            }
        };

        $output = [
            'keys' => array_keys($source[0]),
            'data' => [],
        ];

        foreach ($generator($source) as $entry) {
            array_push($output['data'], array_values($entry));
        }

        return $output;
    }

    /**
     * @param array<string,array<mixed>> $packed
     * @return array<int,array<string,mixed>>
     * @throws WrongPackerSchemaException
     */
    public static function unpack(array $packed): array
    {
        if (!array_key_exists('keys', $packed) || !is_array($packed['keys']) || empty($packed['keys'])) {
            throw new WrongPackerSchemaException('Wrong schema: expected key [ keys ] is required');
        }

        if (!array_key_exists('data', $packed) || !is_array($packed['data']) || empty($packed['data'])) {
            throw new WrongPackerSchemaException('Wrong schema: expected key [ data ] is required');
        }

        $generator = function (array $data) {
            foreach ($data as $entry) {
                yield $entry;
            }
        };

        $output = [];

        foreach ($generator($packed['data']) as $entry) {
            array_push($output, array_combine($packed['keys'], $entry));
        }

        return $output;
    }
}
