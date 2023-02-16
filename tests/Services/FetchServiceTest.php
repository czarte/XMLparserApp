<?php

namespace App\Tests\Services;

use App\Services as Services;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;

class FetchServiceTest extends TestCase
{
    public function testFetchFile()
    {
        $client = HttpClient::create();
        $fetchService = new Services\FetchService($client);
        
        $data = $fetchService->fetchXMLfFile('/test/export_one.xml.zip');

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }
}

