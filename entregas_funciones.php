<?php
/**
 * Funciones para gestionar las entregas parciales - Versión mejorada
 */

/**
 * Registra una entrega parcial de un producto
 *
 * @param PDO $pdo Conexión a la base de datos
 * @param int $detalle_pedido_id ID del detalle de pedido
 * @param float $cantidad Cantidad a entregar
 * @param string $notas Notas adicionales
 * @return bool Resultado de la operación
 */
function registrarEntregaParcial($pdo, $detalle_pedido_id, $cantidad, $notas = '') {
    // Get the details of the product in the order
    $stmt = $pdo->prepare("
        SELECT dp.*, p.nombre as producto_nombre, p.id as producto_id, ped.id as pedido_id
        FROM detalles_pedido dp
        JOIN productos p ON dp.producto_id = p.id
        JOIN pedidos ped ON dp.pedido_id = ped.id
        WHERE dp.id = ?
    ");
    $stmt->execute([$detalle_pedido_id]);
    $detalle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$detalle) {
        error_log("No se encontró el detalle de pedido con ID: $detalle_pedido_id");
        return false;
    }

    // Convertir valores a float para comparaciones precisas
    $cantidad = floatval($cantidad);
    $cantidad_actual = floatval($detalle['cantidad_entregada']);
    $cantidad_total = floatval($detalle['cantidad']);
    
    // Validar que la cantidad sea positiva
    if ($cantidad <= 0) {
        error_log("La cantidad a entregar debe ser mayor que cero");
        return false;
    }

    // Verificar que la cantidad entregada no exceda la solicitada
    // Usar round para evitar problemas de precisión de float
    if (round($cantidad_actual + $cantidad, 3) > round($cantidad_total, 3)) {
        error_log("La cantidad entregada excede la cantidad solicitada");
        return false;
    }

    // Start a transaction to ensure consistency
    $pdo->beginTransaction();

    try {
        // Register the delivery in the history
        $stmt = $pdo->prepare("
            INSERT INTO historial_entregas 
            (detalle_pedido_id, cantidad, notas) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$detalle_pedido_id, $cantidad, $notas]);

        // Update the delivered quantity
        $nueva_cantidad = $cantidad_actual + $cantidad;
        $entregado = (round($nueva_cantidad, 3) >= round($cantidad_total, 3)) ? 1 : 0;
        
        $stmt = $pdo->prepare("
            UPDATE detalles_pedido 
            SET cantidad_entregada = ?, 
                fecha_ultima_entrega = NOW(),
                entregado = ?
            WHERE id = ?
        ");
        $stmt->execute([$nueva_cantidad, $entregado, $detalle_pedido_id]);

        // Update the order status
        actualizarEstadoPedido($pdo, $detalle['pedido_id']);

        // Confirm the transaction
        $pdo->commit();

        return true;
    } catch (Exception $e) {
        // If there's an error, rollback the transaction
        $pdo->rollBack();
        error_log("Error al registrar entrega parcial: " . $e->getMessage());
        return false;
    }
}

/**
 * Corrige una entrega parcial de un producto
 *
 * @param PDO $pdo Conexión a la base de datos
 * @param int $detalle_pedido_id ID del detalle de pedido
 * @param float $nueva_cantidad Nueva cantidad total entregada
 * @param string $notas Notas adicionales
 * @return bool Resultado de la operación
 */
function corregirEntregaParcial($pdo, $detalle_pedido_id, $nueva_cantidad, $notas = '') {
    // Get the details of the product in the order
    $stmt = $pdo->prepare("
        SELECT dp.*, p.nombre as producto_nombre, p.id as producto_id, ped.id as pedido_id
        FROM detalles_pedido dp
        JOIN productos p ON dp.producto_id = p.id
        JOIN pedidos ped ON dp.pedido_id = ped.id
        WHERE dp.id = ?
    ");
    $stmt->execute([$detalle_pedido_id]);
    $detalle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$detalle) {
        error_log("No se encontró el detalle de pedido con ID: $detalle_pedido_id");
        return false;
    }

    // Convertir valores a float para comparaciones precisas
    $nueva_cantidad = floatval($nueva_cantidad);
    $cantidad_total = floatval($detalle['cantidad']);

    // Verificar que la nueva cantidad sea válida (no negativa y no exceda la solicitada)
    if ($nueva_cantidad < 0) {
        error_log("La cantidad entregada no puede ser negativa");
        return false;
    }
    
    if (round($nueva_cantidad, 3) > round($cantidad_total, 3)) {
        error_log("La cantidad entregada excede la cantidad solicitada");
        return false;
    }

    // Start a transaction to ensure consistency
    $pdo->beginTransaction();

    try {
        // Delete previous deliveries for this order detail
        $stmt = $pdo->prepare("DELETE FROM historial_entregas WHERE detalle_pedido_id = ?");
        $stmt->execute([$detalle_pedido_id]);

        // Register the new delivery in the history
        $stmt = $pdo->prepare("
            INSERT INTO historial_entregas 
            (detalle_pedido_id, cantidad, notas) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$detalle_pedido_id, $nueva_cantidad, $notas . ' (Corregido)']);

        // Update the delivered quantity
        $entregado = (round($nueva_cantidad, 3) >= round($cantidad_total, 3)) ? 1 : 0;
        
        $stmt = $pdo->prepare("
            UPDATE detalles_pedido 
            SET cantidad_entregada = ?, 
                fecha_ultima_entrega = NOW(),
                entregado = ?
            WHERE id = ?
        ");
        $stmt->execute([$nueva_cantidad, $entregado, $detalle_pedido_id]);

        // Update the order status
        actualizarEstadoPedido($pdo, $detalle['pedido_id']);

        // Confirm the transaction
        $pdo->commit();

        return true;
    } catch (Exception $e) {
        // If there's an error, rollback the transaction
        $pdo->rollBack();
        error_log("Error al corregir entrega parcial: " . $e->getMessage());
        return false;
    }
}

