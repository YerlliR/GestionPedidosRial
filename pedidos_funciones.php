<?php
// Order management functions
function obtenerPedidosCliente($pdo, $cliente_id) {
    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE cliente_id = ? ORDER BY fecha_creacion DESC");
    $stmt->execute([$cliente_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerPedido($pdo, $pedido_id) {
    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
    $stmt->execute([$pedido_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function obtenerDetallesPedido($pdo, $pedido_id) {
    $stmt = $pdo->prepare("
        SELECT dp.*, p.nombre as producto_nombre, p.precio 
        FROM detalles_pedido dp
        JOIN productos p ON dp.producto_id = p.id
        WHERE dp.pedido_id = ?
    ");
    $stmt->execute([$pedido_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function crearPedido($pdo, $cliente_id) {
    $stmt = $pdo->prepare("INSERT INTO pedidos (cliente_id, estado, fecha_creacion) VALUES (?, 'pendiente', NOW())");
    $stmt->execute([$cliente_id]);
    return $pdo->lastInsertId();
}

function actualizarDetallesPedido($pdo, $pedido_id, $productos_cantidades) {
    // First delete existing details
    $stmt = $pdo->prepare("DELETE FROM detalles_pedido WHERE pedido_id = ?");
    $stmt->execute([$pedido_id]);
    
    // Then insert new details
    $stmt = $pdo->prepare("INSERT INTO detalles_pedido (pedido_id, producto_id, cantidad) VALUES (?, ?, ?)");
    
    foreach ($productos_cantidades as $producto_id => $cantidad) {
        if (floatval($cantidad) > 0) {
            $stmt->execute([$pedido_id, $producto_id, $cantidad]);
        }
    }
    
    // Update the order's update date
    $stmt = $pdo->prepare("UPDATE pedidos SET fecha_actualizacion = NOW() WHERE id = ?");
    $stmt->execute([$pedido_id]);
    
    return true;
}

function calcularTotalPedido($pdo, $pedido_id) {
    $stmt = $pdo->prepare("
        SELECT SUM(dp.cantidad * p.precio) as total
        FROM detalles_pedido dp
        JOIN productos p ON dp.producto_id = p.id
        WHERE dp.pedido_id = ?
    ");
    $stmt->execute([$pedido_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?: 0;
}



// Añadir esta función a pedidos_funciones.php

/**
 * Función mejorada para editar un pedido existente
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param int $pedido_id ID del pedido a editar
 * @param array $productos_cantidades Array de ID de producto => cantidad
 * @return bool Resultado de la operación
 */
function editarPedido($pdo, $pedido_id, $productos_cantidades) {
    try {
        // Comprobar si el pedido existe
        $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
        $stmt->execute([$pedido_id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pedido) {
            error_log("Pedido no encontrado: $pedido_id");
            return false;
        }
        
        // Verificar si es posible modificar este pedido (no completado)
        if ($pedido['estado'] == 'completado') {
            error_log("No se puede editar un pedido completado: $pedido_id");
            return false; // No se puede editar un pedido completado
        }
        
        // Iniciar una transacción para asegurar consistencia
        $pdo->beginTransaction();
        
        // Obtener los detalles actuales del pedido para compararlos
        $stmt = $pdo->prepare("
            SELECT dp.*, p.nombre as producto_nombre
            FROM detalles_pedido dp
            JOIN productos p ON dp.producto_id = p.id
            WHERE dp.pedido_id = ?
        ");
        $stmt->execute([$pedido_id]);
        $detalles_actuales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Crear un mapa de los detalles actuales para facilitar la comparación
        $detalles_map = [];
        foreach ($detalles_actuales as $detalle) {
            $detalles_map[$detalle['producto_id']] = $detalle;
        }
        
        // Para cada producto en la nueva lista de productos/cantidades
        foreach ($productos_cantidades as $producto_id => $cantidad) {
            // Convertir a float para asegurar precisión en comparaciones
            $cantidad = floatval($cantidad);
            
            // Si el producto ya está en el pedido
            if (isset($detalles_map[$producto_id])) {
                $detalle_actual = $detalles_map[$producto_id];
                $cantidad_actual_entregada = floatval($detalle_actual['cantidad_entregada']);
                
                // Si la cantidad es 0, eliminar el producto del pedido si aún no se ha entregado nada
                if ($cantidad == 0 || $cantidad < 0.001) { // Considerar 0 o valores muy pequeños como 0
                    if ($cantidad_actual_entregada == 0) {
                        // Eliminar el producto si no se ha entregado nada
                        $stmt = $pdo->prepare("DELETE FROM detalles_pedido WHERE id = ?");
                        $stmt->execute([$detalle_actual['id']]);
                    } else {
                        // No se puede eliminar si ya se entregó algo
                        error_log("No se puede eliminar un producto con entregas parciales: " . $detalle_actual['id']);
                        $pdo->rollBack();
                        return false;
                    }
                }
                // Si la cantidad es mayor a 0, actualizar la cantidad
                else {
                    // No permitir cambiar a una cantidad menor que lo ya entregado
                    if (round($cantidad, 3) < round($cantidad_actual_entregada, 3)) {
                        error_log("No se puede reducir cantidad por debajo de lo entregado: " . $detalle_actual['id']);
                        $pdo->rollBack();
                        return false;
                    }
                    
                    // Actualizar la cantidad y verificar si está completado basado en cantidades exactas
                    $stmt = $pdo->prepare("
                        UPDATE detalles_pedido
                        SET cantidad = ?,
                            entregado = CASE WHEN ROUND(cantidad_entregada, 3) >= ROUND(?, 3) THEN 1 ELSE 0 END
                        WHERE id = ?
                    ");
                    $stmt->execute([$cantidad, $cantidad, $detalle_actual['id']]);
                }
            }
            // Si el producto no está en el pedido y la cantidad es mayor a 0, añadir
            else if ($cantidad > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO detalles_pedido (pedido_id, producto_id, cantidad, cantidad_entregada, entregado)
                    VALUES (?, ?, ?, 0, 0)
                ");
                $stmt->execute([$pedido_id, $producto_id, $cantidad]);
            }
        }
        
        // Actualizar la fecha de actualización del pedido
        $stmt = $pdo->prepare("UPDATE pedidos SET fecha_actualizacion = NOW() WHERE id = ?");
        $stmt->execute([$pedido_id]);
        
        // Actualizar el estado del pedido
        actualizarEstadoPedido($pdo, $pedido_id);
        
        // Confirmar la transacción
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        // Si hay algún error, deshacer los cambios
        $pdo->rollBack();
        error_log("Error al editar pedido: " . $e->getMessage());
        return false;
    }
}
?>