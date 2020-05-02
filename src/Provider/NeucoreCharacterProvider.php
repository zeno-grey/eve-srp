<?php

declare(strict_types=1);

namespace Brave\EveSrp\Provider;

use Brave\EveSrp\SrpException;
use Brave\NeucoreApi\Api\ApplicationApi;
use Brave\NeucoreApi\ApiException;
use Brave\NeucoreApi\Model\Character;
use Brave\Sso\Basics\SessionHandlerInterface;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

/** @noinspection PhpUnused */
class NeucoreCharacterProvider implements CharacterProviderInterface
{
    /**
     * @var ApplicationApi
     */
    private $api;

    /**
     * @var SessionHandlerInterface
     */
    private $session;

    /**
     * @var Character[]|null
     */
    private $characters;

    public function __construct(ContainerInterface $container)
    {
        $this->api = $container->get(ApplicationApi::class);
        $this->session = $container->get(SessionHandlerInterface::class);
    }

    public function getCharacters(int $characterId): array
    {
        $this->fetchCharacters($characterId);

        return array_map(function (Character $char) {
            return $char->getId();
        }, $this->characters);
    }

    public function getMain(int $characterId): ?int
    {
        try {
            $this->fetchCharacters($characterId);
        } catch (SrpException $e) {
            return null;
        }

        foreach ($this->characters as $character) {
            if ($character->getMain()) {
                return $character->getId();
            }
        }
        return null;
    }

    public function getName(int $characterId): ?string
    {
        foreach ($this->characters as $character) {
            if ($character->getId() === $characterId) {
                return $character->getName();
            }
        }
        return null;
    }

    /**
     * @throws SrpException
     */
    private function fetchCharacters(int $characterId): void
    {
        if ($this->characters !== null) {
            return;
        }
        
        $this->characters = [];
        try {
            $this->characters = $this->api->charactersV1($characterId);
        } catch (ApiException | InvalidArgumentException $e) {
            throw new SrpException('NeucoreCharacterProvider::fetchCharacters: ' . $e->getMessage());
        }
    }
}
