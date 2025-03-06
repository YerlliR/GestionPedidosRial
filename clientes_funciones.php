<?php


function eliminarCliente($pdo, $id) {
    try {
        // Verificar si el cliente tiene pedidos
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE cliente_id = ?");
        $stmt->execute([$id]);
        $pedidos_count = $stmt->fetchColumn();
        
        if ($pedidos_count > 0) {
            // Cliente tiene pedidos, no se puede eliminar
            return ['success' => false, 'mensaje' => 'No se puede eliminar el cliente porque tiene pedidos asociados'];
        }
        
        // Realizar la eliminación si no tiene pedidos
        $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
        $resultado = $stmt->execute([$id]);
        
        if ($resultado) {
            return ['success' => true, 'mensaje' => 'Cliente eliminado con éxito'];
        } else {
            return ['success' => false, 'mensaje' => 'Error al eliminar el cliente'];
        }
    } catch (PDOException $e) {
        error_log("Error al eliminar cliente: " . $e->getMessage());
        return ['success' => false, 'mensaje' => 'Error en la base de datos al eliminar cliente'];
    }
}
// Client management functions
function obtenerClientes($pdo) {
    // Obtener clientes con su producto favorito
    $stmt = $pdo->query("
        SELECT c.*, 
               p.nombre as producto_favorito_nombre,
               p.id as producto_favorito_id
        FROM clientes c
        LEFT JOIN productos p ON c.producto_favorito_id = p.id
        ORDER BY c.nombre ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function agregarCliente($pdo, $nombre, $telefono, $email, $producto_favorito_id = null) {
    $stmt = $pdo->prepare("INSERT INTO clientes (nombre, telefono, email, producto_favorito_id) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$nombre, $telefono, $email, $producto_favorito_id]);
}

function actualizarCliente($pdo, $id, $nombre, $telefono, $email, $producto_favorito_id = null) {
    $stmt = $pdo->prepare("UPDATE clientes SET nombre = ?, telefono = ?, email = ?, producto_favorito_id = ? WHERE id = ?");
    return $stmt->execute([$nombre, $telefono, $email, $producto_favorito_id, $id]);
}

// Función para obtener la última información de pedido del producto favorito
function obtenerInfoProductoFavorito($pdo, $cliente_id, $producto_id) {
    if (!$producto_id) {
        return ['cantidad_solicitada' => 0, 'cantidad_entregada' => 0];
    }
    
    // Obtener el último pedido del cliente que contiene el producto favorito
    $stmt = $pdo->prepare("
        SELECT dp.*, p.fecha_creacion
        FROM detalles_pedido dp
        JOIN pedidos p ON dp.pedido_id = p.id
        WHERE p.cliente_id = ? AND dp.producto_id = ?
        ORDER BY p.fecha_creacion DESC
        LIMIT 1
    ");
    
    $stmt->execute([$cliente_id, $producto_id]);
    $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$detalle) {
        return ['cantidad_solicitada' => 0, 'cantidad_entregada' => 0];
    }
    
    return [
        'cantidad_solicitada' => $detalle['cantidad'],
        'cantidad_entregada' => $detalle['cantidad_entregada']
    ];
}
?>