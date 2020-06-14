<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace Brave\EveSrp\Controller;

use Brave\EveSrp\Controller\Traits\TwigResponse;
use Brave\EveSrp\FlashMessage;
use Brave\EveSrp\Provider\GroupProviderInterface;
use Brave\EveSrp\SrpException;
use Brave\EveSrp\UserService;
use Brave\Sso\Basics\AuthenticationProvider;
use Brave\Sso\Basics\SessionHandlerInterface;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;
use UnexpectedValueException;

class AuthController
{
    use TwigResponse;

    /**
     * @var array
     */
    private $settings;

    /**
     * @var SessionHandlerInterface
     */
    private $sessionHandler;

    /**
     * @var GroupProviderInterface
     */
    private $groupProvider;

    /**
     * @var UserService 
     */
    private $userService;

    /**
     * @var AuthenticationProvider
     */
    private $authenticationProvider;

    /**
     * @var FlashMessage
     */
    private $flashMessage;

    public function __construct(ContainerInterface $container)
    {
        $this->settings = $container->get('settings');
        $this->sessionHandler = $container->get(SessionHandlerInterface::class);
        $this->groupProvider = $container->get(GroupProviderInterface::class);
        $this->userService = $container->get(UserService::class);
        $this->authenticationProvider = $container->get(AuthenticationProvider::class);
        $this->flashMessage = $container->get(FlashMessage::class);

        $this->twigResponse($container->get(Environment::class));
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $state = $this->authenticationProvider->generateState();
        } catch (Exception $e) {
            $state = uniqid('srp', true);
        }
        $this->sessionHandler->set('ssoState', $state);

        return $this->render($response, 'pages/login.twig', [
            'serviceName' => $this->settings['APP_TITLE'],
            'logo'        => $this->settings['APP_LOGO'],
            'logoAltText' => $this->settings['APP_LOGO_ALT'],
            'loginUrl'    => $this->authenticationProvider->buildLoginUrl($state),
            'coreUrl'     => $this->settings['CORE_DOMAIN'],
            'coreName'    => $this->settings['CORE_NAME'],
        ]);
    }

    /**
     * EVE SSO callback.
     *
     * @throws \LogicException
     */
    public function auth(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $code = $request->getQueryParams()['code'] ?? null;
        $state = $request->getQueryParams()['state'] ?? null;
        if (!$code || !$state) {
            $this->flashMessage->addMessage('Invalid SSO state.', FlashMessage::TYPE_DANGER);
            return $response->withHeader('Location', '/login');
        }

        try {
            $eveAuth = $this->authenticationProvider->validateAuthenticationV2(
                $state,
                $this->sessionHandler->get('ssoState'),
                $code
            );
        } catch (UnexpectedValueException $e) {
            $this->flashMessage->addMessage($e->getMessage(), FlashMessage::TYPE_DANGER);
            return $response->withHeader('Location', '/login');
        }

        $user = $this->userService->getUser($eveAuth);
        try {
            $this->userService->syncCharacters($user, $eveAuth->getCharacterId());
        } catch (SrpException $e) {
            $this->flashMessage->addMessage($e->getMessage(), FlashMessage::TYPE_DANGER);
        }
        try {
            $this->userService->syncGroups($eveAuth->getCharacterId(), $user);
        } catch (SrpException $e) {
            $this->flashMessage->addMessage($e->getMessage(), FlashMessage::TYPE_DANGER);
        }
        $this->sessionHandler->set('userId', $user->getId());

        return $response->withHeader('Location', '/');
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->sessionHandler->set('userId', null);

        return $response->withHeader('Location', '/');
    }
}
