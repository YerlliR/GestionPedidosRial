<?php
// Product management functions
function obtenerProductos($pdo) {
    $stmt = $pdo->query("SELECT * FROM productos ORDER BY nombre ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function agregarProducto($pdo, $nombre, $descripcion, $precio) {
    try {
        $stmt = $pdo->prepare("INSERT INTO productos (nombre, descripcion, precio) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $descripcion, $precio]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error al agregar producto: " . $e->getMessage());
        return false;
    }
}

function editarProducto($pdo, $id, $nombre, $descripcion, $precio) {
    try {
        $stmt = $pdo->prepare("UPDATE productos SET nombre = ?, descripcion = ?, precio = ? WHERE id = ?");
        return $stmt->execute([$nombre, $descripcion, $precio, $id]);
    } catch (PDOException $e) {
        error_log("Error al editar producto: " . $e->getMessage());
        return false;
    }
}

function eliminarProducto($pdo, $id) {
    try {
        // First check if the product is used in any order
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM detalles_pedido WHERE producto_id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            // Product is in use, cannot delete
            return false;
        }
        
        // If not in use, delete it
        $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Error al eliminar producto: " . $e->getMessage());
        return false;
    }
}
?>