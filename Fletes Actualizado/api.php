<?php
// ==========================================
// 1. CONFIGURACIÓN DE CORS Y CABECERAS HTTP
// ==========================================
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejo de la petición Preflight (OPTIONS)
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

        // Crear la tabla si no existe
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
            estatus TEXT DEFAULT 'Disponible' -- Disponible, En Ruta, Inactivo
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS unidades (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            placas TEXT NOT NULL,
            modelo TEXT,
            estatus TEXT DEFAULT 'Disponible' -- Disponible, En Ruta, Mantenimiento
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS fletes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            cliente_id INTEGER,
            chofer_id INTEGER,
            unidad_id INTEGER,
            origen TEXT NOT NULL,
            destino TEXT NOT NULL,
            costo REAL NOT NULL,
            estatus TEXT DEFAULT 'Pendiente', -- Pendiente, En Tránsito, Entregado, Pagado
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

// Función auxiliar para responder en formato JSON
function responder($status_code, $data) {
    http_response_code($status_code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

// ==========================================
// 3. FUNCIONES PARA CADA MÉTODO HTTP
// ==========================================

/**
 * Maneja las peticiones GET (Obtener todos o uno por ID)
 */
function get_clientes($pdo, $id = null) {
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $clientes = $stmt->fetch();

        if ($clientes) {
            responder(200, $clientes);
        } else {
            responder(404, ["mensaje" => "Cliente no encontrado"]);
        }
    } else {
        $stmt = $pdo->query("SELECT * FROM clientes");
        $clientes = $stmt->fetchAll();
        responder(200, $clientes);
    }
}

function get_choferes($pdo, $id = null) {
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM choferes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $choferes = $stmt->fetch();

        if ($choferes) {
            responder(200, $choferes);
        } else {
            responder(404, ["mensaje" => "Chofer no encontrado"]);
        }
    } else {
        $stmt = $pdo->query("SELECT * FROM choferes");
        $choferes = $stmt->fetchAll();
        responder(200, $choferes);
    }
}

function get_unidades($pdo, $id = null) {
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM unidades WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $unidades = $stmt->fetch();

        if ($unidades) {
            responder(200, $unidades);
        } else {
            responder(404, ["mensaje" => "Unidad no encontrada"]);
        }
    } else {
        $stmt = $pdo->query("SELECT * FROM unidades");
        $unidades = $stmt->fetchAll();
        responder(200, $unidades);
    }
}

/**
 * Maneja las peticiones POST (Crear nuevo clientes)
 */
function create_cliente($pdo, $data) {
    if (empty($data['nombre']) || !isset($data['telefono']) || !isset($data['saldo_pendiente'])) {
        responder(400, ["error" => "Datos incompletos. Se requiere nombre, telefono y saldo_pendiente."]);
        return;
    }

    $sql = "INSERT INTO clientes (nombre, telefono, saldo_pendiente) VALUES (:nombre, :telefono, :saldo_pendiente)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $data['nombre'],
        ':telefono' => $data['telefono'],
        ':saldo_pendiente' => $data['saldo_pendiente']
    ]);

    responder(201, [
        "mensaje" => "Cliente creado con éxito",
        "id" => $pdo->lastInsertId()
    ]);
}

function create_choferes($pdo, $data) {
    if (empty($data['nombre']) || !isset($data['licencia']) || !isset($data['estatus'])) {
        responder(400, ["error" => "Datos incompletos. Se requiere nombre, licencia y estatus."]);
        return;
    }

    $sql = "INSERT INTO clientes (nombre, licencia, estatus) VALUES (:nombre, :licencia, :estatus)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $data['nombre'],
        ':licencia' => $data['licencia'],
        ':estatus' => $data['estatus']
    ]);

    responder(201, [
        "mensaje" => "Chofer creado con éxito",
        "id" => $pdo->lastInsertId()
    ]);
}

function create_unidades($pdo, $data) {
    if (empty($data['placas']) || !isset($data['modelo']) || !isset($data['estatus'])) {
        responder(400, ["error" => "Datos incompletos. Se requiere placas, modelo y estatus."]);
        return;
    }

    $sql = "INSERT INTO clientes (placas, modelo, estatus) VALUES (:placas, :modelo, :estatus)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':placas' => $data['placas'],
        ':modelo' => $data['modelo'],
        ':estatus' => $data['estatus']
    ]);

    responder(201, [
        "mensaje" => "Unidad creada con éxito",
        "id" => $pdo->lastInsertId()
    ]);
}

