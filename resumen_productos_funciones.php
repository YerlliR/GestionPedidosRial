<?php
/**
 * Obtiene un resumen de productos totales por hacer basado en los pedidos activos
 * Versión mejorada con manejo correcto de valores decimales
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param bool $solo_favoritos Si es true, muestra solo favoritos; si es false, muestra todos
 * @return array Datos del resumen de productos
 */
function obtenerResumenProductos($pdo, $solo_favoritos = false) {
    // Consulta base para obtener todos los productos con sus cantidades en pedidos activos
    $sql = "
        SELECT 
            p.id, 
            p.nombre, 
            p.descripcion,
            p.precio,
            COALESCE(SUM(dp.cantidad), 0) as cantidad_total,
            COALESCE(SUM(dp.cantidad_entregada), 0) as cantidad_entregada,
            CASE WHEN pf.producto_id IS NOT NULL THEN 1 ELSE 0 END as es_favorito
        FROM productos p
        LEFT JOIN detalles_pedido dp ON p.id = dp.producto_id
        LEFT JOIN pedidos ped ON dp.pedido_id = ped.id AND ped.estado != 'completado'
        LEFT JOIN productos_favoritos_admin pf ON p.id = pf.producto_id
    ";
    
    // Si solo queremos favoritos, añadimos una condición
    if ($solo_favoritos) {
        $sql .= " WHERE pf.producto_id IS NOT NULL";
    }
    
    // Completamos la consulta
    $sql .= "
        GROUP BY p.id
        ORDER BY " . ($solo_favoritos ? "cantidad_total DESC" : "es_favorito DESC, cantidad_total DESC");
    
    $stmt = $pdo->query($sql);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Consulta para obtener las cantidades realizadas
    $stmt = $pdo->prepare("
        SELECT producto_id, cantidad_realizada 
        FROM productos_realizados
    ");
    $stmt->execute();
    $realizados = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $realizados[$row['producto_id']] = floatval($row['cantidad_realizada']);
    }
    
    // Calcular cantidades pendientes y añadir datos de realizados
    foreach ($productos as &$producto) {
        // Convertir a floats para evitar problemas de comparación con cadenas
        $producto['cantidad_total'] = floatval($producto['cantidad_total']);
        $producto['cantidad_entregada'] = floatval($producto['cantidad_entregada']);
        $producto['cantidad_realizada'] = isset($realizados[$producto['id']]) ? $realizados[$producto['id']] : 0;
        $producto['cantidad_pendiente'] = max(0, $producto['cantidad_total'] - $producto['cantidad_realizada']);
        
        // Redondear a 3 decimales para evitar errores de precisión float
        $producto['cantidad_total'] = round($producto['cantidad_total'], 3);
        $producto['cantidad_entregada'] = round($producto['cantidad_entregada'], 3);
        $producto['cantidad_realizada'] = round($producto['cantidad_realizada'], 3);
        $producto['cantidad_pendiente'] = round($producto['cantidad_pendiente'], 3);
    }
    
    return $productos;
}

/**
 * Actualiza la cantidad realizada de un producto
 * Versión mejorada con manejo correcto de valores decimales
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param int $producto_id ID del producto
 * @param float $cantidad_realizada Nueva cantidad realizada
 * @return bool Resultado de la operación
 */
function actualizarCantidadRealizada($pdo, $producto_id, $cantidad_realizada) {
    try {
        // Asegurar que la cantidad es un float válido
        $cantidad_realizada = floatval($cantidad_realizada);
        
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
 * Sin cambios en esta función
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