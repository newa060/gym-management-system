<?php
/**
 * Membership Model.
 * Handles membership plan assignments and membership plan CRUD operations.
 */

require_once __DIR__ . '/../includes/db.php';

class Membership {
    /**
     * @var PDO Database connection instance.
     */
    private $db_connection;

    /**
     * Membership constructor.
     *
     * @param PDO $db_connection Active database connection.
     */
    public function __construct($db_connection) {
        $this->db_connection = $db_connection;
    }

    /**
     * Retrieves all membership records joined with member and plan details.
     *
     * @return array List of memberships.
     */
    public function getAll() {
        $sql_query = "
            SELECT ms.*, m.first_name, m.last_name, p.plan_name, p.duration_days, p.price
            FROM memberships ms
            JOIN members m ON ms.member_id = m.id
            JOIN membership_plans p ON ms.plan_id = p.id
            ORDER BY ms.end_date DESC
        ";
        $statement = $this->db_connection->prepare($sql_query);
        $statement->execute();
        return $statement->fetchAll();
    }

    /**
     * Retrieves a single membership by ID.
     *
     * @param int $id Membership identifier.
     * @return array|false Membership record or false if not found.
     */
    public function getById($id) {
        $sql_query = "
            SELECT ms.*, m.first_name, m.last_name, p.plan_name 
            FROM memberships ms
            JOIN members m ON ms.member_id = m.id
            JOIN membership_plans p ON ms.plan_id = p.id
            WHERE ms.id = :id
            LIMIT 1
        ";
        $statement = $this->db_connection->prepare($sql_query);
        $statement->execute([':id' => (int)$id]);
        return $statement->fetch();
    }

    /**
     * Creates a new membership assignment for a member.
     *
     * @param array $data Membership data containing member_id, plan_id, start_date, end_date, status.
     * @return int Newly created membership ID.
     */
    public function create($data) {
        $sql_query = "
            INSERT INTO memberships (member_id, plan_id, start_date, end_date, status)
            VALUES (:member_id, :plan_id, :start_date, :end_date, :status)
        ";
        $statement = $this->db_connection->prepare($sql_query);
        $statement->execute([
            ':member_id'  => (int)$data['member_id'],
            ':plan_id'    => (int)$data['plan_id'],
            ':start_date' => $data['start_date'],
            ':end_date'   => $data['end_date'],
            ':status'     => !empty($data['status']) ? $data['status'] : 'Active',
        ]);
        return (int)$this->db_connection->lastInsertId();
    }

    /**
     * Updates an existing membership record.
     *
     * @param int $id Membership identifier.
     * @param array $data Updated values.
     * @return bool True on success, false on failure.
     */
    public function update($id, $data) {
        $sql_query = "
            UPDATE memberships
            SET member_id = :member_id,
                plan_id = :plan_id,
                start_date = :start_date,
                end_date = :end_date,
                status = :status
            WHERE id = :id
        ";
        $statement = $this->db_connection->prepare($sql_query);
        return $statement->execute([
            ':id'         => (int)$id,
            ':member_id'  => (int)$data['member_id'],
            ':plan_id'    => (int)$data['plan_id'],
            ':start_date' => $data['start_date'],
            ':end_date'   => $data['end_date'],
            ':status'     => $data['status'],
        ]);
    }

    /**
     * Deletes a membership record.
     *
     * @param int $id Membership identifier.
     * @return bool True on success, false on failure.
     */
    public function delete($id) {
        $sql_query = "DELETE FROM memberships WHERE id = :id";
        $statement = $this->db_connection->prepare($sql_query);
        return $statement->execute([':id' => (int)$id]);
    }

