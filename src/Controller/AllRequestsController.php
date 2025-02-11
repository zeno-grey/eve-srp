<?php

declare(strict_types=1);

namespace EveSrp\Controller;

use EveSrp\Controller\Traits\RequestParameter;
use EveSrp\Controller\Traits\TwigResponse;
use EveSrp\Model\Division;
use EveSrp\Repository\CharacterRepository;
use EveSrp\Repository\DivisionRepository;
use EveSrp\Repository\RequestRepository;
use EveSrp\Repository\UserRepository;
use EveSrp\Service\RequestService;
use EveSrp\Service\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class AllRequestsController
{
    use RequestParameter;
    use TwigResponse;

    public function __construct(
        private RequestRepository $requestRepository,
        private DivisionRepository $divisionRepository,
        private UserRepository $userRepository,
        private CharacterRepository $characterRepository,
        private UserService $userService,
        private RequestService $requestService,
        Environment $environment
    ) {
        $this->twigResponse($environment);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $divisions = $this->requestService->getDivisionsWithEditPermission();

        if ($this->paramGet($request, 'submit') !== null) {
            $inputStatus = (string)$this->paramGet($request, 'status');
            $inputDivision = (int)$this->paramGet($request, 'division', '0');
            $inputUser = (string)$this->paramGet($request, 'user');
            $inputPilot = (string)$this->paramGet($request, 'pilot');
            $currentPage = max(1, ((int)$this->paramGet($request, 'page', '1')));

            // check division permission
            $allDivisions = array_map(function (Division $division) {
                return $division->getId();
            }, $divisions);
            if ($inputDivision > 0 && !in_array($inputDivision, $allDivisions)) {
                $inputDivision = -2; // shows nothing
            }

            // Search criteria and variables for pager.
            $criteria = [];
            if ($inputStatus !== '') {
                $criteria['status'] = $inputStatus;
            }
            if ($inputDivision === 0) {
                $criteria['division'] = $allDivisions;
            } else {
                $criteria['division'] = $inputDivision === -1 ? null : $inputDivision;
            }
            if ($inputUser) {
                $criteria['user'] = $this->getUserId($inputUser);
            }
            if ($inputPilot) {
                $criteria['character'] = $this->getCharacterId($inputPilot);
            }
            $limit = 100;
            $totalRequests = $this->requestRepository->count($criteria);
            $totalPages = ceil($totalRequests / $limit);
            $currentPage = min($totalPages, $currentPage);
            $offset = max(0, ($limit * $currentPage) - $limit);

            $pagerLink = "?division=$inputDivision&status=$inputStatus&user=$inputUser&pilot=$inputPilot&submit&page=";
            $requests = $this->requestRepository->findBy($criteria, ['created' => 'DESC'], $limit, $offset);
        }

        return $this->render($response, 'pages/all-requests.twig', [
            'divisions' => $divisions,
            'inputDivision' => $inputDivision ?? 0,
            'inputStatus' => $inputStatus ?? '',
            'inputUser' => $inputUser ?? null,
            'inputPilot' => $inputPilot ?? null,
            'requests' => $requests ?? [],
            'pagerCurrentPage' => $currentPage ?? 0,
            'pagerTotalPages' => $totalPages ?? 0,
            'pagerLink' => $pagerLink ?? '',
        ]);
    }

    private function getUserId(string $name): ?int
    {
        $result = $this->userRepository->findOneBy(['name' => $name]);
        return $result?->getId();
    }

    private function getCharacterId(string $name): ?int
    {
        $result = $this->characterRepository->findOneBy(['name' => $name]);
        return $result?->getId();
    }
}
