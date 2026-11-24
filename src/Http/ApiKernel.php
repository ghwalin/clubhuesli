<?php

declare(strict_types=1);

namespace Clubhuesli\Http;

use Clubhuesli\Config;
use Clubhuesli\Controller\AuthController;
use Clubhuesli\Controller\PersonController;
use Clubhuesli\Dao\PersonDao;
use Clubhuesli\Dao\UserDao;
use Clubhuesli\Validation\ValidationException;
use PDOException;
use Throwable;

/**
 * Wires the JSON API together: it builds the controllers, declares the routes
 * and turns exceptions into responses.
 */
final class ApiKernel
{
    /** @var Router The router with every route of the API. */
    private Router $router;

    /**
     * Builds the kernel and its routes.
     *
     * The dependencies are optional so that tests can inject their own data
     * access objects and session.
     *
     * @param PersonDao|null $personDao Data access for persons.
     * @param UserDao|null   $userDao   Data access for accounts.
     * @param Session|null   $session   Session of the current request.
     */
    public function __construct(?PersonDao $personDao = null, ?UserDao $userDao = null, ?Session $session = null)
    {
        $personDao = $personDao ?? new PersonDao();
        $userDao   = $userDao ?? new UserDao();
        $session   = $session ?? new Session($userDao);

        $auth    = new AuthController($userDao, $session);
        $persons = new PersonController($personDao, $session);

        $this->router = new Router();

        $this->router->add('POST', '/auth/login', static fn (Request $r): Response => $auth->login($r));
        $this->router->add('POST', '/auth/logout', static fn (): Response => $auth->logout());
        $this->router->add('GET', '/auth/me', static fn (): Response => $auth->me());

        $this->router->add('GET', '/meta', static fn (): Response => $persons->meta());

        $this->router->add('GET', '/persons', static fn (Request $r): Response => $persons->index($r));
        $this->router->add('POST', '/persons', static fn (Request $r): Response => $persons->store($r));
        $this->router->add('GET', '/persons/{id}', static fn (Request $r, array $p): Response => $persons->show($r, $p));
        $this->router->add('PUT', '/persons/{id}', static fn (Request $r, array $p): Response => $persons->update($r, $p));
        $this->router->add(
            'DELETE',
            '/persons/{id}',
            static fn (Request $r, array $p): Response => $persons->destroy($r, $p)
        );
        $this->router->add(
            'PUT',
            '/persons/{id}/guardians',
            static fn (Request $r, array $p): Response => $persons->updateGuardians($r, $p)
        );
    }

    /**
     * Handles a request and never throws.
     *
     * @param Request $request The request to handle.
     *
     * @return Response The response, including error responses.
     */
    public function handle(Request $request): Response
    {
        try {
            return $this->router->dispatch($request);
        } catch (HttpException $exception) {
            return Response::error($exception->getStatus(), $exception->getMessage());
        } catch (ValidationException $exception) {
            return Response::error(422, $exception->getMessage(), $exception->getErrors());
        } catch (PDOException $exception) {
            $isDuplicate = ($exception->errorInfo[1] ?? 0) === 1062;
            if ($isDuplicate) {
                return Response::error(409, 'Eintrag existiert bereits');
            }

            return Response::error(500, self::serverMessage($exception, 'Datenbankfehler'));
        } catch (Throwable $exception) {
            return Response::error(500, self::serverMessage($exception, 'Serverfehler'));
        }
    }

    /**
     * Reads the request from the superglobals, handles it and sends the
     * response.
     */
    public function run(): void
    {
        try {
            $request = Request::fromGlobals();
        } catch (HttpException $exception) {
            Response::error($exception->getStatus(), $exception->getMessage())->send();

            return;
        }

        $this->handle($request)->send();
    }

    /**
     * Returns the message of an unexpected error: details only in debug mode.
     *
     * @param Throwable $exception The error.
     * @param string    $fallback  Message shown when debugging is off.
     *
     * @return string The message.
     */
    private static function serverMessage(Throwable $exception, string $fallback): string
    {
        return Config::get('debug') === true ? $exception->getMessage() : $fallback;
    }
}
