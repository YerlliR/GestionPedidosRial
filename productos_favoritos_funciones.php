<?php
/**
 * Funciones para la gestión de productos favoritos del administrador
 */

/**
 * Obtiene los productos marcados como favoritos por el administrador
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @return array Productos favoritos
 */
function obtenerProductosFavoritos($pdo) {
    $stmt = $pdo->query("
        SELECT p.* 
        FROM productos p
        JOIN productos_favoritos_admin pf ON p.id = pf.producto_id
        ORDER BY p.nombre ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Marca o desmarca un producto como favorito del administrador
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param int $producto_id ID del producto
 * @param bool $es_favorito Si es true, marca como favorito; si es false, desmarca
 * @return bool Resultado de la operación
 */
function actualizarProductoFavorito($pdo, $producto_id, $es_favorito) {
    try {
        if ($es_favorito) {
            // Verificar si ya existe
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM productos_favoritos_admin WHERE producto_id = ?");
            $stmt->execute([$producto_id]);
            $existe = $stmt->fetchColumn() > 0;
            
            if (!$existe) {
                $stmt = $pdo->prepare("INSERT INTO productos_favoritos_admin (producto_id) VALUES (?)");
                return $stmt->execute([$producto_id]);
            }
            return true; // Ya estaba marcado como favorito
        } else {
            // Eliminar de favoritos
            $stmt = $pdo->prepare("DELETE FROM productos_favoritos_admin WHERE producto_id = ?");
            return $stmt->execute([$producto_id]);
        }
    } catch (PDOException $e) {
        error_log("Error al actualizar producto favorito: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica si un producto está marcado como favorito
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param int $producto_id ID del producto
 * @return bool true si es favorito, false si no
 */
function esProductoFavorito($pdo, $producto_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM productos_favoritos_admin WHERE producto_id = ?");
    $stmt->execute([$producto_id]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Crea la tabla de productos favoritos si no existe
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @return bool Resultado de la operación
 */
function crearTablaProductosFavoritos($pdo) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS productos_favoritos_admin (
                id INT AUTO_INCREMENT PRIMARY KEY,
                producto_id INT NOT NULL,
                fecha_agregado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
                UNIQUE KEY (producto_id)
            )
        ");
        return true;
    } catch (PDOException $e) {
        error_log("Error al crear tabla productos_favoritos_admin: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene un resumen de productos favoritos del administrador para el dashboard
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param bool $solo_favoritos Si es true, muestra solo favoritos; si es false, muestra todos
 * @return array Datos del resumen de productos
 */
function obtenerResumenProductosFavoritos($pdo, $solo_favoritos = true) {
    // Base de la consulta para obtener productos y sus cantidades
    $base_query = "
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
    
    // Si solo queremos favoritos, añadimos una condición WHERE
    if ($solo_favoritos) {
        $base_query .= " WHERE pf.producto_id IS NOT NULL";
    }
    
    // Completamos la consulta
    $base_query .= "
        GROUP BY p.id
        ORDER BY " . ($solo_favoritos ? "cantidad_total DESC" : "es_favorito DESC, cantidad_total DESC");
    
    $stmt = $pdo->query($base_query);
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