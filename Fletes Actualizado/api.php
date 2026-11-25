<?php
// ==========================================
// 1. CONFIGURACIÓN DE CORS Y CABECERAS HTTP
// ==========================================
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ==========================================
// 2. CONEXIÓN A LA BASE DE DATOS (PDO)
// ==========================================
function get_db_connection() {
    try {
        $pdo = new PDO("sqlite:" . __DIR__ . "/database.sqlite");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("PRAGMA foreign_keys = ON;");

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

        return $pdo;
    } catch (PDOException $e) {
        responder(500, ["error" => "Error de conexión a la base de datos: " . $e->getMessage()]);
        exit();
    }
}

function responder($status_code, $data) {
    http_response_code($status_code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

// ==========================================
// 3. CLIENTES
// ==========================================
function get_clientes($pdo, $id = null) {
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $cliente = $stmt->fetch();
        $cliente ? responder(200, $cliente) : responder(404, ["mensaje" => "Cliente no encontrado"]);
    } else {
        $stmt = $pdo->query("SELECT * FROM clientes ORDER BY id DESC");
        responder(200, $stmt->fetchAll());
    }
}

function create_cliente($pdo, $data) {
    if (empty($data['nombre'])) {
        responder(400, ["error" => "El nombre del cliente es obligatorio."]);
        return;
    }
    $stmt = $pdo->prepare("INSERT INTO clientes (nombre, telefono, saldo_pendiente) VALUES (:nombre, :telefono, :saldo_pendiente)");
    $stmt->execute([
        ':nombre' => $data['nombre'],
        ':telefono' => $data['telefono'] ?? '',
        ':saldo_pendiente' => $data['saldo_pendiente'] ?? 0.0
    ]);
    responder(201, ["mensaje" => "Cliente creado con éxito", "id" => $pdo->lastInsertId()]);
}

function update_cliente($pdo, $id, $data) {
    if (!$id) {
        responder(400, ["error" => "Se requiere el parámetro 'id'."]);
        return;
    }
    $stmt = $pdo->prepare("UPDATE clientes SET nombre = :nombre, telefono = :telefono, saldo_pendiente = :saldo_pendiente WHERE id = :id");
    $stmt->execute([
        ':nombre' => $data['nombre'],
        ':telefono' => $data['telefono'] ?? '',
        ':saldo_pendiente' => $data['saldo_pendiente'] ?? 0.0,
        ':id' => $id
    ]);
    $stmt->rowCount() > 0
        ? responder(200, ["mensaje" => "Cliente actualizado con éxito"])
        : responder(404, ["mensaje" => "Cliente no encontrado o sin cambios"]);
}

function delete_cliente($pdo, $id) {
    if (!$id) {
        responder(400, ["error" => "Se requiere el parámetro 'id'."]);
        return;
    }
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $stmt->rowCount() > 0
        ? responder(200, ["mensaje" => "Cliente eliminado con éxito"])
        : responder(404, ["mensaje" => "Cliente no encontrado"]);
}

// ==========================================
// 4. CHOFERES
// ==========================================
function get_choferes($pdo, $id = null) {
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM choferes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $chofer = $stmt->fetch();
        $chofer ? responder(200, $chofer) : responder(404, ["mensaje" => "Chofer no encontrado"]);
    } else {
        $stmt = $pdo->query("SELECT * FROM choferes ORDER BY id DESC");
        responder(200, $stmt->fetchAll());
    }
}

function create_chofer($pdo, $data) {
    if (empty($data['nombre']) || empty($data['licencia'])) {
        responder(400, ["error" => "Se requiere nombre y licencia."]);
        return;
    }
    $stmt = $pdo->prepare("INSERT INTO choferes (nombre, licencia, estatus) VALUES (:nombre, :licencia, :estatus)");
    $stmt->execute([
        ':nombre' => $data['nombre'],
        ':licencia' => $data['licencia'],
        ':estatus' => $data['estatus'] ?? 'Disponible'
    ]);
    responder(201, ["mensaje" => "Chofer creado con éxito", "id" => $pdo->lastInsertId()]);
}

