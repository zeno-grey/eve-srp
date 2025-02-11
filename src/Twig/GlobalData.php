<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace EveSrp\Twig;

use EveSrp\Model\Character;
use EveSrp\Model\User;
use EveSrp\Service\UserService;
use EveSrp\Settings;
use EveSrp\Type;

class GlobalData
{
    public function __construct(private Settings $settings, private UserService $userService)
    {
    }

    public function appTitle(): string
    {
        return $this->settings['APP_TITLE'];
    }

    public function loginHint(): string
    {
        return nl2br(str_replace(
            '\n',
            '<br>',
            $this->replaceMarkdownLink(htmlspecialchars($this->settings['LOGIN_HINT']))
        ));
    }

    public function footerText(): string
    {
        $html = $this->replaceMarkdownLink(htmlspecialchars($this->settings['FOOTER_TEXT']));

        if (!empty($html)) {
            $html .= '<br>';
        }

        return $html;
    }

    public function userName(): string
    {
        return $this->getUser() ? $this->getUser()->getName() : '';
    }

    public function characters(): array
    {
        return $this->getUser() ? array_map(function(Character $char) {
            return $char->getName();
        }, $this->getUser()->getCharacters()) : [];
    }

    public function statuses(): array
    {
        return [Type::INCOMPLETE, Type::EVALUATING, Type::APPROVED, Type::REJECTED, Type::PAID];
    }

    private function getUser(): ?User
    {
        return $this->userService->getAuthenticatedUser();
    }

    private function replaceMarkdownLink(string $text): string
    {
        return preg_replace(
            '/\[(.*?)]\((.*?)\)/',
            '<a href="$2" target="_blank" rel="noopener noreferrer" class="srp-external-link">$1</a> ',
            $text
        );
    }
}
