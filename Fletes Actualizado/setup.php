<?php
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');

    $pdo->beginTransaction();
    $pdo->exec("CREATE TABLE IF NOT EXISTS clientes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        telefono TEXT,
        saldo_pendiente REAL DEFAULT 0.0
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS choferes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        licencia TEXT NOT NULL,
        estatus TEXT DEFAULT 'Disponible'
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS unidades (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        placas TEXT NOT NULL,
        modelo TEXT,
        estatus TEXT DEFAULT 'Disponible'
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS fletes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        cliente_id INTEGER,
        chofer_id INTEGER,
        unidad_id INTEGER,
        origen TEXT NOT NULL,
        destino TEXT NOT NULL,
        costo REAL NOT NULL,
        estatus TEXT DEFAULT 'Pendiente',
        fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(cliente_id) REFERENCES clientes(id),
        FOREIGN KEY(chofer_id) REFERENCES choferes(id),
        FOREIGN KEY(unidad_id) REFERENCES unidades(id)
    )");

    $base_vacia = (int)$pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn() === 0
        && (int)$pdo->query('SELECT COUNT(*) FROM choferes')->fetchColumn() === 0
        && (int)$pdo->query('SELECT COUNT(*) FROM unidades')->fetchColumn() === 0
        && (int)$pdo->query('SELECT COUNT(*) FROM fletes')->fetchColumn() === 0;

    if ((int)$pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn() === 0) {
        $pdo->exec("INSERT INTO clientes (nombre, telefono, saldo_pendiente) VALUES ('cliente demo', '', 0)");
    }
    if ((int)$pdo->query('SELECT COUNT(*) FROM choferes')->fetchColumn() === 0) {
        $pdo->exec("INSERT INTO choferes (nombre, licencia, estatus) VALUES ('chofer demo', 'licencia demo', 'Disponible')");
    }
    if ((int)$pdo->query('SELECT COUNT(*) FROM unidades')->fetchColumn() === 0) {
        $pdo->exec("INSERT INTO unidades (placas, modelo, estatus) VALUES ('demo', 'unidad demo', 'Disponible')");
    }
    if ($base_vacia) {
        $cliente_id = (int)$pdo->query('SELECT id FROM clientes ORDER BY id LIMIT 1')->fetchColumn();
        $chofer_id = (int)$pdo->query('SELECT id FROM choferes ORDER BY id LIMIT 1')->fetchColumn();
        $unidad_id = (int)$pdo->query('SELECT id FROM unidades ORDER BY id LIMIT 1')->fetchColumn();
        $stmt = $pdo->prepare("INSERT INTO fletes (cliente_id, chofer_id, unidad_id, origen, destino, costo, estatus)
                               VALUES (:cliente_id, :chofer_id, :unidad_id, 'origen demo', 'destino demo', 1000, 'Pendiente')");
        $stmt->execute([
            ':cliente_id' => $cliente_id,
            ':chofer_id' => $chofer_id,
            ':unidad_id' => $unidad_id
        ]);
    }

    $pdo->commit();
    echo json_encode(['mensaje' => 'base de datos preparada'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'no se pudo preparar la base de datos'], JSON_UNESCAPED_UNICODE);
}