function update_chofer($pdo, $id, $data) {
    if (!$id) {
        responder(400, ["error" => "Se requiere el parámetro 'id'."]);
        return;
    }
    $stmt = $pdo->prepare("UPDATE choferes SET nombre = :nombre, licencia = :licencia, estatus = :estatus WHERE id = :id");
    $stmt->execute([
        ':nombre' => $data['nombre'],
        ':licencia' => $data['licencia'],
        ':estatus' => $data['estatus'] ?? 'Disponible',
        ':id' => $id
    ]);
    $stmt->rowCount() > 0
        ? responder(200, ["mensaje" => "Chofer actualizado con éxito"])
        : responder(404, ["mensaje" => "Chofer no encontrado o sin cambios"]);
}

function delete_chofer($pdo, $id) {
    if (!$id) {
        responder(400, ["error" => "Se requiere el parámetro 'id'."]);
        return;
    }
    $stmt = $pdo->prepare("DELETE FROM choferes WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $stmt->rowCount() > 0
        ? responder(200, ["mensaje" => "Chofer eliminado con éxito"])
        : responder(404, ["mensaje" => "Chofer no encontrado"]);
}

// ==========================================
// 5. UNIDADES
// ==========================================
function get_unidades($pdo, $id = null) {
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM unidades WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $unidad = $stmt->fetch();
        $unidad ? responder(200, $unidad) : responder(404, ["mensaje" => "Unidad no encontrada"]);
    } else {
        $stmt = $pdo->query("SELECT * FROM unidades ORDER BY id DESC");
        responder(200, $stmt->fetchAll());
    }
}

function create_unidad($pdo, $data) {
    if (empty($data['placas'])) {
        responder(400, ["error" => "Se requieren las placas de la unidad."]);
        return;
    }
    $stmt = $pdo->prepare("INSERT INTO unidades (placas, modelo, estatus) VALUES (:placas, :modelo, :estatus)");
    $stmt->execute([
        ':placas' => $data['placas'],
        ':modelo' => $data['modelo'] ?? '',
        ':estatus' => $data['estatus'] ?? 'Disponible'
    ]);
    responder(201, ["mensaje" => "Unidad creada con éxito", "id" => $pdo->lastInsertId()]);
}

function update_unidad($pdo, $id, $data) {
    if (!$id) {
        responder(400, ["error" => "Se requiere el parámetro 'id'."]);
        return;
    }
    $stmt = $pdo->prepare("UPDATE unidades SET placas = :placas, modelo = :modelo, estatus = :estatus WHERE id = :id");
    $stmt->execute([
        ':placas' => $data['placas'],
        ':modelo' => $data['modelo'] ?? '',
        ':estatus' => $data['estatus'] ?? 'Disponible',
        ':id' => $id
    ]);
    $stmt->rowCount() > 0
        ? responder(200, ["mensaje" => "Unidad actualizada con éxito"])
        : responder(404, ["mensaje" => "Unidad no encontrada o sin cambios"]);
}

