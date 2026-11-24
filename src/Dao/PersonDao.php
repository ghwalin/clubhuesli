<?php

declare(strict_types=1);

namespace Clubhuesli\Dao;

use Clubhuesli\Model\AbstractPerson;
use Clubhuesli\Model\LegalPerson;
use Clubhuesli\Model\NaturalPerson;
use Clubhuesli\Model\PersonType;
use Clubhuesli\Model\TeamMember;
use PDO;
use RuntimeException;

/**
 * Database access for persons.
 *
 * The inheritance of the person hierarchy is mapped table-per-type: `person`
 * holds the common attributes, `natural_person`, `team_member` and
 * `legal_person` the specialised ones and share the primary key.
 */
class PersonDao
{
    /** Columns of a person including every specialisation. */
    private const SELECT_COLUMNS = '
        p.id, p.person_type, p.member_type, p.email, p.phone, p.street, p.house_number,
        p.address_addition, p.zip, p.city, p.country, p.note,
        n.first_name, n.last_name, n.birth_date,
        t.person_id AS team_member_id, t.ahv_number, t.js_number, t.photo_consent,
        l.name, l.contact_first_name, l.contact_last_name, l.support';

    /** Joins of the specialised tables. */
    private const FROM_JOINS = '
        FROM person p
        LEFT JOIN natural_person n ON n.person_id = p.id
        LEFT JOIN team_member t ON t.person_id = p.id
        LEFT JOIN legal_person l ON l.person_id = p.id';

    /** @var PDO|null The connection used by this DAO; resolved on first use. */
    private ?PDO $connection;

    /**
     * @param PDO|null $connection Connection to use; defaults to the shared one.
     */
    public function __construct(?PDO $connection = null)
    {
        $this->connection = $connection;
    }

    /**
     * Returns the connection and opens the shared one on first use, so that
     * requests which need no database (e.g. an unauthenticated one) do not
     * trigger a connection.
     *
     * @return PDO The connection.
     */
    private function connection(): PDO
    {
        if ($this->connection === null) {
            $this->connection = Database::connection();
        }

        return $this->connection;
    }

    /**
     * Returns all persons, optionally filtered, sorted by name.
     *
     * Guardianships are not loaded for the list; use {@see find()} for the
     * details of a single person.
     *
     * @param string|null     $search     Text matched against name, organisation and e-mail.
     * @param PersonType|null $personType Restriction to natural or legal persons.
     *
     * @return array<int, AbstractPerson> The persons found.
     */
    public function findAll(?string $search = null, ?PersonType $personType = null): array
    {
        $sql        = 'SELECT ' . self::SELECT_COLUMNS . self::FROM_JOINS;
        $conditions = [];
        $parameters = [];

        if ($personType !== null) {
            $conditions[]            = 'p.person_type = :personType';
            $parameters['personType'] = $personType->value;
        }
        if ($search !== null && trim($search) !== '') {
            $conditions[]        = '(n.first_name LIKE :search OR n.last_name LIKE :search'
                . ' OR l.name LIKE :search OR p.email LIKE :search)';
            $parameters['search'] = '%' . trim($search) . '%';
        }
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY COALESCE(n.last_name, l.name), n.first_name';

        $statement = $this->connection()->prepare($sql);
        $statement->execute($parameters);

        $persons = [];
        foreach ($statement->fetchAll() as $row) {
            $persons[] = $this->hydrate($row);
        }

        return $persons;
    }

