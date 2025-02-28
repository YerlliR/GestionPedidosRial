<?php
// Functions for registering a partial delivery
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
        return false;
    }

    // Verify that the delivered quantity does not exceed the requested quantity
    if (floatval($detalle['cantidad_entregada']) + floatval($cantidad) > floatval($detalle['cantidad'])) {
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
        $stmt = $pdo->prepare("
            UPDATE detalles_pedido 
            SET cantidad_entregada = cantidad_entregada + ?, 
                fecha_ultima_entrega = NOW(),
                entregado = CASE WHEN cantidad_entregada + ? >= cantidad THEN 1 ELSE 0 END
            WHERE id = ?
        ");
        $stmt->execute([$cantidad, $cantidad, $detalle_pedido_id]);

        // Update the order status
        actualizarEstadoPedido($pdo, $detalle['pedido_id']);

        // Confirm the transaction
        $pdo->commit();

        return true;
    } catch (Exception $e) {
        // If there's an error, rollback the transaction
        $pdo->rollBack();
        return false;
    }
}

// Function to correct a partial delivery
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
        return false;
    }

    // Verify that the new quantity does not exceed the requested quantity
    if (floatval($nueva_cantidad) > floatval($detalle['cantidad'])) {
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
        $stmt = $pdo->prepare("
            UPDATE detalles_pedido 
            SET cantidad_entregada = ?, 
                fecha_ultima_entrega = NOW(),
                entregado = CASE WHEN ? = cantidad THEN 1 ELSE 0 END
            WHERE id = ?
        ");
        $stmt->execute([$nueva_cantidad, $nueva_cantidad, $detalle_pedido_id]);

        // Update the order status
        actualizarEstadoPedido($pdo, $detalle['pedido_id']);

        // Confirm the transaction
        $pdo->commit();

        return true;
    } catch (Exception $e) {
        // If there's an error, rollback the transaction
        $pdo->rollBack();
        return false;
    }
}

// Function to mark a product as completely delivered
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
        return false;
    }

    // Register as delivered the product and the pending quantity
    $cantidad_pendiente = floatval($detalle['cantidad']) - floatval($detalle['cantidad_entregada']);

    // Start a transaction to ensure consistency
    $pdo->beginTransaction();

    try {
        // Register the delivery in the history
        $stmt = $pdo->prepare("
            INSERT INTO historial_entregas 
            (detalle_pedido_id, cantidad, notas) 
            VALUES (?, ?, 'Marcado como entregado')
        ");
        $stmt->execute([$detalle_pedido_id, $cantidad_pendiente]);

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
        return false;
    }
}

function actualizarEstadoPedido($pdo, $pedido_id) {
    // Check if all products are delivered
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_productos,
            SUM(CASE WHEN entregado = 1 THEN 1 ELSE 0 END) as productos_entregados,
            SUM(CASE WHEN cantidad_entregada > 0 AND cantidad_entregada < cantidad THEN 1 ELSE 0 END) as productos_parciales
        FROM detalles_pedido
        WHERE pedido_id = ?
    ");
    $stmt->execute([$pedido_id]);
    $estado_productos = $stmt->fetch(PDO::FETCH_ASSOC);

    // Primera verificación: contamos si hay productos sin entregar o parcialmente entregados
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as productos_incompletos
        FROM detalles_pedido
        WHERE pedido_id = ? AND (cantidad_entregada < cantidad)
    ");
    $stmt->execute([$pedido_id]);
    $productos_incompletos = $stmt->fetchColumn();

    // Determinamos el estado basándonos en la información recopilada
    $nuevo_estado = 'pendiente';
    
    // El pedido está completado SOLO si no hay productos incompletos
    if ($productos_incompletos == 0) {
        $nuevo_estado = 'completado';
    }
    // Si hay al menos un producto con entrega parcial, el estado es 'parcial'
    elseif ($estado_productos['productos_parciales'] > 0) {
        $nuevo_estado = 'parcial';
    } 
    // Si hay algún producto entregado pero no hay parciales, el estado es 'en_proceso'
    elseif ($estado_productos['productos_entregados'] > 0) {
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
?>