function delete_unidad($pdo, $id) {
    if (!$id) {
        responder(400, ["error" => "Se requiere el parámetro 'id'."]);
        return;
    }
    $stmt = $pdo->prepare("DELETE FROM unidades WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $stmt->rowCount() > 0
        ? responder(200, ["mensaje" => "Unidad eliminada con éxito"])
        : responder(404, ["mensaje" => "Unidad no encontrada"]);
}

// ==========================================
// 6. FLETES
// ==========================================
function get_fletes($pdo, $id = null) {
    $sql = "SELECT f.*,
                   c.nombre AS cliente_nombre,
                   ch.nombre AS chofer_nombre,
                   u.placas AS unidad_placas,
                   u.modelo AS unidad_modelo
            FROM fletes f
            LEFT JOIN clientes c ON f.cliente_id = c.id
            LEFT JOIN choferes ch ON f.chofer_id = ch.id
            LEFT JOIN unidades u ON f.unidad_id = u.id";

    if ($id) {
        $stmt = $pdo->prepare($sql . " WHERE f.id = :id");
        $stmt->execute([':id' => $id]);
        $flete = $stmt->fetch();
        $flete ? responder(200, $flete) : responder(404, ["mensaje" => "Flete no encontrado"]);
    } else {
        $stmt = $pdo->query($sql . " ORDER BY f.id DESC");
        responder(200, $stmt->fetchAll());
    }
}

function create_flete($pdo, $data) {
    if (empty($data['origen']) || empty($data['destino']) || !isset($data['costo'])) {
        responder(400, ["error" => "Se requiere origen, destino y costo."]);
        return;
    }

    $estatus = $data['estatus'] ?? 'Pendiente';
    $clienteId = !empty($data['cliente_id']) ? (int)$data['cliente_id'] : null;
    $choferId = !empty($data['chofer_id']) ? (int)$data['chofer_id'] : null;
    $unidadId = !empty($data['unidad_id']) ? (int)$data['unidad_id'] : null;
    $costo = (float)$data['costo'];

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO fletes (cliente_id, chofer_id, unidad_id, origen, destino, costo, estatus)
                              VALUES (:cliente_id, :chofer_id, :unidad_id, :origen, :destino, :costo, :estatus)");
        $stmt->execute([
            ':cliente_id' => $clienteId,
            ':chofer_id' => $choferId,
            ':unidad_id' => $unidadId,
            ':origen' => $data['origen'],
            ':destino' => $data['destino'],
            ':costo' => $costo,
            ':estatus' => $estatus
        ]);

        if ($estatus === 'En Tránsito') {
            if ($choferId) {
                $pdo->prepare("UPDATE choferes SET estatus = 'En Ruta' WHERE id = :id")->execute([':id' => $choferId]);
            }
            if ($unidadId) {
                $pdo->prepare("UPDATE unidades SET estatus = 'En Ruta' WHERE id = :id")->execute([':id' => $unidadId]);
            }
        }

        if ($estatus === 'Entregado' && $clienteId) {
            $pdo->prepare("UPDATE clientes SET saldo_pendiente = saldo_pendiente + :costo WHERE id = :id")
                ->execute([':costo' => $costo, ':id' => $clienteId]);
        }

        $idCreado = $pdo->lastInsertId();
        $pdo->commit();
        responder(201, ["mensaje" => "Flete creado con éxito", "id" => $idCreado]);
    } catch (Exception $e) {
        $pdo->rollBack();
        responder(500, ["error" => "Error al crear flete: " . $e->getMessage()]);
    }
}

function update_flete($pdo, $id, $data) {
    if (!$id) {
        responder(400, ["error" => "Se requiere el parámetro 'id'."]);
        return;
    }

    $stmtActual = $pdo->prepare("SELECT * FROM fletes WHERE id = :id");
    $stmtActual->execute([':id' => $id]);
    $actual = $stmtActual->fetch();

    if (!$actual) {
        responder(404, ["mensaje" => "Flete no encontrado"]);
        return;
    }

    $nuevoEstatus = $data['estatus'] ?? $actual['estatus'];
    $origen = $data['origen'] ?? $actual['origen'];
    $destino = $data['destino'] ?? $actual['destino'];
    $costo = isset($data['costo']) ? (float)$data['costo'] : (float)$actual['costo'];
    $clienteId = array_key_exists('cliente_id', $data) ? ($data['cliente_id'] ?: null) : $actual['cliente_id'];
    $choferId = array_key_exists('chofer_id', $data) ? ($data['chofer_id'] ?: null) : $actual['chofer_id'];
    $unidadId = array_key_exists('unidad_id', $data) ? ($data['unidad_id'] ?: null) : $actual['unidad_id'];

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE fletes
                              SET cliente_id = :cliente_id, chofer_id = :chofer_id, unidad_id = :unidad_id,
                                  origen = :origen, destino = :destino, costo = :costo, estatus = :estatus
                              WHERE id = :id");
        $stmt->execute([
            ':cliente_id' => $clienteId,
            ':chofer_id' => $choferId,
            ':unidad_id' => $unidadId,
            ':origen' => $origen,
            ':destino' => $destino,
            ':costo' => $costo,
            ':estatus' => $nuevoEstatus,
            ':id' => $id
        ]);

        // Sincronizar disponibilidad de chofer y unidad
        if ($nuevoEstatus === 'En Tránsito') {
            if ($choferId) {
                $pdo->prepare("UPDATE choferes SET estatus = 'En Ruta' WHERE id = :id")->execute([':id' => $choferId]);
            }
            if ($unidadId) {
                $pdo->prepare("UPDATE unidades SET estatus = 'En Ruta' WHERE id = :id")->execute([':id' => $unidadId]);
            }
        } elseif (in_array($nuevoEstatus, ['Entregado', 'Pagado'])) {
            if ($choferId) {
                $pdo->prepare("UPDATE choferes SET estatus = 'Disponible' WHERE id = :id")->execute([':id' => $choferId]);
            }
            if ($unidadId) {
                $pdo->prepare("UPDATE unidades SET estatus = 'Disponible' WHERE id = :id")->execute([':id' => $unidadId]);
            }
        }

        // Ajustar saldo pendiente del cliente al pasar a Entregado o Pagado
        $estatusAnterior = $actual['estatus'];
        if ($estatusAnterior !== 'Entregado' && $nuevoEstatus === 'Entregado' && $clienteId) {
            $pdo->prepare("UPDATE clientes SET saldo_pendiente = saldo_pendiente + :costo WHERE id = :id")
                ->execute([':costo' => $costo, ':id' => $clienteId]);
        } elseif ($estatusAnterior === 'Entregado' && $nuevoEstatus === 'Pagado' && $clienteId) {
            $pdo->prepare("UPDATE clientes SET saldo_pendiente = MAX(0, saldo_pendiente - :costo) WHERE id = :id")
                ->execute([':costo' => $actual['costo'], ':id' => $clienteId]);
        } elseif ($estatusAnterior !== 'Entregado' && $estatusAnterior !== 'Pagado' && $nuevoEstatus === 'Pagado') {
            // Si pasó directo a Pagado sin haber sumado saldo, no se altera saldo pendiente
        }

        $pdo->commit();
        responder(200, ["mensaje" => "Flete actualizado con éxito"]);
    } catch (Exception $e) {
        $pdo->rollBack();
        responder(500, ["error" => "Error al actualizar flete: " . $e->getMessage()]);
    }
}

