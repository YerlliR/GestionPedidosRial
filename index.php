<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'config.php';
require_once 'db_connect.php';
require_once 'funciones.php';
require_once 'clientes_funciones.php';
require_once 'productos_funciones.php';
require_once 'pedidos_funciones.php';
require_once 'entregas_funciones.php';
require_once 'facturas_funciones.php';
require_once 'resumen_productos_funciones.php';
require_once 'productos_favoritos_funciones.php'; // Nuevo archivo


crearTablaProductosFavoritos($pdo);

// Handle invoice download
if (isset($_GET['descargar_factura']) && isset($_GET['pedido_id'])) {
    $pedido_id = $_GET['pedido_id'];
    $cliente_id = $_GET['cliente_id'];
    manejarDescargaFactura($pdo, $pedido_id, $cliente_id);
}

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    if ($_GET['ajax'] == 'get_pedido_detalles') {
        $pedido_id = $_GET['pedido_id'];
        
        // Get order information
        $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
        $stmt->execute([$pedido_id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pedido) {
            echo json_encode(['error' => 'Pedido no encontrado']);
            exit;
        }
        
        // Get order details
        $stmt = $pdo->prepare("
            SELECT dp.*, p.nombre as producto_nombre, p.precio 
            FROM detalles_pedido dp
            JOIN productos p ON dp.producto_id = p.id
            WHERE dp.pedido_id = ?
        ");
        $stmt->execute([$pedido_id]);
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Return data in JSON format
        echo json_encode([
            'pedido' => $pedido,
            'detalles' => $detalles
        ]);
        exit;
    }else if ($_GET['ajax'] == 'get_productos') {
        $productos = obtenerProductos($pdo);
        echo json_encode(['productos' => $productos]);
        exit;
    }    elseif ($_GET['ajax'] == 'toggle_favorito') {
        $producto_id = (int)$_GET['producto_id'];
        $es_favorito = (bool)$_GET['es_favorito'];
        
        $resultado = actualizarProductoFavorito($pdo, $producto_id, $es_favorito);
        echo json_encode(['success' => $resultado]);
        exit;
    }
}




// Check if we are on the products page
$mostrar_productos = isset($_GET['productos']) && $_GET['productos'] == '1';

// Handle form actions
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';

// Handle delivery actions

// Sección para manejar entregas
if ($accion == 'registrar_entrega_parcial') {
    $detalle_pedido_id = $_POST['detalle_pedido_id'];
    $cantidad = floatval($_POST['cantidad']);
    $notas = $_POST['notas'] ?? '';
    $cliente_id = $_POST['cliente_id'];
    $pedido_id = $_POST['pedido_id'];
    $volver_clientes = isset($_POST['volver_clientes']) && $_POST['volver_clientes'] == '1';

    if (registrarEntregaParcial($pdo, $detalle_pedido_id, $cantidad, $notas)) {
        if ($volver_clientes) {
            header("Location: index.php");
        } else {
            header("Location: index.php?cliente_id=" . $cliente_id . "&pedido_id=" . $pedido_id . "&success=entrega_registrada");
        }
    } else {
        if ($volver_clientes) {
            header("Location: index.php?error=entrega_fallida");
        } else {
            header("Location: index.php?cliente_id=" . $cliente_id . "&pedido_id=" . $pedido_id . "&error=entrega_fallida");
        }
    }
    exit;
} elseif ($accion == 'marcar_producto_entregado') {
    $detalle_pedido_id = $_POST['detalle_pedido_id'];
    $cliente_id = $_POST['cliente_id'];
    $pedido_id = $_POST['pedido_id'];
    
    if (marcarProductoEntregado($pdo, $detalle_pedido_id)) {
        header("Location: index.php?cliente_id=" . $cliente_id . "&pedido_id=" . $pedido_id . "&success=producto_completado");
    } else {
        header("Location: index.php?cliente_id=" . $cliente_id . "&pedido_id=" . $pedido_id . "&error=marcar_completado_fallido");
    }
    exit;
} elseif ($accion == 'corregir_entrega_parcial') {
    $detalle_pedido_id = $_POST['detalle_pedido_id'];
    $nueva_cantidad = floatval($_POST['nueva_cantidad']);
    $notas = $_POST['notas'] ?? '';
    $cliente_id = $_POST['cliente_id'];
    $pedido_id = $_POST['pedido_id'];

    if (corregirEntregaParcial($pdo, $detalle_pedido_id, $nueva_cantidad, $notas)) {
        header("Location: index.php?cliente_id=" . $cliente_id . "&pedido_id=" . $pedido_id);
    } else {
        header("Location: index.php?cliente_id=" . $cliente_id . "&pedido_id=" . $pedido_id . "&error=correccion_fallida");
    }
    exit;
} 
// Resto de la sección de acciones
elseif ($accion == 'crear_pedido') {
    $cliente_id = $_POST['cliente_id'];
    $pedido_id = crearPedido($pdo, $cliente_id);
    
    // Process selected products
    $productos = obtenerProductos($pdo);
    $productos_cantidades = [];
    
    foreach ($productos as $producto) {
        $producto_id = $producto['id'];
        $cantidad = isset($_POST['cantidad_' . $producto_id]) ? floatval($_POST['cantidad_' . $producto_id]) : 0;
        $productos_cantidades[$producto_id] = $cantidad;
    }
    
    actualizarDetallesPedido($pdo, $pedido_id, $productos_cantidades);
    
    header("Location: index.php?cliente_id=" . $cliente_id);
    exit;
} elseif ($accion == 'actualizar_pedido') {
    $pedido_id = $_POST['pedido_id'];
    $cliente_id = $_POST['cliente_id'];
    $estado = $_POST['estado'];
    
    // Process selected products
    $productos = obtenerProductos($pdo);
    $productos_cantidades = [];
    
    foreach ($productos as $producto) {
        $producto_id = $producto['id'];
        $cantidad = isset($_POST['cantidad_' . $producto_id]) ? floatval($_POST['cantidad_' . $producto_id]) : 0;
        $productos_cantidades[$producto_id] = $cantidad;
    }
    
    actualizarDetallesPedido($pdo, $pedido_id, $productos_cantidades);
    actualizarEstadoPedido($pdo, $pedido_id);
    
    header("Location: index.php?cliente_id=" . $cliente_id);
    exit;
} elseif ($accion == 'editar_pedido') {
    $pedido_id = $_POST['pedido_id'];
    $cliente_id = $_POST['cliente_id'];
    
    // Procesar productos seleccionados
    $productos = obtenerProductos($pdo);
    $productos_cantidades = [];
    
    foreach ($productos as $producto) {
        $producto_id = $producto['id'];
        $cantidad = isset($_POST['cantidad_' . $producto_id]) ? floatval($_POST['cantidad_' . $producto_id]) : 0;
        $productos_cantidades[$producto_id] = $cantidad;
    }
    
    if (editarPedido($pdo, $pedido_id, $productos_cantidades)) {
        header("Location: index.php?cliente_id=" . $cliente_id . "&pedido_id=" . $pedido_id . "&success=pedido_actualizado");
    } else {
        header("Location: index.php?cliente_id=" . $cliente_id . "&pedido_id=" . $pedido_id . "&error=error_editar_pedido");
    }
    exit;
} elseif ($accion == 'actualizar_cantidad_realizada') {
    $producto_id = (int)$_POST['producto_id'];
    $cantidad_realizada = floatval($_POST['cantidad_realizada']);
    
    if (actualizarCantidadRealizada($pdo, $producto_id, $cantidad_realizada)) {
        header("Location: index.php?success=cantidad_realizada_actualizada");
    } else {
        header("Location: index.php?error=error_actualizar_cantidad");
    }
    exit;
}
// Sección para manejar clientes
elseif ($accion == 'agregar_cliente') {
    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'] ?? '';
    $email = $_POST['email'] ?? '';
    $producto_favorito_id = !empty($_POST['producto_favorito_id']) ? $_POST['producto_favorito_id'] : null;
    
    if (agregarCliente($pdo, $nombre, $telefono, $email, $producto_favorito_id)) {
        header("Location: index.php?success=cliente_agregado");
    } else {
        header("Location: index.php?error=error_agregar_cliente");
    }
    exit;
} elseif ($accion == 'editar_cliente') {
    $cliente_id = $_POST['cliente_id'];
    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'] ?? '';
    $email = $_POST['email'] ?? '';
    $producto_favorito_id = !empty($_POST['producto_favorito_id']) ? $_POST['producto_favorito_id'] : null;
    
    if (actualizarCliente($pdo, $cliente_id, $nombre, $telefono, $email, $producto_favorito_id)) {
        header("Location: index.php?success=cliente_actualizado");
    } else {
        header("Location: index.php?error=error_actualizar_cliente");
    }
    exit;
} elseif ($accion == 'eliminar_cliente') {
    $cliente_id = $_POST['cliente_id'];
    $resultado = eliminarCliente($pdo, $cliente_id);
    
    if ($resultado['success']) {
        header("Location: index.php?success=cliente_eliminado");
    } else {
        $mensaje_codificado = urlencode($resultado['mensaje']);
        header("Location: index.php?error=error_eliminar_cliente&mensaje=" . $mensaje_codificado);
    }
    exit;
}elseif ($accion == 'toggle_producto_favorito') {
    $producto_id = (int)$_POST['producto_id'];
    $es_favorito = (bool)$_POST['es_favorito'];
    
    $resultado = actualizarProductoFavorito($pdo, $producto_id, $es_favorito);
    
    // Si es una petición AJAX, devolver JSON
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => $resultado]);
        exit;
    }
    
    // Si no es AJAX, redirigir
    header("Location: index.php");
    exit;
}

