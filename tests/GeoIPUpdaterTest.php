<?php

namespace PulkitJalan\GeoIP\Tests;

use Mockery;
use PHPUnit\Framework\TestCase;
use PulkitJalan\GeoIP\GeoIPUpdater;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;
use PulkitJalan\GeoIP\Exceptions\InvalidDatabaseException;
use PulkitJalan\GeoIP\Exceptions\InvalidCredentialsException;

class GeoIPUpdaterTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
    }

    public function test_no_database()
    {
        $this->expectException(InvalidDatabaseException::class);

        (new GeoIPUpdater([]))->update();
    }

    public function test_no_license_key()
    {
        $this->expectException(InvalidCredentialsException::class);

        $database = __DIR__.'/data/GeoLite2-City.mmdb';
        $config = [
            'driver' => 'maxmind_database',
            'maxmind_database' => [
                'database' => $database,
            ],
        ];

        (new GeoIPUpdater($config))->update();
    }

    public function test_maxmind_updater()
    {
        $database = __DIR__.'/data/GeoLite2-City.mmdb';
        $config = [
            'driver' => 'maxmind_database',
            'maxmind_database' => [
                'database' => $database,
                'license_key' => 'test',
            ],
        ];

        $client = Mockery::mock(GuzzleClient::class);
        $response = Mockery::mock(ResponseInterface::class);

        $client->shouldReceive('get')
            ->once()
            ->with('https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&suffix=tar.gz&license_key=test')
            ->andReturn($response);

        $response->shouldReceive('getBody')
            ->once()
            ->andReturn(gzencode('test'));

        $geoipUpdater = new GeoIPUpdater($config, $client);

        $this->assertEquals($geoipUpdater->update(), $database);

        unlink($database);
    }

    public function test_maxmind_updater_invalid_url()
    {
        $database = __DIR__.'/data/GeoLite2-City.mmdb';
        $config = [
            'driver' => 'maxmind_database',
            'maxmind_database' => [
                'database' => $database,
                'download' => 'http://example.com/maxmind_database.mmdb.gz?license_key=',
                'license_key' => 'test',
            ],
        ];

        $client = Mockery::mock(GuzzleClient::class);
        $response = Mockery::mock(ResponseInterface::class);

        $client->shouldReceive('get')
            ->once()
            ->with('http://example.com/maxmind_database.mmdb.gz?license_key=test')
            ->andReturn($response);

        $response->shouldReceive('getBody')
            ->once()
            ->andReturn(false);

        $geoipUpdater = new GeoIPUpdater($config, $client);

        $this->assertFalse($geoipUpdater->update());
    }

    public function test_maxmind_updater_dir_not_exist()
    {
        $database = __DIR__.'/data/new_dir/GeoLite2-City.mmdb';
        $config = [
            'driver' => 'maxmind_database',
            'maxmind_database' => [
                'database' => $database,
                'license_key' => 'test',
            ],
        ];

        $client = Mockery::mock(GuzzleClient::class);
        $response = Mockery::mock(ResponseInterface::class);

        $client->shouldReceive('get')
            ->once()
            ->with('https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&suffix=tar.gz&license_key=test')
            ->andReturn($response);

        $response->shouldReceive('getBody')
            ->once()
            ->andReturn(gzencode('test'));

        $geoipUpdater = new GeoIPUpdater($config, $client);

        $this->assertEquals($geoipUpdater->update(), $database);

        unlink($database);
        rmdir(pathinfo($database, PATHINFO_DIRNAME));
    }
}