function delete_flete($pdo, $id) {
    if (!$id) {
        responder(400, ["error" => "Se requiere el parámetro 'id'."]);
        return;
    }
    $stmt = $pdo->prepare("DELETE FROM fletes WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $stmt->rowCount() > 0
        ? responder(200, ["mensaje" => "Flete eliminado con éxito"])
        : responder(404, ["mensaje" => "Flete no encontrado"]);
}

// ==========================================
// 7. ENRUTADOR PRINCIPAL
// ==========================================
$pdo = get_db_connection();
$method = $_SERVER['REQUEST_METHOD'];
$recurso = $_GET['recurso'] ?? 'clientes';
$id = $_GET['id'] ?? null;
$input = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($recurso) {
    case 'clientes':
        if ($method === 'GET') get_clientes($pdo, $id);
        elseif ($method === 'POST') create_cliente($pdo, $input);
        elseif ($method === 'PUT') update_cliente($pdo, $id, $input);
        elseif ($method === 'DELETE') delete_cliente($pdo, $id);
        else responder(405, ["error" => "Método HTTP no permitido"]);
        break;

    case 'choferes':
        if ($method === 'GET') get_choferes($pdo, $id);
        elseif ($method === 'POST') create_chofer($pdo, $input);
        elseif ($method === 'PUT') update_chofer($pdo, $id, $input);
        elseif ($method === 'DELETE') delete_chofer($pdo, $id);
        else responder(405, ["error" => "Método HTTP no permitido"]);
        break;

    case 'unidades':
        if ($method === 'GET') get_unidades($pdo, $id);
        elseif ($method === 'POST') create_unidad($pdo, $input);
        elseif ($method === 'PUT') update_unidad($pdo, $id, $input);
        elseif ($method === 'DELETE') delete_unidad($pdo, $id);
        else responder(405, ["error" => "Método HTTP no permitido"]);
        break;

    case 'fletes':
        if ($method === 'GET') get_fletes($pdo, $id);
        elseif ($method === 'POST') create_flete($pdo, $input);
        elseif ($method === 'PUT') update_flete($pdo, $id, $input);
        elseif ($method === 'DELETE') delete_flete($pdo, $id);
        else responder(405, ["error" => "Método HTTP no permitido"]);
        break;

    default:
        responder(404, ["error" => "Recurso no encontrado"]);
        break;
}
