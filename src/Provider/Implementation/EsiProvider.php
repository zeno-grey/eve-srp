<?php

declare(strict_types=1);

namespace EveSrp\Provider\Implementation;

use EveSrp\Exception;
use EveSrp\Provider\ProviderInterface;
use EveSrp\Provider\Data\Account;
use EveSrp\Settings;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * A very simple group and character provider.
 *
 * Groups are assigned based on alliance and/or corporation membership for submitter
 * and character IDs for other groups.
 *
 * This does not support alternative characters.
 *
 * @noinspection PhpUnused
 */
class EsiProvider implements ProviderInterface
{
    private string $esiBaseUrl;

    public function __construct(private ClientInterface $httpClient, Settings $settings)
    {
        $this->esiBaseUrl = $settings['ESI_BASE_URL'];
    }

    public function getAccount(int $eveCharacterId): ?Account
    {
        return null;
    }

    public function getGroups(int $eveCharacterId): array
    {
        try {
            $result = $this->httpClient->request(
                'GET',
                "{$this->esiBaseUrl}latest/characters/$eveCharacterId/?datasource=tranquility"
            );
        } catch (GuzzleException $e) {
            throw new Exception('EsiProvider::getGroups: ' . $e->getMessage());
        }

        $userData = \json_decode($result->getBody()->getContents());

        $submitterAlliances = explode(',', (string)($_ENV['EVE_SRP_ESI_SUBMITTER_ALLIANCES'] ?? ''));
        $submitterCorporations = explode(',', (string)($_ENV['EVE_SRP_ESI_SUBMITTER_CORPORATIONS'] ?? ''));
        $reviewChars = explode(',', (string)($_ENV['EVE_SRP_ESI_REVIEW_CHARACTERS'] ?? ''));
        $reviewCorps = explode(',', (string)($_ENV['EVE_SRP_ESI_REVIEW_CORPORATIONS'] ?? ''));
        $payChars = explode(',', (string)($_ENV['EVE_SRP_ESI_PAY_CHARACTERS'] ?? ''));
        $payCorps = explode(',', (string)($_ENV['EVE_SRP_ESI_PAY_CORPORATIONS'] ?? ''));
        $adminChars = explode(',', (string)($_ENV['EVE_SRP_ESI_ADMIN_CHARACTERS'] ?? ''));
        $globalAdminChars = explode(',', (string)($_ENV['EVE_SRP_ESI_GLOBAL_ADMIN_CHARACTERS'] ?? ''));

        $groups = [];
        if (
            in_array($userData->alliance_id, $submitterAlliances) ||
            in_array($userData->corporation_id, $submitterCorporations)
        ) {
            $groups[] = 'member';
        }
        if (in_array($eveCharacterId, $reviewChars) || in_array($userData->corporation_id, $reviewCorps)) {
            $groups[] = 'review';
        }
        if (in_array($eveCharacterId, $payChars) || in_array($userData->corporation_id, $payCorps)) {
            $groups[] = 'pay';
        }
        if (in_array($eveCharacterId, $adminChars)) {
            $groups[] = 'admin';
        }
        if (in_array($eveCharacterId, $globalAdminChars)) {
            $groups[] = 'global-admin';
        }

        return $groups;
    }

    public function getAvailableGroups(): array
    {
        return ['member', 'review', 'pay', 'admin', 'global-admin'];
    }
}
