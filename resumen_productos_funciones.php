<?php
/**
 * Obtiene un resumen de productos totales por hacer basado en los pedidos activos
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @return array Datos del resumen de productos
 */
function obtenerResumenProductos($pdo) {
    // Consulta para obtener todos los productos con sus cantidades en pedidos activos
    $stmt = $pdo->query("
        SELECT 
            p.id, 
            p.nombre, 
            p.descripcion,
            p.precio,
            SUM(dp.cantidad) as cantidad_total,
            SUM(dp.cantidad_entregada) as cantidad_entregada
        FROM productos p
        LEFT JOIN detalles_pedido dp ON p.id = dp.producto_id
        LEFT JOIN pedidos ped ON dp.pedido_id = ped.id
        WHERE ped.estado != 'completado' OR ped.id IS NULL
        GROUP BY p.id
        ORDER BY cantidad_total DESC
    ");
    
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Consulta para obtener las cantidades realizadas (desde una tabla nueva que crearemos)
    $stmt = $pdo->prepare("
        SELECT producto_id, cantidad_realizada 
        FROM productos_realizados
    ");
    $stmt->execute();
    $realizados = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $realizados[$row['producto_id']] = $row['cantidad_realizada'];
    }
    
    // Calcular cantidades pendientes y añadir datos de realizados
    foreach ($productos as &$producto) {
        $producto['cantidad_realizada'] = isset($realizados[$producto['id']]) ? $realizados[$producto['id']] : 0;
        $producto['cantidad_pendiente'] = max(0, floatval($producto['cantidad_total']) - floatval($producto['cantidad_realizada']));
    }
    
    return $productos;
}

/**
 * Actualiza la cantidad realizada de un producto
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param int $producto_id ID del producto
 * @param float $cantidad_realizada Nueva cantidad realizada
 * @return bool Resultado de la operación
 */
function actualizarCantidadRealizada($pdo, $producto_id, $cantidad_realizada) {
    try {
        // Verificar si ya existe un registro para este producto
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM productos_realizados WHERE producto_id = ?");
        $stmt->execute([$producto_id]);
        
        if ($stmt->fetchColumn() > 0) {
            // Actualizar registro existente
            $stmt = $pdo->prepare("
                UPDATE productos_realizados 
                SET cantidad_realizada = ?, 
                    fecha_actualizacion = NOW() 
                WHERE producto_id = ?
            ");
            return $stmt->execute([$cantidad_realizada, $producto_id]);
        } else {
            // Crear nuevo registro
            $stmt = $pdo->prepare("
                INSERT INTO productos_realizados 
                (producto_id, cantidad_realizada, fecha_actualizacion) 
                VALUES (?, ?, NOW())
            ");
            return $stmt->execute([$producto_id, $cantidad_realizada]);
        }
    } catch (PDOException $e) {
        error_log("Error al actualizar cantidad realizada: " . $e->getMessage());
        return false;
    }
}

/**
 * Función para crear la tabla productos_realizados si no existe
 * 
 * @param PDO $pdo Conexión a la base de datos
 */
function crearTablaProductosRealizados($pdo) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS productos_realizados (
                id INT AUTO_INCREMENT PRIMARY KEY,
                producto_id INT NOT NULL,
                cantidad_realizada DECIMAL(10,3) NOT NULL DEFAULT 0,
                fecha_actualizacion DATETIME NOT NULL,
                FOREIGN KEY (producto_id) REFERENCES productos(id),
                UNIQUE KEY (producto_id)
            )
        ");
        return true;
    } catch (PDOException $e) {
        error_log("Error al crear tabla productos_realizados: " . $e->getMessage());
        return false;
    }
}