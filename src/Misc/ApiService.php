<?php

declare(strict_types=1);

namespace EveSrp\Misc;

use EveSrp\Settings;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class ApiService
{
    private string $esiBaseUrl;

    private string $killboardBaseUrl;

    public function __construct(private ClientInterface $httpClient, Settings $settings)
    {
        $this->esiBaseUrl = $settings['ESI_BASE_URL'];
        $this->killboardBaseUrl = $settings['ZKILLBOARD_BASE_URL'];
    }

    public function getJsonData(string $url): array|\stdClass|null
    {
        $url = str_starts_with($url, 'http') ? $url : $this->esiBaseUrl . $url;
        try {
            $apiResponse = $this->httpClient->request('GET', $url);
        } catch (GuzzleException $e) {
            error_log(__METHOD__ . ' request: ' . $e->getMessage());
            return null;
        }
        $apiData = \json_decode($apiResponse->getBody()->__toString());
        if ($apiData === null) {
            error_log(__METHOD__ . ' json: ' . json_last_error_msg());
            return null;
        }

        return $apiData;
    }

    /**
     * @param string $url e.g. https://zkillboard.com/kill/82474608/
     * @return string|null
     */
    public function getEsiUrlFromKillboard(string $url): ?string
    {
        $urlParts = explode('/', rtrim($url, '/'));
        $killId = end($urlParts);
        if (!is_numeric($killId)) {
            error_log(__METHOD__ . ': Invalid kill ID: ' . $killId);
            return null;
        }

        $killboardData = $this->getJsonData("$this->killboardBaseUrl/api/killID/$killId/");
        if ($killboardData === null || !isset($killboardData[0])) {
            return null;
        }

        return "{$this->esiBaseUrl}latest/killmails/$killId/{$killboardData[0]->zkb->hash}/";
    }
}