/**
 * Marca un producto como completamente entregado
 *
 * @param PDO $pdo Conexión a la base de datos
 * @param int $detalle_pedido_id ID del detalle de pedido
 * @return bool Resultado de la operación
 */
function marcarProductoEntregado($pdo, $detalle_pedido_id) {
    // Get the details of the product in the order
    $stmt = $pdo->prepare("
        SELECT dp.*, p.nombre as producto_nombre, p.id as producto_id, ped.id as pedido_id
        FROM detalles_pedido dp
        JOIN productos p ON dp.producto_id = p.id
        JOIN pedidos ped ON dp.pedido_id = ped.id
        WHERE dp.id = ?
    ");
    $stmt->execute([$detalle_pedido_id]);
    $detalle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$detalle) {
        error_log("No se encontró el detalle de pedido con ID: $detalle_pedido_id");
        return false;
    }

    // Convertir valores a float para cálculos precisos
    $cantidad_actual = floatval($detalle['cantidad_entregada']);
    $cantidad_total = floatval($detalle['cantidad']);
    
    // Calcular la cantidad pendiente
    $cantidad_pendiente = round($cantidad_total - $cantidad_actual, 3);

    // Start a transaction to ensure consistency
    $pdo->beginTransaction();

    try {
        // Register the delivery in the history (solo si hay cantidad pendiente)
        if ($cantidad_pendiente > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO historial_entregas 
                (detalle_pedido_id, cantidad, notas) 
                VALUES (?, ?, 'Marcado como entregado')
            ");
            $stmt->execute([$detalle_pedido_id, $cantidad_pendiente]);
        }

        // Update the delivered quantity and mark as delivered
        $stmt = $pdo->prepare("
            UPDATE detalles_pedido 
            SET cantidad_entregada = cantidad, 
                entregado = 1,
                fecha_ultima_entrega = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$detalle_pedido_id]);

        // Update the order status
        actualizarEstadoPedido($pdo, $detalle['pedido_id']);

        // Confirm the transaction
        $pdo->commit();

        return true;
    } catch (Exception $e) {
        // If there's an error, rollback the transaction
        $pdo->rollBack();
        error_log("Error al marcar producto como entregado: " . $e->getMessage());
        return false;
    }
}

/**
 * Actualiza el estado de un pedido basado en el estado de sus productos
 *
 * @param PDO $pdo Conexión a la base de datos
 * @param int $pedido_id ID del pedido
 * @return bool Resultado de la operación
 */
function actualizarEstadoPedido($pdo, $pedido_id) {
    // Usamos ROUND para evitar problemas de precisión en las comparaciones
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_productos,
            SUM(CASE WHEN ROUND(cantidad_entregada, 3) >= ROUND(cantidad, 3) THEN 1 ELSE 0 END) as productos_entregados,
            SUM(CASE WHEN ROUND(cantidad_entregada, 3) > 0 AND ROUND(cantidad_entregada, 3) < ROUND(cantidad, 3) THEN 1 ELSE 0 END) as productos_parciales
        FROM detalles_pedido
        WHERE pedido_id = ?
    ");
    $stmt->execute([$pedido_id]);
    $estado_productos = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Aquí puede haber productos con cantidad 0 que no deberían afectar
    $total_productos_reales = $estado_productos['total_productos'];
    
    // Si no hay productos en el pedido, no hacemos nada
    if ($total_productos_reales == 0) {
        return true;
    }
    
    // Determinar el estado del pedido
    $nuevo_estado = 'pendiente';
    
    if ($estado_productos['productos_entregados'] == $total_productos_reales) {
        $nuevo_estado = 'completado';
    } elseif ($estado_productos['productos_parciales'] > 0) {
        $nuevo_estado = 'parcial';
    } elseif ($estado_productos['productos_entregados'] > 0) {
        $nuevo_estado = 'en_proceso';
    }
    
    // Actualizamos el estado del pedido
    $stmt = $pdo->prepare("
        UPDATE pedidos 
        SET estado = ?, 
            fecha_actualizacion = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$nuevo_estado, $pedido_id]);

    // Si el estado es completado, generar factura
    if ($nuevo_estado == 'completado') {
        try {
            $archivo_factura = generarFacturaPDF($pdo, $pedido_id);
            
            // Save invoice path in the database
            $stmt = $pdo->prepare("UPDATE pedidos SET archivo_factura = ? WHERE id = ?");
            $stmt->execute([$archivo_factura, $pedido_id]);
            
            error_log("Factura generada para pedido completado: $pedido_id");
        } catch (Exception $e) {
            error_log("Error generando factura para pedido completado $pedido_id: " . $e->getMessage());
        }
    }

    return true;
}