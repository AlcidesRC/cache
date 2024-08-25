<?php

declare(strict_types=1);

namespace Cache\Tests\Unit;

use Cache\Exceptions\WrongPackerSchemaException;
use Cache\Packer;
use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Cache\Packer::class)]
#[CoversClass(\Cache\Exceptions\WrongPackerSchemaException::class)]
final class PackerTest extends TestCase
{
    private const string STATS_FILENAME = '/tmp/stats.log';

    // phpcs:ignore
    private const string STATS_LINE = 'Total rows: {rows} | Source: {sizeSource} bytes | Packed: {sizePacked} bytes | Optimized {percentage} %' . PHP_EOL;

    private Generator $faker;

    protected function setUp(): void
    {
        $this->faker = Factory::create();
    }

    #[Test]
    public function testPackedSchemaShouldContainRequiredKeys(): void
    {
        $source = $this->generateFakedData(9);

        $packed = Packer::pack($source);

        $first = (array) reset($source);

        $this->assertArrayHasKey('keys', $packed);
        $this->assertArrayHasKey('data', $packed);
        $this->assertEquals(array_keys($first), $packed['keys']);
    }

    #[Test]
    public function testUnpackedVersionMatchesSource(): void
    {
        $source = $this->generateFakedData(9);

        $unpacked = Packer::unpack(
            Packer::pack($source)
        );

        $this->assertArrayNotHasKey('keys', $unpacked);
        $this->assertArrayNotHasKey('data', $unpacked);
        $this->assertEquals($source, $unpacked);
    }

    #[Test]
    #[DataProvider('sizesProvider')]
    public function testPackedVersionRequiresLessResources(
        int $rows,
        string $sourceFilename,
        string $packedFilename
    ): void {
        $logStatistics = function (int $rows, int $sizeSource, int $sizePacked): void {
            $percentage = number_format(100 - round($sizePacked * 100 / $sizeSource, 2), 2);

            $line = strtr(self::STATS_LINE, [
                '{rows}' => str_pad((string) $rows, 4, ' ', STR_PAD_LEFT),
                '{sizeSource}' => str_pad((string) $sizeSource, 7, ' ', STR_PAD_LEFT),
                '{sizePacked}' => str_pad((string) $sizePacked, 7, ' ', STR_PAD_LEFT),
                '{percentage}' => str_pad((string) $percentage, 5, ' ', STR_PAD_LEFT),
            ]);

            file_put_contents(self::STATS_FILENAME, $line, FILE_APPEND);
        };

        $source = $this->generateFakedData($rows);
        file_put_contents($sourceFilename, serialize($source));
        $sizeSource = filesize($sourceFilename);
        unset($sourceFilename);

        $packed = Packer::pack($source);
        file_put_contents($packedFilename, serialize($packed));
        $sizePacked = filesize($packedFilename);
        unset($packedFilename);

        $this->assertLessThan($sizeSource, $sizePacked);

        $logStatistics($rows, (int) $sizeSource, (int) $sizePacked);
    }

    /**
     * @return array<int,mixed>
     */
    public static function sizesProvider(): array
    {
        return [
            [9, '/tmp/source.data', '/tmp/packed.data'],
            [99, '/tmp/source.data', '/tmp/packed.data'],
            [999, '/tmp/source.data', '/tmp/packed.data'],
            [9999, '/tmp/source.data', '/tmp/packed.data'],
            [99999, '/tmp/source.data', '/tmp/packed.data'],
        ];
    }

    /**
     * @param non-empty-array<string, array> $packed
     */
    #[Test]
    #[DataProvider('exceptionProvider')]
    public function testExpectsExceptionWithWrongPackedKeysKey(array $packed, string $message): void
    {
        $this->expectException(WrongPackerSchemaException::class);
        $this->expectExceptionMessage($message);

        Packer::unpack($packed);
    }

    /**
     * @return array<int,mixed>
     */
    public static function exceptionProvider(): array
    {
        return [
            [[], 'Wrong schema: expected key [ keys ] is required'],
            [['keys' => null], 'Wrong schema: expected key [ keys ] is required'],
            [['keys' => []], 'Wrong schema: expected key [ keys ] is required'],
            [['keys' => ['id', 'firstName']], 'Wrong schema: expected key [ data ] is required'],
            [['keys' => ['id', 'firstName'], 'data' => null], 'Wrong schema: expected key [ data ] is required'],
            [['keys' => ['id', 'firstName'], 'data' => []], 'Wrong schema: expected key [ data ] is required'],
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function generateFakedData(int $maxRows): array
    {
        return array_map(function (int $i): array {
            return [
                'id' => $i,
                'firstName' => $this->faker->firstName(),
                'lastName' => $this->faker->lastName(),
                'email' => $this->faker->email(),
                'address' => $this->faker->address(),
                'city' => $this->faker->city(),
                'postcode' => $this->faker->postcode(),
                'country' => $this->faker->country(),
            ];
        }, range(1, $maxRows));
    }
}
