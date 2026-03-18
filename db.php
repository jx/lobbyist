<?php
// ============================================================
//  db.php  –  SQLite init + shared helpers
// ============================================================
require_once __DIR__ . '/config.php';

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        init_db($pdo);
    }
    return $pdo;
}

function init_db(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS employees (
            display_order  INTEGER PRIMARY KEY,
            name           TEXT    NOT NULL DEFAULT '',
            photo          TEXT    NOT NULL DEFAULT '',
            phone          TEXT    NOT NULL DEFAULT '',
            email          TEXT    NOT NULL DEFAULT '',
            active         INTEGER NOT NULL DEFAULT 1
        );
    ");
}

function get_active_employees(): array {
    $pdo = get_db();
    $stmt = $pdo->query(
        'SELECT * FROM employees WHERE active = 1 ORDER BY display_order ASC'
    );
    return $stmt->fetchAll();
}

function get_all_employees(): array {
    $pdo = get_db();
    $stmt = $pdo->query('SELECT * FROM employees ORDER BY display_order ASC');
    return $stmt->fetchAll();
}

function get_employee(int $order): ?array {
    $pdo  = get_db();
    $stmt = $pdo->prepare('SELECT * FROM employees WHERE display_order = ?');
    $stmt->execute([$order]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function upsert_employee(array $data): void {
    $pdo  = get_db();
    $stmt = $pdo->prepare("
        INSERT INTO employees (display_order, name, photo, phone, email, active)
        VALUES (:order, :name, :photo, :phone, :email, :active)
        ON CONFLICT(display_order) DO UPDATE SET
            name  = excluded.name,
            photo = excluded.photo,
            phone = excluded.phone,
            email = excluded.email,
            active = excluded.active
    ");
    $stmt->execute([
        ':order'  => (int)$data['display_order'],
        ':name'   => $data['name']  ?? '',
        ':photo'  => $data['photo'] ?? '',
        ':phone'  => $data['phone'] ?? '',
        ':email'  => $data['email'] ?? '',
        ':active' => isset($data['active']) ? 1 : 0,
    ]);
}

function delete_employee(int $order): void {
    $pdo  = get_db();
    $stmt = $pdo->prepare('DELETE FROM employees WHERE display_order = ?');
    $stmt->execute([$order]);
}