// Sección para manejar productos
elseif ($accion == 'agregar_producto') {
    $nombre = $_POST['nombre_producto'];
    $descripcion = $_POST['descripcion_producto'] ?? '';
    $precio = floatval($_POST['precio_producto']);
    
    if (agregarProducto($pdo, $nombre, $descripcion, $precio)) {
        header("Location: index.php?productos=1&success=producto_agregado");
    } else {
        header("Location: index.php?productos=1&error=error_agregar_producto");
    }
    exit;
} elseif ($accion == 'editar_producto') {
    $id = $_POST['id_producto'];
    $nombre = $_POST['nombre_producto'];
    $descripcion = $_POST['descripcion_producto'] ?? '';
    $precio = floatval($_POST['precio_producto']);
    
    if (editarProducto($pdo, $id, $nombre, $descripcion, $precio)) {
        header("Location: index.php?productos=1&success=producto_editado");
    } else {
        header("Location: index.php?productos=1&error=error_editar_producto");
    }
    exit;
} elseif ($accion == 'eliminar_producto') {
    $id = $_POST['id_producto'];
    
    if (eliminarProducto($pdo, $id)) {
        header("Location: index.php?productos=1&success=producto_eliminado");
    } else {
        header("Location: index.php?productos=1&error=error_eliminar_producto");
    }
    exit;
}