    /**
     * Retrieves active memberships that expire within a specific number of upcoming days.
     *
     * @param int $days_threshold Number of days ahead to inspect (default 7).
     * @return array List of memberships expiring soon.
     */
    public function getExpiringSoon($days_threshold = 7) {
        // Query memberships ending between today and the specified window
        $sql_query = "
            SELECT ms.*, m.first_name, m.last_name, m.email, m.phone, p.plan_name,
                   DATEDIFF(ms.end_date, CURDATE()) AS days_remaining
            FROM memberships ms
            JOIN members m ON ms.member_id = m.id
            JOIN membership_plans p ON ms.plan_id = p.id
            WHERE ms.status = 'Active'
              AND ms.end_date >= CURDATE()
              AND ms.end_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
            ORDER BY ms.end_date ASC
        ";
        $statement = $this->db_connection->prepare($sql_query);
        $statement->bindValue(':days', (int)$days_threshold, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    /**
     * Retrieves all membership plan types from membership_plans table.
     *
     * @return array List of membership plans.
     */
    public function getAllPlans() {
        $sql_query = "SELECT * FROM membership_plans ORDER BY price ASC";
        $statement = $this->db_connection->prepare($sql_query);
        $statement->execute();
        return $statement->fetchAll();
    }

    /**
     * Retrieves a single membership plan by ID.
     *
     * @param int $id Plan identifier.
     * @return array|false Plan record.
     */
    public function getPlanById($id) {
        $sql_query = "SELECT * FROM membership_plans WHERE id = :id LIMIT 1";
        $statement = $this->db_connection->prepare($sql_query);
        $statement->execute([':id' => (int)$id]);
        return $statement->fetch();
    }

    /**
     * Creates a new membership plan.
     *
     * @param array $data Plan data containing plan_name, duration_days, price.
     * @return int Created plan ID.
     */
    public function createPlan($data) {
        $sql_query = "
            INSERT INTO membership_plans (plan_name, duration_days, price)
            VALUES (:plan_name, :duration_days, :price)
        ";
        $statement = $this->db_connection->prepare($sql_query);
        $statement->execute([
            ':plan_name'     => $data['plan_name'],
            ':duration_days' => (int)$data['duration_days'],
            ':price'         => (float)$data['price'],
        ]);
        return (int)$this->db_connection->lastInsertId();
    }

    /**
     * Updates an existing membership plan.
     *
     * @param int $id Plan identifier.
     * @param array $data Updated plan values.
     * @return bool True on success, false on failure.
     */
    public function updatePlan($id, $data) {
        $sql_query = "
            UPDATE membership_plans
            SET plan_name = :plan_name,
                duration_days = :duration_days,
                price = :price
            WHERE id = :id
        ";
        $statement = $this->db_connection->prepare($sql_query);
        return $statement->execute([
            ':id'            => (int)$id,
            ':plan_name'     => $data['plan_name'],
            ':duration_days' => (int)$data['duration_days'],
            ':price'         => (float)$data['price'],
        ]);
    }

    /**
     * Deletes a membership plan.
     *
     * @param int $id Plan identifier.
     * @return bool True on success, false on failure.
     */
    public function deletePlan($id) {
        $sql_query = "DELETE FROM membership_plans WHERE id = :id";
        $statement = $this->db_connection->prepare($sql_query);
        return $statement->execute([':id' => (int)$id]);
    }

    /**
     * Assigns or renews a membership plan for a member.
     * Automatically calculates start_date and end_date based on plan duration.
     *
     * @param int $member_id Member identifier.
     * @param int $plan_id Membership plan identifier.
     * @param string|null $start_date Optional starting date (YYYY-MM-DD), defaults to today.
     * @return int Created membership ID.
     * @throws Exception If plan not found.
     */
    public function assignPlan($member_id, $plan_id, $start_date = null) {
        $plan = $this->getPlanById($plan_id);
        if (!$plan) {
            throw new Exception("Selected membership plan not found.");
        }

        $start_date = !empty($start_date) ? $start_date : date('Y-m-d');
        $duration_days = (int)$plan['duration_days'];
        $end_date = date('Y-m-d', strtotime("$start_date + $duration_days days"));

        // Insert new active membership period
        $membership_id = $this->create([
            'member_id'  => (int)$member_id,
            'plan_id'    => (int)$plan_id,
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'status'     => 'Active',
        ]);

        // Mark member profile status as Active (1)
        $member_statement = $this->db_connection->prepare("UPDATE members SET status_id = 1 WHERE id = :member_id");
        $member_statement->execute([':member_id' => (int)$member_id]);

        return $membership_id;
    }

    /**
     * Synchronizes and expires any active memberships whose end_date is in the past.
     *
     * @return void
     */
    public function syncExpiredMemberships() {
        // Mark elapsed memberships as Expired
        $this->db_connection->query("
            UPDATE memberships 
            SET status = 'Expired' 
            WHERE status = 'Active' AND end_date < CURDATE()
        ");

        // Mark member status to Expired (2) if their latest membership has expired
        $this->db_connection->query("
            UPDATE members m
            JOIN (
                SELECT member_id, MAX(end_date) AS max_end
                FROM memberships
                GROUP BY member_id
            ) latest_ms ON m.id = latest_ms.member_id
            SET m.status_id = 2
            WHERE latest_ms.max_end < CURDATE() AND m.status_id = 1
        ");
    }
}
