<?php
/**
 * Registration Model.
 * Handles class registrations, duplicate sign-up checks, and capacity validations.
 */

require_once __DIR__ . '/../includes/db.php';

class Registration {
    /**
     * @var PDO Database connection instance.
     */
    private $db_connection;

    /**
     * Registration constructor.
     *
     * @param PDO $db_connection Active database connection.
     */
    public function __construct($db_connection) {
        $this->db_connection = $db_connection;
    }

    /**
     * Retrieves all registrations with member and class details.
     *
     * @return array List of registration records.
     */
    public function getAll() {
        $sql_query = "
            SELECT r.*, 
                   m.first_name, m.last_name, m.email AS member_email,
                   c.class_name, c.class_date, c.start_time, c.room,
                   i.full_name AS instructor_name
            FROM registrations r
            JOIN members m ON r.member_id = m.id
            JOIN classes c ON r.class_id = c.id
            JOIN instructors i ON c.instructor_id = i.id
            ORDER BY r.registered_at DESC
        ";
        $statement = $this->db_connection->prepare($sql_query);
        $statement->execute();
        return $statement->fetchAll();
    }

    /**
     * Retrieves a single registration by ID.
     *
     * @param int $id Registration identifier.
     * @return array|false Registration record or false if not found.
     */
    public function getById($id) {
        $sql_query = "
            SELECT r.*, 
                   m.first_name, m.last_name, 
                   c.class_name, c.class_date, c.start_time
            FROM registrations r
            JOIN members m ON r.member_id = m.id
            JOIN classes c ON r.class_id = c.id
            WHERE r.id = :id
            LIMIT 1
        ";
        $statement = $this->db_connection->prepare($sql_query);
        $statement->execute([':id' => (int)$id]);
        return $statement->fetch();
    }

    /**
     * Enrolls a member into a class, verifying that no duplicate enrollment exists
     * and that the class capacity is not exceeded.
     *
     * @param array $data Associative array with member_id and class_id.
     * @return int Newly created registration ID.
     * @throws Exception If duplicate registration or capacity reached.
     */
    public function create($data) {
        $member_id = (int)$data['member_id'];
        $class_id = (int)$data['class_id'];

        // Critical guard 1: Block duplicate registration
        if ($this->isDuplicateRegistration($member_id, $class_id)) {
            throw new Exception("Member is already registered for this class.");
        }

        // Critical guard 2: Block registration when class capacity is reached
        $class_statement = $this->db_connection->prepare("
            SELECT c.capacity, COUNT(r.id) AS current_registrations
            FROM classes c
            LEFT JOIN registrations r ON c.id = r.class_id
            WHERE c.id = :class_id
            GROUP BY c.id
            LIMIT 1
        ");
        $class_statement->execute([':class_id' => $class_id]);
        $class_info = $class_statement->fetch();

        if (!$class_info) {
            throw new Exception("The selected class does not exist.");
        }

        $class_capacity = (int)$class_info['capacity'];
        $current_registrations = (int)$class_info['current_registrations'];

        if ($current_registrations >= $class_capacity) {
            throw new Exception("Class capacity has been reached. No more seats available.");
        }

        // Insert valid registration record
        $sql_query = "
            INSERT INTO registrations (member_id, class_id, attendance_status)
            VALUES (:member_id, :class_id, :attendance_status)
        ";
        $statement = $this->db_connection->prepare($sql_query);
        $statement->execute([
            ':member_id'         => $member_id,
            ':class_id'          => $class_id,
            ':attendance_status' => !empty($data['attendance_status']) ? $data['attendance_status'] : 'Registered',
        ]);

        return (int)$this->db_connection->lastInsertId();
    }

    /**
     * Updates an existing registration record.
     *
     * @param int $id Registration identifier.
     * @param array $data Updated registration data.
     * @return bool True on success, false on failure.
     */
    public function update($id, $data) {
        $sql_query = "
            UPDATE registrations
            SET attendance_status = :attendance_status
            WHERE id = :id
        ";
        $statement = $this->db_connection->prepare($sql_query);
        return $statement->execute([
            ':id'                => (int)$id,
            ':attendance_status' => $data['attendance_status'],
        ]);
    }

    /**
     * Removes/cancels a registration by ID.
     *
     * @param int $id Registration identifier.
     * @return bool True on success, false on failure.
     */
    public function delete($id) {
        $sql_query = "DELETE FROM registrations WHERE id = :id";
        $statement = $this->db_connection->prepare($sql_query);
        return $statement->execute([':id' => (int)$id]);
    }

    /**
     * Checks if a member is already enrolled in a specified class.
     *
     * @param int $member_id Member identifier.
     * @param int $class_id Class identifier.
     * @return bool True if duplicate found, false otherwise.
     */
    public function isDuplicateRegistration($member_id, $class_id) {
        $sql_query = "
            SELECT id 
            FROM registrations 
            WHERE member_id = :member_id AND class_id = :class_id 
            LIMIT 1
        ";
        $statement = $this->db_connection->prepare($sql_query);
        $statement->execute([
            ':member_id' => (int)$member_id,
            ':class_id'  => (int)$class_id,
        ]);
        return (bool)$statement->fetch();
    }
}
