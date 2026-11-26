<?php
/**
 * Member Model.
 * Handles database operations related to gym members.
 */

require_once __DIR__ . '/../includes/db.php';

class Member {
    /**
     * @var PDO Database connection instance.
     */
    private $db_connection;

    /**
     * Member constructor.
     *
     * @param PDO $db_connection Active database connection.
     */
    public function __construct($db_connection) {
        $this->db_connection = $db_connection;
    }

    /**
     * Retrieves all members, optionally filtered by a search keyword.
     *
     * @param string|null $search_keyword Optional name or email filter.
     * @return array List of member records.
     */
    public function getAll($search_keyword = null) {
        $base_sql = "
            SELECT m.*, s.status_name,
                   p.plan_name,
                   ms.start_date AS membership_start,
                   ms.end_date AS membership_end,
                   ms.status AS membership_status,
                   DATEDIFF(ms.end_date, CURDATE()) AS days_remaining
            FROM members m
            JOIN member_statuses s ON m.status_id = s.id
            LEFT JOIN (
                SELECT m1.*
                FROM memberships m1
                INNER JOIN (
                    SELECT member_id, MAX(id) AS max_id
                    FROM memberships
                    GROUP BY member_id
                ) m2 ON m1.id = m2.max_id
            ) ms ON m.id = ms.member_id
            LEFT JOIN membership_plans p ON ms.plan_id = p.id
        ";

        if (!empty($search_keyword)) {
            $sql_query = $base_sql . "
                WHERE m.first_name LIKE :keyword1 
                   OR m.last_name LIKE :keyword2 
                   OR m.email LIKE :keyword3 
                   OR m.phone LIKE :keyword4
                ORDER BY m.id DESC
            ";
            $statement = $this->db_connection->prepare($sql_query);
            $search_param = '%' . $search_keyword . '%';
            $statement->execute([
                ':keyword1' => $search_param,
                ':keyword2' => $search_param,
                ':keyword3' => $search_param,
                ':keyword4' => $search_param,
            ]);
            return $statement->fetchAll();
        }

        $sql_query = $base_sql . " ORDER BY m.id DESC";
        $statement = $this->db_connection->prepare($sql_query);
        $statement->execute();
        return $statement->fetchAll();
    }

    /**
     * Retrieves a single member by their primary ID.
     *
     * @param int $id Member identifier.
     * @return array|false Member data or false if not found.
     */
    public function getById($id) {
        $sql_query = "
            SELECT m.*, s.status_name 
            FROM members m
            JOIN member_statuses s ON m.status_id = s.id
            WHERE m.id = :id
            LIMIT 1
        ";
        $statement = $this->db_connection->prepare($sql_query);
        $statement->execute([':id' => (int)$id]);
        return $statement->fetch();
    }

    /**
     * Creates a new member record.
     *
     * @param array $data Associative array of member attributes.
     * @return int The ID of the newly inserted member.
     */
    public function create($data) {
        $sql_query = "
            INSERT INTO members (first_name, last_name, email, phone, date_of_birth, status_id, joined_on)
            VALUES (:first_name, :last_name, :email, :phone, :date_of_birth, :status_id, :joined_on)
        ";
        $statement = $this->db_connection->prepare($sql_query);
        $statement->execute([
            ':first_name'    => $data['first_name'],
            ':last_name'     => $data['last_name'],
            ':email'         => $data['email'],
            ':phone'         => $data['phone'],
            ':date_of_birth' => $data['date_of_birth'],
            ':status_id'     => (int)$data['status_id'],
            ':joined_on'     => $data['joined_on'],
        ]);
        return (int)$this->db_connection->lastInsertId();
    }

    /**
     * Updates an existing member record.
     *
     * @param int $id Member identifier.
     * @param array $data Associative array of updated values.
     * @return bool True on success, false on failure.
     */
    public function update($id, $data) {
        $sql_query = "
            UPDATE members 
            SET first_name = :first_name,
                last_name = :last_name,
                email = :email,
                phone = :phone,
                date_of_birth = :date_of_birth,
                status_id = :status_id,
                joined_on = :joined_on
            WHERE id = :id
        ";
        $statement = $this->db_connection->prepare($sql_query);
        return $statement->execute([
            ':id'            => (int)$id,
            ':first_name'    => $data['first_name'],
            ':last_name'     => $data['last_name'],
            ':email'         => $data['email'],
            ':phone'         => $data['phone'],
            ':date_of_birth' => $data['date_of_birth'],
            ':status_id'     => (int)$data['status_id'],
            ':joined_on'     => $data['joined_on'],
        ]);
    }

    /**
     * Deletes a member record by ID.
     *
     * @param int $id Member identifier.
     * @return bool True on success, false on failure.
     */
    public function delete($id) {
        $sql_query = "DELETE FROM members WHERE id = :id";
        $statement = $this->db_connection->prepare($sql_query);
        return $statement->execute([':id' => (int)$id]);
    }

    /**
     * Counts active members for dashboard reporting.
     *
     * @return int Number of active members.
     */
    public function getActiveCount() {
        $sql_query = "
            SELECT COUNT(*) AS total 
            FROM members m
            JOIN member_statuses s ON m.status_id = s.id
            WHERE s.status_name = 'Active'
        ";
        $statement = $this->db_connection->prepare($sql_query);
        $statement->execute();
        $row = $statement->fetch();
        return $row ? (int)$row['total'] : 0;
    }

    /**
     * Retrieves all available member status lookup options.
     *
     * @return array List of member statuses.
     */
    public function getAllStatuses() {
        $sql_query = "SELECT * FROM member_statuses ORDER BY id ASC";
        $statement = $this->db_connection->prepare($sql_query);
        $statement->execute();
        return $statement->fetchAll();
    }
}
