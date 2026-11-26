<?php

declare(strict_types=1);

namespace Clubhuesli\Controller;

use Clubhuesli\Dao\MembershipDao;
use Clubhuesli\Dao\SeasonDao;
use Clubhuesli\Http\HttpException;
use Clubhuesli\Http\Request;
use Clubhuesli\Http\Response;
use Clubhuesli\Http\Session;
use Clubhuesli\Model\Role;
use Clubhuesli\Model\Season;
use Clubhuesli\Validation\ValidationException;

/**
 * REST endpoints for seasons. Only the roles admin and office may use them.
 */
final class SeasonController
{
    /**
     * @param SeasonDao     $seasonDao     Data access for seasons.
     * @param MembershipDao $membershipDao Data access for memberships.
     * @param Session       $session       Session of the current request.
     */
    public function __construct(
        private readonly SeasonDao $seasonDao,
        private readonly MembershipDao $membershipDao,
        private readonly Session $session,
    ) {
    }

    /**
     * Lists the seasons, the latest first: `GET /api/seasons`.
     *
     * @return Response The seasons.
     */
    public function index(): Response
    {
        $this->requireManager();

        return Response::ok([
            'seasons' => array_map(
                static fn (Season $season): array => $season->toArray(),
                $this->seasonDao->findAll()
            ),
        ]);
    }

    /**
     * Creates a season: `POST /api/seasons`.
     *
     * @param Request $request Request with `name`, `startDate` and `endDate`.
     *
     * @return Response The created season.
     *
     * @throws ValidationException When a rule is violated.
     */
    public function store(Request $request): Response
    {
        $this->requireManager();
        $season = Season::fromArray($request->getBody());
        $this->check($season);
        $this->seasonDao->save($season);

        return Response::created(['season' => $season->toArray()]);
    }

    /**
     * Updates a season: `PUT /api/seasons/{id}`.
     *
     * @param Request               $request    Request with `name`, `startDate` and `endDate`.
     * @param array<string, string> $parameters Route parameters with the key `id`.
     *
     * @return Response The updated season.
     *
     * @throws HttpException       When the season does not exist.
     * @throws ValidationException When a rule is violated.
     */
    public function update(Request $request, array $parameters): Response
    {
        $this->requireManager();
        $id = (int) $parameters['id'];
        $this->load($id);

        $season = Season::fromArray($request->getBody());
        $season->setId($id);
        $this->check($season);
        $this->seasonDao->save($season);

        return Response::ok(['season' => $season->toArray()]);
    }

    /**
     * Deletes a season that no membership refers to: `DELETE /api/seasons/{id}`.
     *
     * @param Request               $request    The request.
     * @param array<string, string> $parameters Route parameters with the key `id`.
     *
     * @return Response An empty response.
     *
     * @throws HttpException When the season does not exist or is still used.
     */
    public function destroy(Request $request, array $parameters): Response
    {
        $this->requireManager();
        $id = (int) $parameters['id'];
        $this->load($id);

        if ($this->membershipDao->existsForSeason($id)) {
            throw new HttpException(409, 'Die Saison wird noch verwendet');
        }
        $this->seasonDao->delete($id);

        return Response::noContent();
    }

    /**
     * Checks the rules that need the other seasons: valid dates, unique name and
     * no overlap.
     *
     * @param Season $season The season to check; a stored one carries its id.
     *
     * @throws ValidationException When a rule is violated.
     */
    private function check(Season $season): void
    {
        $season->assertValid();

        $sameName = $this->seasonDao->findByName((string) $season->getName());
        if ($sameName !== null && $sameName->getId() !== $season->getId()) {
            throw new ValidationException('name', 'Diese Saison existiert bereits');
        }
        foreach ($this->seasonDao->findAll() as $other) {
            if ($other->getId() !== $season->getId() && $season->overlaps($other)) {
                throw new ValidationException(
                    'startDate',
                    'Die Saison überschneidet sich mit der Saison ' . $other->getName()
                );
            }
        }
    }

    /**
     * Loads a season or fails.
     *
     * @param int $id Id of the season.
     *
     * @return Season The season.
     *
     * @throws HttpException When the season does not exist.
     */
    private function load(int $id): Season
    {
        $season = $this->seasonDao->find($id);
        if ($season === null) {
            throw HttpException::notFound('Saison nicht gefunden');
        }

        return $season;
    }

    /**
     * Checks that somebody with the role admin or office is logged in.
     *
     * @throws HttpException When nobody is logged in or the role does not match.
     */
    private function requireManager(): void
    {
        $this->session->requireRole([Role::ADMIN, Role::OFFICE]);
    }
}
