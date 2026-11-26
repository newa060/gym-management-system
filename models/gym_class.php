<?php
/**
 * Gym Class Model.
 * Handles database operations for classes, schedules, and capacity calculations.
 */

require_once __DIR__ . '/../includes/db.php';

class GymClass {
    /**
     * @var PDO Database connection instance.
     */
    private $db_connection;

    /**
     * GymClass constructor.
     *
     * @param PDO $db_connection Active database connection.
     */
    public function __construct($db_connection) {
        $this->db_connection = $db_connection;
    }

    /**
     * Retrieves all scheduled classes with instructor details and registered counts.
     *
     * @return array List of class records with seat calculations.
     */
    public function getAll() {
        $sql_query = "
            SELECT c.*, i.full_name AS instructor_name, i.email AS instructor_email,
                   COUNT(r.id) AS registered_count,
                   (c.capacity - COUNT(r.id)) AS seats_remaining
            FROM classes c
            JOIN instructors i ON c.instructor_id = i.id
            LEFT JOIN registrations r ON c.id = r.class_id
            GROUP BY c.id
            ORDER BY c.class_date ASC, c.start_time ASC
        ";
        $statement = $this->db_connection->prepare($sql_query);
        $statement->execute();
        return $statement->fetchAll();
    }

    /**
     * Retrieves a single class record by ID.
     *
     * @param int $id Class identifier.
     * @return array|false Class data or false if not found.
     */
    public function getById($id) {
        $sql_query = "
            SELECT c.*, i.full_name AS instructor_name,
                   COUNT(r.id) AS registered_count,
                   (c.capacity - COUNT(r.id)) AS seats_remaining
            FROM classes c
            JOIN instructors i ON c.instructor_id = i.id
            LEFT JOIN registrations r ON c.id = r.class_id
            WHERE c.id = :id
            GROUP BY c.id
            LIMIT 1
        ";
        $statement = $this->db_connection->prepare($sql_query);
        $statement->execute([':id' => (int)$id]);
        return $statement->fetch();
    }

    /**
     * Creates a new class session.
     *
     * @param array $data Class information.
     * @return int ID of the newly created class.
     */
    public function create($data) {
        $sql_query = "
            INSERT INTO classes (class_name, instructor_id, class_date, start_time, capacity, room)
            VALUES (:class_name, :instructor_id, :class_date, :start_time, :capacity, :room)
        ";
        $statement = $this->db_connection->prepare($sql_query);
        $statement->execute([
            ':class_name'    => $data['class_name'],
            ':instructor_id' => (int)$data['instructor_id'],
            ':class_date'    => $data['class_date'],
            ':start_time'    => $data['start_time'],
            ':capacity'      => (int)$data['capacity'],
            ':room'          => $data['room'],
        ]);
        return (int)$this->db_connection->lastInsertId();
    }

    /**
     * Updates an existing class session.
     *
     * @param int $id Class identifier.
     * @param array $data Updated class information.
     * @return bool True on success, false on failure.
     */
    public function update($id, $data) {
        $sql_query = "
            UPDATE classes
            SET class_name = :class_name,
                instructor_id = :instructor_id,
                class_date = :class_date,
                start_time = :start_time,
                capacity = :capacity,
                room = :room
            WHERE id = :id
        ";
        $statement = $this->db_connection->prepare($sql_query);
        return $statement->execute([
            ':id'            => (int)$id,
            ':class_name'    => $data['class_name'],
            ':instructor_id' => (int)$data['instructor_id'],
            ':class_date'    => $data['class_date'],
            ':start_time'    => $data['start_time'],
            ':capacity'      => (int)$data['capacity'],
            ':room'          => $data['room'],
        ]);
    }

    /**
     * Deletes a class session by ID.
     *
     * @param int $id Class identifier.
     * @return bool True on success, false on failure.
     */
    public function delete($id) {
        $sql_query = "DELETE FROM classes WHERE id = :id";
        $statement = $this->db_connection->prepare($sql_query);
        return $statement->execute([':id' => (int)$id]);
    }

    /**
     * Retrieves all classes scheduled for today's date.
     *
     * @return array List of today's classes with seat availability.
     */
    public function getTodayClasses() {
        $sql_query = "
            SELECT c.*, i.full_name AS instructor_name,
                   COUNT(r.id) AS registered_count,
                   (c.capacity - COUNT(r.id)) AS seats_remaining
            FROM classes c
            JOIN instructors i ON c.instructor_id = i.id
            LEFT JOIN registrations r ON c.id = r.class_id
            WHERE c.class_date = CURDATE()
            GROUP BY c.id
            ORDER BY c.start_time ASC
        ";
        $statement = $this->db_connection->prepare($sql_query);
        $statement->execute();
        return $statement->fetchAll();
    }

    /**
     * Retrieves classes scheduled within the current week.
     *
     * @param string|null $start_date Optional start date of the week (YYYY-MM-DD).
     * @return array List of weekly classes.
     */
    public function getWeeklySchedule($start_date = null) {
        $sql_query = "
            SELECT c.*, i.full_name AS instructor_name,
                   COUNT(r.id) AS registered_count,
                   (c.capacity - COUNT(r.id)) AS seats_remaining
            FROM classes c
            JOIN instructors i ON c.instructor_id = i.id
            LEFT JOIN registrations r ON c.id = r.class_id
            WHERE YEARWEEK(c.class_date, 1) = YEARWEEK(IFNULL(:start_date, CURDATE()), 1)
            GROUP BY c.id
            ORDER BY c.class_date ASC, c.start_time ASC
        ";
        $statement = $this->db_connection->prepare($sql_query);
        $statement->execute([':start_date' => $start_date]);
        return $statement->fetchAll();
    }

    /**
     * Retrieves all instructor records.
     *
     * @return array List of instructors.
     */
    public function getInstructors() {
        $sql_query = "SELECT * FROM instructors ORDER BY full_name ASC";
        $statement = $this->db_connection->prepare($sql_query);
        $statement->execute();
        return $statement->fetchAll();
    }
}