/**
 * Maneja las peticiones PUT (Actualizar cliente existente)
 */
function update_cliente($pdo, $id, $data) {
    if (!$id) {
        responder(400, ["error" => "Se requiere el parámetro 'id' en la URL."]);
        return;
    }

    if (empty($data['nombre']) || !isset($data['telefono']) || !isset($data['saldo_pendiente'])) {
        responder(400, ["error" => "Datos incompletos para actualizar."]);
        return;
    }

    $sql = "UPDATE fletes SET nombre = :nombre, telefono = :telefono, saldo_pendiente = :saldo_pendiente WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $data['nombre'],
        ':telefono' => $data['telefono'],
        ':saldo_pendiente' => $data['saldo_pendiente'],
        ':id' => $id
    ]);

    if ($stmt->rowCount() > 0) {
        responder(200, ["mensaje" => "Cliente actualizado con éxito"]);
    } else {
        responder(404, ["mensaje" => "Cliente no encontrado o sin cambios"]);
    }
}

function update_choferes($pdo, $id, $data) {
    if (!$id) {
        responder(400, ["error" => "Se requiere el parámetro 'id' en la URL."]);
        return;
    }

    if (empty($data['nombre']) || !isset($data['licencia']) || !isset($data['estatus'])) {
        responder(400, ["error" => "Datos incompletos para actualizar."]);
        return;
    }

    $sql = "UPDATE fletes SET nombre = :nombre, licencia = :licencia, estatus = :estatus WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $data['nombre'],
        ':licencia' => $data['licencia'],
        ':estatus' => $data['estatus'],
        ':id' => $id
    ]);

    if ($stmt->rowCount() > 0) {
        responder(200, ["mensaje" => "Chofer actualizado con éxito"]);
    } else {
        responder(404, ["mensaje" => "Chofer no encontrado o sin cambios"]);
    }
}

function update_unidades($pdo, $id, $data) {
    if (!$id) {
        responder(400, ["error" => "Se requiere el parámetro 'id' en la URL."]);
        return;
    }

    if (empty($data['placas']) || !isset($data['modelo']) || !isset($data['estatus'])) {
        responder(400, ["error" => "Datos incompletos para actualizar."]);
        return;
    }

    $sql = "UPDATE fletes SET placas = :placas, modelo = :modelo, estatus = :estatus WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':placas' => $data['placas'],
        ':modelo' => $data['modelo'],
        ':estatus' => $data['estatus'],
        ':id' => $id
    ]);

    if ($stmt->rowCount() > 0) {
        responder(200, ["mensaje" => "Unidad actualizada con éxito"]);
    } else {
        responder(404, ["mensaje" => "Unidad no encontrada o sin cambios"]);
    }
}


/**
 * Maneja las peticiones DELETE (Eliminar Clientes por ID)
 */
function delete_cliente($pdo, $id) {
    if (!$id) {
        responder(400, ["error" => "Se requiere el parámetro 'id' en la URL."]);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = :id");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        responder(200, ["mensaje" => "Cliente eliminado con éxito"]);
    } else {
        responder(404, ["mensaje" => "Cliente no encontrado"]);
    }
}

function delete_choferes($pdo, $id) {
    if (!$id) {
        responder(400, ["error" => "Se requiere el parámetro 'id' en la URL."]);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM choferes WHERE id = :id");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        responder(200, ["mensaje" => "Chofer eliminado con éxito"]);
    } else {
        responder(404, ["mensaje" => "Chofer no encontrado"]);
    }
}

function delete_unidades($pdo, $id) {
    if (!$id) {
        responder(400, ["error" => "Se requiere el parámetro 'id' en la URL."]);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM unidades WHERE id = :id");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        responder(200, ["mensaje" => "Unidad eliminada con éxito"]);
    } else {
        responder(404, ["mensaje" => "Unidad no encontrada"]);
    }
}

// ==========================================
// 4. ENRUTADOR Y CONTROLADOR PRINCIPAL
// ==========================================
$pdo = get_db_connection();
$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        get_clientes($pdo, $id);
        break;

    case 'POST':
        create_cliente($pdo, $input);
        break;

    case 'PUT':
        update_cliente($pdo, $id, $input);
        break;

    case 'DELETE':
        delete_cliente($pdo, $id);
        break;

    default:
        responder(451, ["error" => "Método HTTP no permitido"]);
        break;
}