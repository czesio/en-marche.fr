<?php

namespace AppBundle\Event;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Marks a registration process starting as anonymous
 * for any event, including committee and citizen related events.
 */
final class AnonymousRegistrationSession
{
    public const INTENTION = 'app.anonymous_registration_intention';

    private const SESSION_KEY = 'app.anonymous_registration_url';

    private $session;
    private $urlGenerator;

    public function __construct(SessionInterface $session, UrlGeneratorInterface $urlGenerator)
    {
        $this->session = $session;
        $this->urlGenerator = $urlGenerator;
    }

    public function start(Request $request): ?RedirectResponse
    {
        if (!$request->query->has(self::INTENTION)) {
            return null;
        }

        $this->session->set(self::SESSION_KEY, $request->getPathInfo());

        return new RedirectResponse($request->query->get(self::INTENTION));
    }

    public function isStarted(): bool
    {
        return $this->session->has(self::SESSION_KEY);
    }

    public function terminate(): RedirectResponse
    {
        if (!$this->isStarted()) {
            throw new \LogicException('The event registration session is not started.');
        }

        return new RedirectResponse($this->session->remove(self::SESSION_KEY));
    }
}
