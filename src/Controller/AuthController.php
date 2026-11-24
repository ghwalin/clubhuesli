<?php

declare(strict_types=1);

namespace Clubhuesli\Controller;

use Clubhuesli\Dao\UserDao;
use Clubhuesli\Http\HttpException;
use Clubhuesli\Http\Request;
use Clubhuesli\Http\Response;
use Clubhuesli\Http\Session;

/**
 * Minimal login for the roles admin and office (sprint 1 stub).
 *
 * The full authentication (OAuth 2.0 with the Microsoft Identity Platform,
 * passkeys, JWT) follows in a later sprint.
 */
final class AuthController
{
    /**
     * @param UserDao $userDao Data access for accounts.
     * @param Session $session Session of the current request.
     */
    public function __construct(
        private readonly UserDao $userDao,
        private readonly Session $session,
    ) {
    }

    /**
     * Logs an account in: `POST /api/auth/login`.
     *
     * @param Request $request Request with `email` and `password`.
     *
     * @return Response The logged-in account.
     *
     * @throws HttpException When the credentials are wrong or the account is inactive.
     */
    public function login(Request $request): Response
    {
        $body     = $request->getBody();
        $email    = is_string($body['email'] ?? null) ? trim($body['email']) : '';
        $password = is_string($body['password'] ?? null) ? $body['password'] : '';

        if ($email === '' || $password === '') {
            throw HttpException::badRequest('Bitte E-Mail-Adresse und Passwort eingeben');
        }

        $user = $this->userDao->findByEmail($email);
        // The same message for an unknown account and a wrong password, so that
        // no information about existing accounts leaks.
        if ($user === null || !$user->verifyPassword($password)) {
            throw HttpException::unauthorized('E-Mail-Adresse oder Passwort ist falsch');
        }
        if (!$user->isActive()) {
            throw HttpException::forbidden('Dieses Konto ist deaktiviert');
        }

        $this->session->login($user);

        return Response::ok(['user' => $user->toArray()]);
    }

    /**
     * Logs the current account out: `POST /api/auth/logout`.
     *
     * @return Response An empty response.
     */
    public function logout(): Response
    {
        $this->session->logout();

        return Response::noContent();
    }

    /**
     * Returns the logged-in account: `GET /api/auth/me`.
     *
     * @return Response The account.
     *
     * @throws HttpException When nobody is logged in.
     */
    public function me(): Response
    {
        return Response::ok(['user' => $this->session->requireUser()->toArray()]);
    }
}