crearTablaProductosRealizados($pdo);



if (isset($_GET['success'])) {
    switch($_GET['success']) {
        case 'cantidad_realizada_actualizada':
            echo "Cantidad realizada actualizada con éxito";
            break;
        // Otros casos existentes...
    }
}

if (isset($_GET['error'])) {
    switch($_GET['error']) {
        case 'error_actualizar_cantidad':
            echo "Error al actualizar la cantidad realizada";
            break;
        // Otros casos existentes...
    }
}

// Get context

$mostrar_solo_favoritos = isset($_GET['mostrar_solo_favoritos']) ? ($_GET['mostrar_solo_favoritos'] !== '0') : true;$clientes = obtenerClientes($pdo);
$cliente_id = isset($_GET['cliente_id']) ? $_GET['cliente_id'] : null;
$ver_todos = isset($_GET['ver_todos']) && $_GET['ver_todos'] == '1';
$pedido_id = isset($_GET['pedido_id']) ? $_GET['pedido_id'] : null;

// If there's a selected client but no specific order,
// we try to automatically load the client's last order
if ($cliente_id && !$pedido_id) {
    $stmt = $pdo->prepare("SELECT id FROM pedidos WHERE cliente_id = ? ORDER BY fecha_creacion DESC LIMIT 1");
    $stmt->execute([$cliente_id]);
    $ultimo_pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ultimo_pedido) {
        $pedido_id = $ultimo_pedido['id'];
    }
}





// Include the HTML view file
include 'vista.php';
?>