    /**
     * Returns a single person including its guardianships.
     *
     * @param int $id Id of the person.
     *
     * @return AbstractPerson|null The person, or null when unknown.
     */
    public function find(int $id): ?AbstractPerson
    {
        $statement = $this->connection()->prepare(
            'SELECT ' . self::SELECT_COLUMNS . self::FROM_JOINS . ' WHERE p.id = :id'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        if ($row === false) {
            return null;
        }

        $person = $this->hydrate($row);
        if ($person instanceof TeamMember && $person->isMinor()) {
            // Guardianships are only shown for minors. Rows of members who have
            // come of age stay in the database until the member is edited.
            $person->setGuardians($this->findGuardiansOf($id));
        }

        return $person;
    }

    /**
     * Returns the persons holding custody of a team member.
     *
     * @param int $childId Id of the team member.
     *
     * @return array<int, NaturalPerson> The guardians.
     */
    public function findGuardiansOf(int $childId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT ' . self::SELECT_COLUMNS . self::FROM_JOINS
            . ' JOIN guardianship g ON g.guardian_id = p.id WHERE g.child_id = :childId'
            . ' ORDER BY n.last_name, n.first_name'
        );
        $statement->execute(['childId' => $childId]);

        $guardians = [];
        foreach ($statement->fetchAll() as $row) {
            $guardian = $this->hydrate($row);
            if ($guardian instanceof NaturalPerson) {
                $guardians[] = $guardian;
            }
        }

        return $guardians;
    }

    /**
     * Returns the children a natural person holds custody of.
     *
     * @param int $guardianId Id of the guardian.
     *
     * @return array<int, array{id: int, displayName: string}> Id and name of each child.
     */
    public function findChildrenOf(int $guardianId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT p.id, n.first_name, n.last_name FROM guardianship g'
            . ' JOIN person p ON p.id = g.child_id'
            . ' JOIN natural_person n ON n.person_id = p.id'
            . ' WHERE g.guardian_id = :guardianId ORDER BY n.last_name, n.first_name'
        );
        $statement->execute(['guardianId' => $guardianId]);

        $children = [];
        foreach ($statement->fetchAll() as $row) {
            $children[] = [
                'id'          => (int) $row['id'],
                'displayName' => trim($row['first_name'] . ' ' . $row['last_name']),
            ];
        }

