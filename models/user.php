<?php
/**
 * User Model.
 * Handles database operations and authentication for system users and administrators.
 */

require_once __DIR__ . '/../includes/db.php';

class User {
    /**
     * @var PDO Database connection instance.
     */
    private $db_connection;

    /**
     * User constructor.
     *
     * @param PDO $db_connection Active database connection.
     */
    public function __construct($db_connection) {
        $this->db_connection = $db_connection;
    }

    /**
     * Authenticates a user by username or email and plain password.
     *
     * @param string $username_or_email Username or email address.
     * @param string $password Plaintext password provided by user.
     * @return array|false User record array on success, false on failure.
     */
    public function authenticate($username_or_email, $password) {
        $user = $this->findByUsernameOrEmail($username_or_email);
        if (!$user) {
            return false;
        }

        if (password_verify($password, $user['password_hash'])) {
            return $user;
        }

        return false;
    }

    /**
     * Finds a user by their username or email address.
     *
     * @param string $identifier Username or email.
     * @return array|false User record or false if not found.
     */
    public function findByUsernameOrEmail($identifier) {
        $clean_identifier = trim($identifier);
        $statement = $this->db_connection->prepare("
            SELECT * FROM users
            WHERE username = :username OR email = :email
            LIMIT 1
        ");
        $statement->execute([
            ':username' => $clean_identifier,
            ':email' => $clean_identifier,
        ]);
        return $statement->fetch();
    }

    /**
     * Finds a user by primary key ID.
     *
     * @param int $id User ID.
     * @return array|false User record or false if not found.
     */
    public function findById($id) {
        $statement = $this->db_connection->prepare("
            SELECT id, username, email, full_name, role, created_at
            FROM users
            WHERE id = :id
            LIMIT 1
        ");
        $statement->execute([':id' => (int)$id]);
        return $statement->fetch();
    }

    /**
     * Creates a new user in the system.
     *
     * @param string $username
     * @param string $email
     * @param string $password Plaintext password.
     * @param string $full_name
     * @param string $role
     * @return int Created user ID.
     */
    public function createUser($username, $email, $password, $full_name, $role = 'admin') {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $statement = $this->db_connection->prepare("
            INSERT INTO users (username, email, password_hash, full_name, role)
            VALUES (:username, :email, :password_hash, :full_name, :role)
        ");
        $statement->execute([
            ':username' => trim($username),
            ':email' => trim($email),
            ':password_hash' => $hash,
            ':full_name' => trim($full_name),
            ':role' => trim($role)
        ]);
        return (int)$this->db_connection->lastInsertId();
    }
}