        return $children;
    }

    /**
     * Returns the person type stored for an id.
     *
     * @param int $id Id of the person.
     *
     * @return PersonType|null The person type, or null when the id is unknown.
     */
    public function findPersonType(int $id): ?PersonType
    {
        $statement = $this->connection()->prepare('SELECT person_type FROM person WHERE id = :id');
        $statement->execute(['id' => $id]);
        $value = $statement->fetchColumn();

        return is_string($value) ? PersonType::from($value) : null;
    }

    /**
     * Inserts or updates a person together with its specialised rows and its
     * guardianships.
     *
     * @param AbstractPerson $person The person to store.
     *
     * @return int The id of the stored person.
     *
     * @throws RuntimeException When the person type of an existing record would change.
     */
    public function save(AbstractPerson $person): int
    {
        $this->connection()->beginTransaction();
        try {
            $id = $person->getId();
            if ($id === null) {
                $id = $this->insertPerson($person);
                $person->setId($id);
            } else {
                $this->updatePerson($person, $id);
            }

            if ($person instanceof NaturalPerson) {
                $this->saveNaturalPerson($person, $id);
            } elseif ($person instanceof LegalPerson) {
                $this->saveLegalPerson($person, $id);
            }

            $this->connection()->commit();
        } catch (\Throwable $exception) {
            $this->connection()->rollBack();
            throw $exception;
        }

        return $id;
    }

    /**
     * Deletes a person. Specialised rows and guardianships are removed by the
     * cascading foreign keys.
     *
     * @param int $id Id of the person.
     *
     * @return bool True when a person was deleted.
     */
    public function delete(int $id): bool
    {
        $statement = $this->connection()->prepare('DELETE FROM person WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    /**
     * Inserts the row of the general person.
     *
     * @param AbstractPerson $person The person to insert.
     *
     * @return int The generated id.
     */
    private function insertPerson(AbstractPerson $person): int
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO person (person_type, member_type, email, phone, street, house_number,'
            . ' address_addition, zip, city, country, note)'
            . ' VALUES (:personType, :memberType, :email, :phone, :street, :houseNumber,'
            . ' :addressAddition, :zip, :city, :country, :note)'
        );
        $statement->execute([
            'personType'      => $person->getPersonType()->value,
            'memberType'      => $person->getMemberType()?->value,
            'email'           => $person->getEmail(),
            'phone'           => $person->getPhone(),
            'street'          => $person->getStreet(),
            'houseNumber'     => $person->getHouseNumber(),
            'addressAddition' => $person->getAddressAddition(),
            'zip'             => $person->getZip(),
            'city'            => $person->getCity(),
            'country'         => $person->getCountry(),
            'note'            => $person->getNote(),
        ]);

        return (int) $this->connection()->lastInsertId();
    }

    /**
     * Updates the row of the general person.
     *
     * @param AbstractPerson $person The person to update.
     * @param int            $id     Id of the person.
     *
     * @throws RuntimeException When the stored person type differs.
     */
    private function updatePerson(AbstractPerson $person, int $id): void
    {
        $storedType = $this->findPersonType($id);
        if ($storedType !== null && $storedType !== $person->getPersonType()) {
            throw new RuntimeException('Die Personenart einer gespeicherten Person kann nicht geändert werden');
        }

        $statement = $this->connection()->prepare(
            'UPDATE person SET member_type = :memberType, email = :email, phone = :phone,'
            . ' street = :street, house_number = :houseNumber, address_addition = :addressAddition,'
            . ' zip = :zip, city = :city, country = :country, note = :note WHERE id = :id'
        );
        $statement->execute([
            'memberType'      => $person->getMemberType()?->value,
            'email'           => $person->getEmail(),
            'phone'           => $person->getPhone(),
            'street'          => $person->getStreet(),
            'houseNumber'     => $person->getHouseNumber(),
            'addressAddition' => $person->getAddressAddition(),
            'zip'             => $person->getZip(),
            'city'            => $person->getCity(),
            'country'         => $person->getCountry(),
            'note'            => $person->getNote(),
            'id'              => $id,
        ]);
    }

    /**
     * Writes the rows of a natural person and, where applicable, of a team
     * member including its guardianships.
     *
     * @param NaturalPerson $person The person to store.
     * @param int           $id     Id of the person.
     */
    private function saveNaturalPerson(NaturalPerson $person, int $id): void
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO natural_person (person_id, first_name, last_name, birth_date)'
            . ' VALUES (:id, :firstName, :lastName, :birthDate)'
            . ' ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), last_name = VALUES(last_name),'
            . ' birth_date = VALUES(birth_date)'
        );
        $statement->execute([
            'id'        => $id,
            'firstName' => $person->getFirstName(),
            'lastName'  => $person->getLastName(),
            'birthDate' => $person->getBirthDate()?->format('Y-m-d'),
        ]);

        if (!$person instanceof TeamMember) {
            // The person is no longer a team member: drop the specialised row,
            // its guardianships are removed by the cascading foreign key.
            $this->connection()->prepare('DELETE FROM team_member WHERE person_id = :id')->execute(['id' => $id]);

            return;
        }

        $statement = $this->connection()->prepare(
            'INSERT INTO team_member (person_id, ahv_number, js_number, photo_consent)'
            . ' VALUES (:id, :ahvNumber, :jsNumber, :photoConsent)'
            . ' ON DUPLICATE KEY UPDATE ahv_number = VALUES(ahv_number), js_number = VALUES(js_number),'
            . ' photo_consent = VALUES(photo_consent)'
        );
        $statement->execute([
            'id'           => $id,
            'ahvNumber'    => $person->getAhvNumber(),
            'jsNumber'     => $person->getJsNumber(),
            'photoConsent' => $person->hasPhotoConsent() ? 1 : 0,
        ]);

        $this->replaceGuardianships($id, array_keys($person->getGuardians()));
    }

    /**
     * Writes the row of a legal person.
     *
     * @param LegalPerson $person The person to store.
     * @param int         $id     Id of the person.
     */
    private function saveLegalPerson(LegalPerson $person, int $id): void
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO legal_person (person_id, name, contact_first_name, contact_last_name, support)'
            . ' VALUES (:id, :name, :contactFirstName, :contactLastName, :support)'
            . ' ON DUPLICATE KEY UPDATE name = VALUES(name), contact_first_name = VALUES(contact_first_name),'
            . ' contact_last_name = VALUES(contact_last_name), support = VALUES(support)'
        );
        $statement->execute([
            'id'               => $id,
            'name'             => $person->getName(),
            'contactFirstName' => $person->getContactFirstName(),
            'contactLastName'  => $person->getContactLastName(),
            'support'          => $person->getSupport(),
        ]);
    }

    /**
     * Replaces the guardianships of a team member.
     *
     * @param int             $childId     Id of the team member.
     * @param array<int, int> $guardianIds Ids of the guardians.
     */
    private function replaceGuardianships(int $childId, array $guardianIds): void
    {
        $delete = $this->connection()->prepare('DELETE FROM guardianship WHERE child_id = :childId');
        $delete->execute(['childId' => $childId]);

        if ($guardianIds === []) {
            return;
        }
        $insert = $this->connection()->prepare(
            'INSERT INTO guardianship (child_id, guardian_id) VALUES (:childId, :guardianId)'
        );
        foreach ($guardianIds as $guardianId) {
            $insert->execute(['childId' => $childId, 'guardianId' => (int) $guardianId]);
        }
    }

    /**
     * Turns a database row into the matching model object.
     *
     * @param array<string, mixed> $row Row as returned by the select above.
     *
     * @return AbstractPerson The hydrated person.
     */
    protected function hydrate(array $row): AbstractPerson
    {
        $personType = PersonType::from((string) $row['person_type']);

        if ($personType === PersonType::LEGAL) {
            $person = new LegalPerson();
            $this->hydrateCommon($person, $row);
            $person->setName(self::asString($row['name'] ?? null));
            $person->setContactFirstName(self::asString($row['contact_first_name'] ?? null));
            $person->setContactLastName(self::asString($row['contact_last_name'] ?? null));
            $person->setSupport(self::asString($row['support'] ?? null));

            return $person;
        }

        $person = ($row['team_member_id'] ?? null) !== null ? new TeamMember() : new NaturalPerson();
        $this->hydrateCommon($person, $row);
        $person->setFirstName(self::asString($row['first_name'] ?? null));
        $person->setLastName(self::asString($row['last_name'] ?? null));
        $person->setBirthDate(self::asString($row['birth_date'] ?? null));

        if ($person instanceof TeamMember) {
            $person->setAhvNumber(self::asString($row['ahv_number'] ?? null));
            $person->setJsNumber(self::asString($row['js_number'] ?? null));
            // Photo consent applies to minors only; for members who have come
            // of age the stored flag is not carried over.
            $person->setPhotoConsent($person->isMinor() && (int) ($row['photo_consent'] ?? 0) === 1);
        }

        return $person;
    }

    /**
     * Copies the columns of the general person into the model.
     *
     * @param AbstractPerson       $person The person to fill.
     * @param array<string, mixed> $row    Row as returned by the select above.
     */
    private function hydrateCommon(AbstractPerson $person, array $row): void
    {
        $person->setId((int) $row['id']);
        $person->setMemberType(self::asString($row['member_type'] ?? null));
        $person->setCountry(self::asString($row['country'] ?? null));
        $person->setEmail(self::asString($row['email'] ?? null));
        $person->setPhone(self::asString($row['phone'] ?? null));
        $person->setStreet(self::asString($row['street'] ?? null));
        $person->setHouseNumber(self::asString($row['house_number'] ?? null));
        $person->setAddressAddition(self::asString($row['address_addition'] ?? null));
        $person->setZip(self::asString($row['zip'] ?? null));
        $person->setCity(self::asString($row['city'] ?? null));
        $person->setNote(self::asString($row['note'] ?? null));
    }

    /**
     * Casts a database value to a nullable string.
     *
     * @param mixed $value Raw value.
     *
     * @return string|null The value as string, or null.
     */
    private static function asString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }
}
