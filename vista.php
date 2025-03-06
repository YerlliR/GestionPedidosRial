<?php
// Agregar esta función al principio del archivo vista.php, justo después de la etiqueta inicial <?php

/**
 * Función para formatear cantidades mostrando decimales solo cuando es necesario
 * 
 * @param float|int $cantidad La cantidad a formatear
 * @return string Cantidad formateada
 */
function formatearCantidad($cantidad) {
    // Convertir a float para asegurar que la comparación funciona correctamente
    $cantidad = floatval($cantidad);
    
    // Verificar si la cantidad tiene decimales
    if ($cantidad == floor($cantidad)) {
        // Es un número entero (sin decimales)
        return number_format($cantidad, 0);
    } else {
        // Tiene decimales, mostramos hasta 3 decimales
        return number_format($cantidad, 3);
    }
}


// AÑADIR AQUÍ EL CÓDIGO DE INICIALIZACIÓN
if (!isset($cliente)) {
    $cliente = [
        'id' => '',
        'nombre' => '',
        'telefono' => '',
        'email' => '',
        'producto_favorito_id' => null,
        'producto_favorito_nombre' => ''
    ];
}

if (!isset($info_producto)) {
    $info_producto = [
        'cantidad_solicitada' => 0,
        'cantidad_entregada' => 0
    ];
}

// También asegúrate de que $iniciales está definido antes de usarlo
if (!isset($iniciales) && isset($cliente['nombre'])) {
    // Generar iniciales a partir del nombre del cliente
    $iniciales = strtoupper(substr($cliente['nombre'], 0, 1));
    if (strpos($cliente['nombre'], ' ') !== false) {
        $partes = explode(' ', $cliente['nombre']);
        $iniciales = strtoupper(substr($partes[0], 0, 1) . substr(end($partes), 0, 1));
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>

<nav class="navbar">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center w-100">
            <a href="index.php" class="navbar-brand">
                <i class="fas fa-boxes me-2"></i>
                Gestión de Pedidos
            </a>

            <button class="menu-toggle d-md-none" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>

            <div class="d-none d-md-flex align-items-center gap-3">
                <a href="index.php" class="nav-link <?php echo !$mostrar_productos && !$cliente_id ? 'active' : ''; ?>">
                    <i class="fas fa-users me-1"></i>
                    <span class="d-none d-md-inline">Clientes</span>
                </a>

                <a href="?productos=1" class="nav-link <?php echo $mostrar_productos ? 'active' : ''; ?>">
                    <i class="fas fa-box me-1"></i>
                    <span class="d-none d-md-inline">Productos</span>
                </a>

                <?php if ($cliente_id): ?>
                    <a href="index.php?cliente_id=<?php echo $cliente_id; ?>" class="nav-link active">
                        <i class="fas fa-shopping-cart me-1"></i>
                        <span class="d-none d-md-inline">Pedidos</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Menú móvil -->
    <div class="navbar-backdrop" id="menuBackdrop"></div>
    <div class="navbar-menu" id="mobileMenu">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="m-0">Menú</h5>
            <button class="btn-close" id="menuClose"></button>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a href="index.php" class="nav-link <?php echo !$mostrar_productos && !$cliente_id ? 'active' : ''; ?>">
                    <i class="fas fa-users me-2"></i>
                    Clientes
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="?productos=1" class="nav-link <?php echo $mostrar_productos ? 'active' : ''; ?>">
                    <i class="fas fa-box me-2"></i>
                    Productos
                </a>
            </li>
            <?php if ($cliente_id): ?>
                <li class="nav-item mb-2">
                    <a href="index.php?cliente_id=<?php echo $cliente_id; ?>" class="nav-link active">
                        <i class="fas fa-shopping-cart me-2"></i>
                        Pedidos de <?php echo htmlspecialchars(substr($cliente_actual['nombre'], 0, 15) . (strlen($cliente_actual['nombre']) > 15 ? '...' : '')); ?>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
    </nav>
    

    <?php
    // Añadir este código en la vista de pedidos (justo después de la barra de navegación del pedido)
    if (isset($_GET['success']) || isset($_GET['error'])):
    ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php
                switch ($_GET['success']) {
                    case 'pedido_actualizado':
                        echo "Pedido actualizado con éxito";
                        break;
                    default:
                        echo "Operación completada con éxito";
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php
                switch ($_GET['error']) {
                    case 'error_editar_pedido':
                        echo "Error al editar el pedido. Es posible que no se pueda reducir la cantidad por debajo de lo ya entregado o el pedido esté completado.";
                        break;
                    default:
                        echo "Ha ocurrido un error en la operación";
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    <div class="container">

        <?php
        if (isset($_GET['success']) || isset($_GET['error'])):
        ?>
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php
                    switch ($_GET['success']) {
                        case 'cliente_eliminado':
                            echo "Cliente eliminado con éxito";
                            break;
                        default:
                            echo "Operación completada con éxito";
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php
                    switch ($_GET['error']) {
                        case 'error_eliminar_cliente':
                            echo isset($_GET['mensaje']) ? $_GET['mensaje'] : "Error al eliminar cliente";
                            break;
                        default:
                            echo "Ha ocurrido un error en la operación";
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!$cliente_id && !$mostrar_productos): ?>
            <div class="dashboard animate-fade-in">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Clientes y sus Productos Favoritos</h2>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAgregarCliente">
                    <i class="fas fa-user-plus me-2"></i>Agregar Cliente
                </button>
            </div>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="buscadorClientes" class="form-control" placeholder="Buscar clientes por nombre, teléfono o email...">
            </div>

                <div class="row">
                    <?php foreach ($clientes as $cliente):
                        $info_producto = ['cantidad_solicitada' => 0, 'cantidad_entregada' => 0];
                        if (!empty($cliente['producto_favorito_id'])) {
                            $info_producto = obtenerInfoProductoFavorito($pdo, $cliente['id'], $cliente['producto_favorito_id']);
                        }

                        $porcentaje_entrega = 0;
                        if ($info_producto['cantidad_solicitada'] > 0) {
                            $porcentaje_entrega = ($info_producto['cantidad_entregada'] / $info_producto['cantidad_solicitada']) * 100;
                        }

                        $estado_clase = 'bg-secondary';
                        $estado_texto = 'Sin pedidos';

                        if ($info_producto['cantidad_solicitada'] > 0) {
                            if ($info_producto['cantidad_entregada'] == 0) {
                                $estado_clase = 'badge-pendiente';
                                $estado_texto = 'Pendiente';
                            } elseif ($info_producto['cantidad_entregada'] < $info_producto['cantidad_solicitada']) {
                                $estado_clase = 'badge-parcial';
                                $estado_texto = 'Parcial';
                            } else {
                                // Verificar si el pedido completo está completado o no
                                $stmt = $pdo->prepare("
                                    SELECT ped.estado, ped.id as pedido_id
                                    FROM detalles_pedido dp
                                    JOIN pedidos ped ON dp.pedido_id = ped.id
                                    WHERE ped.cliente_id = ? 
                                    AND dp.producto_id = ? 
                                    ORDER BY ped.fecha_creacion DESC
                                    LIMIT 1
                                ");
                                $stmt->execute([$cliente['id'], $cliente['producto_favorito_id']]);
                                $pedido_estado = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($pedido_estado && $pedido_estado['estado'] == 'completado') {
                                    $estado_clase = 'badge-completado';
                                    $estado_texto = 'Completado';
                                } else {
                                    // Mejorar la diferenciación visual para este estado
                                    $estado_clase = 'bg-info';  // Cambiamos a azul claro (clase Bootstrap)
                                    $estado_texto = '✓ Producto entregado';  // Añadimos un símbolo de check y mejoramos texto
                                }
                            }
                        }

                        // Obtener las iniciales para el avatar
                        $iniciales = strtoupper(substr($cliente['nombre'], 0, 1));
                        if (strpos($cliente['nombre'], ' ') !== false) {
                            $partes = explode(' ', $cliente['nombre']);
                            $iniciales = strtoupper(substr($partes[0], 0, 1) . substr(end($partes), 0, 1));
                        }
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="cliente-card animate-fade-in">
                            <!-- Botones de acción en la esquina superior derecha -->
                            <div class="cliente-acciones">
                                <button class="btn btn-warning btn-sm btn-cliente-accion"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditarCliente"
                                    data-id="<?php echo $cliente['id']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($cliente['nombre'] ?? ''); ?>"
                                    data-telefono="<?php echo htmlspecialchars($cliente['telefono']); ?>"
                                    data-email="<?php echo htmlspecialchars($cliente['email']); ?>"
                                    data-producto-favorito-id="<?php echo $cliente['producto_favorito_id']; ?>"
                                    data-tooltip="Editar cliente">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm btn-cliente-accion"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEliminarCliente"
                                    data-id="<?php echo $cliente['id']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($cliente['nombre'] ?? '');?>"
                                    data-tooltip="Eliminar cliente">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>

                            <!-- Información principal del cliente -->
                            <div class="cliente-info">
                                <div class="cliente-avatar">
                                    <?php echo $iniciales; ?>
                                </div>
                                <div class="cliente-details">
                                    <h3 class="cliente-name"><?php echo htmlspecialchars($cliente['nombre'] ?? ''); ?></h3>

                                    <?php if (!empty($cliente['telefono'])): ?>
                                        <div class="cliente-contact">
                                            <i class="fas fa-phone text-muted"></i>
                                            <?php echo htmlspecialchars($cliente['telefono']); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($cliente['email'])): ?>
                                        <div class="cliente-contact">
                                            <i class="fas fa-envelope text-muted"></i>
                                            <?php echo htmlspecialchars($cliente['email']); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($cliente['producto_favorito_nombre'])): ?>
                                        <div class="mt-2">
                                            <span class="badge bg-primary">
                                                <i class="fas fa-star me-1"></i>
                                                <?php echo htmlspecialchars($cliente['producto_favorito_nombre']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Barra de progreso para producto favorito (si existe) -->
                            <?php if ($info_producto['cantidad_solicitada'] > 0): ?>
                                <div class="mt-3">
                                    <div class="progress-label">
                                        <span>Entrega de producto favorito</span>
                                        <span><?php echo formatearCantidad($info_producto['cantidad_entregada']); ?> / <?php echo formatearCantidad($info_producto['cantidad_solicitada']); ?></span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-info" role="progressbar"
                                            style="width: <?php echo $porcentaje_entrega; ?>%"
                                            aria-valuenow="<?php echo $porcentaje_entrega; ?>"
                                            aria-valuemin="0"
                                            aria-valuemax="100"></div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span class="badge <?php
                                        if ($estado_texto == '✓ Producto entregado') {
                                            echo 'bg-info'; // Estilo mejorado para producto entregado
                                        } elseif ($estado_texto == 'Completado') {
                                            echo 'badge-completado';
                                        } elseif ($estado_texto == 'Parcial') {
                                            echo 'badge-parcial';
                                        } elseif ($estado_texto == 'Pendiente') {
                                            echo 'badge-pendiente';
                                        } else {
                                            echo 'bg-secondary';
                                        }
                                        ?>" 
                                        title="<?php 
                                            if ($estado_texto == '✓ Producto entregado') {
                                                echo 'Producto completamente entregado, pero el pedido contiene otros productos pendientes';
                                            } elseif ($estado_texto == 'Completado') {
                                                echo 'Pedido completamente finalizado';
                                            } elseif ($estado_texto == 'Parcial') {
                                                echo 'Producto parcialmente entregado';
                                            } elseif ($estado_texto == 'Pendiente') {
                                                echo 'Pendiente de entrega';
                                            } else {
                                                echo 'No hay pedidos activos para este producto';
                                            }
                                        ?>">
                                            <?php echo $estado_texto; ?>
                                        </span>
                                        <span class="text-muted small">Actualizado: <?php echo date('d/m/Y'); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Botones de acción principales -->
                            <div class="d-flex justify-content-between mt-3">
                                <!-- Este enlace será usado para hacer toda la tarjeta clickable con JavaScript -->
                                <a href="?cliente_id=<?php echo $cliente['id']; ?>" class="btn btn-primary btn-sm btn-ver-pedidos">
                                    <i class="fas fa-shopping-cart me-1"></i>Ver Pedidos
                                </a>

                                <?php if (!empty($cliente['producto_favorito_id'])): ?>
                                    <?php
                                    // Obtener el último pedido activo que contenga el producto favorito
                                    $stmt = $pdo->prepare("
                                        SELECT dp.id as detalle_id, ped.id as pedido_id, dp.cantidad, dp.cantidad_entregada, dp.entregado, 
                                            ped.estado as pedido_estado
                                        FROM detalles_pedido dp
                                        JOIN pedidos ped ON dp.pedido_id = ped.id
                                        WHERE ped.cliente_id = ? 
                                        AND dp.producto_id = ? 
                                        ORDER BY ped.fecha_creacion DESC
                                        LIMIT 1
                                    ");
                                    $stmt->execute([$cliente['id'], $cliente['producto_favorito_id']]);
                                    $ultimo_pedido_favorito = $stmt->fetch(PDO::FETCH_ASSOC);
                                    ?>

                                    <?php if ($ultimo_pedido_favorito): ?>
                                        <?php if ($ultimo_pedido_favorito['pedido_estado'] == 'completado'): ?>
                                            <!-- Si el pedido está completado al 100% -->
                                            <button class="btn btn-secondary btn-sm" disabled data-tooltip="No hay pedidos activos">
                                                <i class="fas fa-truck me-1"></i>
                                                Sin pedido activo
                                            </button>
                                        <?php elseif ($ultimo_pedido_favorito['entregado'] == 0): ?>
                                            <!-- Si el producto favorito NO está entregado todavía -->
                                            <button type="button" class="btn btn-success btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEntregaParcial"
                                                data-detalle-id="<?php echo $ultimo_pedido_favorito['detalle_id']; ?>"
                                                data-producto="<?php echo htmlspecialchars($cliente['producto_favorito_nombre']); ?>"
                                                data-cantidad-pedida="<?php echo $ultimo_pedido_favorito['cantidad']; ?>"
                                                data-cantidad-entregada="<?php echo $ultimo_pedido_favorito['cantidad_entregada']; ?>"
                                                data-cliente-id="<?php echo $cliente['id']; ?>"
                                                data-pedido-id="<?php echo $ultimo_pedido_favorito['pedido_id']; ?>"
                                                data-tooltip="Entregar producto favorito">
                                                <i class="fas fa-truck me-1"></i>
                                                Entregar <?php echo htmlspecialchars($cliente['producto_favorito_nombre']); ?>
                                            </button>
                                        <?php else: ?>
                                            <!-- Si el producto favorito está entregado pero el pedido no está completado -->
                                            <button class="btn btn-info btn-sm" data-tooltip="Producto favorito entregado">
                                                <i class="fas fa-check-circle me-1"></i>
                                                Producto favorito entregado
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm" disabled data-tooltip="No hay pedidos activos">
                                            <i class="fas fa-truck me-1"></i>
                                            Sin pedido activo
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="btn btn-outline-primary btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditarCliente"
                                        data-id="<?php echo $cliente['id']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($cliente['nombre'] ?? ''); ?>"
                                        data-telefono="<?php echo htmlspecialchars($cliente['telefono']); ?>"
                                        data-email="<?php echo htmlspecialchars($cliente['email']); ?>"
                                        data-tooltip="Añadir producto favorito">
                                        <i class="fas fa-star me-1"></i>
                                        Definir favorito
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if (empty($clientes)): ?>
                        <div class="col-12 text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-users text-muted mb-3" style="font-size: 3rem;"></i>
                                <h3>No hay clientes registrados</h3>
                                <p class="text-muted">Comienza agregando clientes a tu sistema</p>
                                <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#modalAgregarCliente">
                                    <i class="fas fa-user-plus me-2"></i>Agregar Cliente
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                </table>
            </div>
    </div>
    <!-- El resto del código sigue igual... -->
<?php elseif ($mostrar_productos): ?>
    <div class="dashboard animate-fade-in">
        <div class="d-flex justify-content-between align-items-center mb-4">
            
            <h2><i class="fas fa-boxes me-2"></i>Gestión de Productos</h2>
            <div>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAgregarProducto">
                    <i class="fas fa-plus me-2"></i>Nuevo Producto
                </button>
                <a href="index.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-arrow-left me-2"></i>Volver
                </a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php
                switch ($_GET['success']) {
                    case 'producto_agregado':
                        echo "Producto añadido con éxito";
                        break;
                    case 'producto_editado':
                        echo "Producto actualizado con éxito";
                        break;
                    case 'producto_eliminado':
                        echo "Producto eliminado con éxito";
                        break;
                    default:
                        echo "Operación completada con éxito";
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php
                switch ($_GET['error']) {
                    case 'error_agregar_producto':
                        echo "Error al agregar el producto";
                        break;
                    case 'error_editar_producto':
                        echo "Error al actualizar el producto";
                        break;
                    case 'error_eliminar_producto':
                        echo "No se puede eliminar el producto porque está siendo utilizado en pedidos";
                        break;
                    default:
                        echo "Ha ocurrido un error en la operación";
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Tabla de productos -->
        <div class="table-responsive">
        <table class="table table-striped tabla-clickable tabla-adaptativa">
        <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Precio</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $productos = obtenerProductos($pdo);
                    foreach ($productos as $producto):
                    ?>
                        <tr>
                            <td><?php echo $producto['id']; ?></td>
                            <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?></td>
                            <td><?php echo number_format($producto['precio'], 2); ?> €</td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                    data-bs-target="#modalEditarProducto"
                                    data-id="<?php echo $producto['id']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                    data-descripcion="<?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?>"
                                    data-precio="<?php echo $producto['precio']; ?>">
                                    <i class="fas fa-edit me-1"></i>Editar
                                </button>
                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                    data-bs-target="#modalEliminarProducto"
                                    data-id="<?php echo $producto['id']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                    <i class="fas fa-trash-alt me-1"></i>Eliminar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($productos)): ?>
                        <tr>
                            <td colspan="5" class="text-center">
                                <i class="fas fa-box-open text-muted mb-3" style="font-size: 3rem;"></i>
                                <p>No hay productos registrados</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="pedidos-container animate-fade-in">
        <?php
            $pedidos = obtenerPedidosCliente($pdo, $cliente_id);
            $cliente_actual = null;
            foreach ($clientes as $c) {
                if ($c['id'] == $cliente_id) {
                    $cliente_actual = $c;
                    break;
                }
            }
        ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Pedidos de <?php echo htmlspecialchars($cliente_actual['nombre']); ?></h2>
            <div>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNuevoPedido">
                    <i class="fas fa-plus me-2"></i>
                    Nuevo Pedido
                </button>
                <a href="index.php" class="btn btn-secondary ms-2">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver a Clientes
                </a>
            </div>
        </div>

        <div class="tabs-simple mb-4">
            <a href="?cliente_id=<?php echo $cliente_id; ?>&ver_todos=1" class="tab-simple <?php echo $ver_todos ? 'active' : ''; ?>">
                <i class="fas fa-list me-1"></i>Todos los pedidos
            </a>
            <?php if ($pedido_id): ?>
            <a href="?cliente_id=<?php echo $cliente_id; ?>&pedido_id=<?php echo $pedido_id; ?>" class="tab-simple <?php echo (!$ver_todos && $pedido_id) ? 'active' : ''; ?>">
                <i class="fas fa-eye me-1"></i>Detalles del pedido #<?php echo $pedido_id; ?>
            </a>
            <?php endif; ?>
            <a href="#" class="tab-simple" data-bs-toggle="modal" data-bs-target="#modalNuevoPedido">
                <i class="fas fa-plus me-1"></i>Nuevo pedido
            </a>
        </div>

        <?php if ($ver_todos || !$pedido_id): ?>
            <div class="table-responsive">
            <table class="table table-striped tabla-clickable tabla-adaptativa">
            <thead>
                        <tr>
                            <th>ID</th>
                            <th>Estado</th>
                            <th>Total</th>
                            <th>Fecha Creación</th>
                            <th>Fecha Actualización</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido):
                            $total_pedido = calcularTotalPedido($pdo, $pedido['id']);
                        ?>
                            <tr>
                                <td><?php echo $pedido['id']; ?></td>
                                <td>
                                    <span class="badge badge-estado badge-<?php echo $pedido['estado']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $pedido['estado'])); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($total_pedido, 2); ?> €</td>
                                <td><?php echo $pedido['fecha_creacion']; ?></td>
                                <td><?php echo $pedido['fecha_actualizacion'] ?? '-'; ?></td>
                                <td>
                                    <a href="?cliente_id=<?php echo $cliente_id; ?>&pedido_id=<?php echo $pedido['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>
                                        Ver Detalles
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($pedidos)): ?>
                            <tr>
                                <td colspan="6" class="text-center">
                                    <i class="fas fa-inbox text-muted mb-3" style="font-size: 3rem;"></i>
                                    <p>No hay pedidos para este cliente</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <?php if ($pedido_id):
                $pedido_actual = obtenerPedido($pdo, $pedido_id);
                $detalles_pedido = obtenerDetallesPedido($pdo, $pedido_id);
                $total_pedido = calcularTotalPedido($pdo, $pedido_id);
        ?>
            <div class="card mt-4 animate-fade-in">
                <div class="card-header">
                    <h3 class="m-0">Detalles del Pedido #<?php echo $pedido_id; ?></h3>
                    <div>
                        <span class="badge badge-estado badge-<?php echo $pedido_actual['estado']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $pedido_actual['estado'])); ?>
                        </span>
                        <?php if ($pedido_actual['estado'] != 'completado'): ?>
                            <!-- El botón de editar solo aparece para pedidos NO completados -->
                            <button type="button" class="btn btn-warning ms-2" id="btnEditarPedido" data-bs-toggle="modal" data-bs-target="#modalEditarPedido" data-pedido-id="<?php echo $pedido_id; ?>">
                                <i class="fas fa-edit me-2"></i>Editar Pedido
                            </button>
                        <?php endif; ?>
                        <?php if ($pedido_actual['estado'] == 'completado'): ?>
                            <a href="?descargar_factura=1&pedido_id=<?php echo $pedido_id; ?>&cliente_id=<?php echo $cliente_id; ?>"
                                class="btn btn-success ms-2">
                                <i class="fas fa-file-invoice me-2"></i>Descargar Factura
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <strong>Fecha de creación:</strong>
                            <span><?php echo $pedido_actual['fecha_creacion']; ?></span>
                        </div>
                        <div class="col-md-4">
                            <strong>Última actualización:</strong>
                            <span><?php echo $pedido_actual['fecha_actualizacion'] ?? 'N/A'; ?></span>
                        </div>
                        <div class="col-md-4">
                            <strong>Total del pedido:</strong>
                            <span><?php echo number_format($total_pedido, 2); ?> €</span>
                        </div>
                    </div>

                    <h4 class="mb-3">Productos</h4>
                    <div class="table-responsive">
                    <table class="table table-striped tabla-clickable tabla-adaptativa">
                    <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Precio Unitario</th>
                                    <th>Cantidad Solicitada</th>
                                    <th>Cantidad Entregada</th>
                                    <th>Estado Entrega</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detalles_pedido as $detalle):
                                    $subtotal = $detalle['cantidad'] * $detalle['precio'];
                                    $estado_entrega = $detalle['entregado'] ? 'Completado' : ($detalle['cantidad_entregada'] > 0 ? 'Parcial' : 'Pendiente');
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($detalle['producto_nombre']); ?></td>
                                        <td><?php echo number_format($detalle['precio'], 2); ?> €</td>
                                        <td><?php echo formatearCantidad($detalle['cantidad']); ?></td>
                                        <td><?php echo formatearCantidad($detalle['cantidad_entregada']); ?></td>
                                        <td>
                                            <span class="badge <?php
                                            echo $estado_entrega == 'Completado' ? 'bg-success' : ($estado_entrega == 'Parcial' ? 'bg-warning' : 'bg-secondary');
                                            ?>">
                                                <?php echo $estado_entrega; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!$detalle['entregado']): ?>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                                        data-bs-target="#modalEntregaParcial"
                                                        data-detalle-id="<?php echo $detalle['id']; ?>"
                                                        data-producto="<?php echo htmlspecialchars($detalle['producto_nombre']); ?>"
                                                        data-cantidad-pedida="<?php echo $detalle['cantidad']; ?>"
                                                        data-cantidad-entregada="<?php echo $detalle['cantidad_entregada']; ?>">
                                                        <i class="fas fa-truck me-1"></i>
                                                        Registrar Entrega
                                                    </button>

                                                    <button class="btn btn-sm btn-success" data-bs-toggle="modal"
                                                        data-bs-target="#modalMarcarEntregado"
                                                        data-detalle-id="<?php echo $detalle['id']; ?>"
                                                        data-producto="<?php echo htmlspecialchars($detalle['producto_nombre']); ?>">
                                                        <i class="fas fa-check me-1"></i>
                                                        Marcar Completado
                                                    </button>

                                                    <?php if ($detalle['cantidad_entregada'] > 0): ?>
                                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                            data-bs-target="#modalCorregirEntrega"
                                                            data-detalle-id="<?php echo $detalle['id']; ?>"
                                                            data-producto="<?php echo htmlspecialchars($detalle['producto_nombre']); ?>"
                                                            data-cantidad-entregada="<?php echo $detalle['cantidad_entregada']; ?>">
                                                            <i class="fas fa-edit me-1"></i>
                                                            Corregir Entrega
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (empty($detalles_pedido)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <i class="fas fa-box-open text-muted mb-3" style="font-size: 3rem;"></i>
                                            <p>No hay productos en este pedido</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Modal Agregar Cliente -->
<div class="modal fade" id="modalAgregarCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>
                    Agregar Nuevo Cliente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="agregar_cliente">

                    <div class="mb-3">
                        <label for="nombre" class="form-label">
                            <i class="fas fa-user me-2"></i>Nombre
                        </label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>

                    <div class="mb-3">
                        <label for="telefono" class="form-label">
                            <i class="fas fa-phone me-2"></i>Teléfono
                        </label>
                        <input type="text" class="form-control" id="telefono" name="telefono">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-2"></i>Email
                        </label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>

                    <div class="mb-3">
                        <label for="producto_favorito" class="form-label">
                            <i class="fas fa-star me-2"></i>Producto Favorito
                        </label>
                        <select class="form-select" id="producto_favorito" name="producto_favorito_id">
                            <option value="">-- Seleccionar producto favorito --</option>
                            <?php
                            $productos = obtenerProductos($pdo);
                            foreach ($productos as $producto):
                            ?>
                                <option value="<?php echo $producto['id']; ?>">
                                    <?php echo htmlspecialchars($producto['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Tabla de Resumen de Productos -->
<div class="seccion-colapsable">
    <div class="seccion-encabezado">
        <h2 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Resumen de Producción</h2>
        <div>
            <button type="button" class="btn btn-outline-primary" id="toggleFavoritos" data-show-favs="<?php echo $mostrar_solo_favoritos ? '1' : '0'; ?>">
                <?php if ($mostrar_solo_favoritos): ?>
                    <i class="fas fa-th-list me-1"></i>Mostrar Todos
                <?php else: ?>
                    <i class="fas fa-star me-1"></i>Mostrar Solo Favoritos
                <?php endif; ?>
            </button>
        </div>
    </div>
    <div class="seccion-contenido">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th style="width: 50px;"></th>
                        <th>Producto</th>
                        <th class="text-center">Demandados</th>
                        <th class="text-center">Entregados</th>
                        <th class="text-center">Realizados</th>
                        <th class="text-center">Pendientes</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Usar la nueva función que soporta filtrado por favoritos
                    $resumen_productos = obtenerResumenProductosFavoritos($pdo, $mostrar_solo_favoritos);
                    $total_demandados = 0;
                    $total_entregados = 0;
                    $total_realizados = 0;
                    $total_pendientes = 0;

                    foreach ($resumen_productos as $producto):
                        // Acumular totales
                        $total_demandados += $producto['cantidad_total'];
                        $total_entregados += $producto['cantidad_entregada'];
                        $total_realizados += $producto['cantidad_realizada'];
                        $total_pendientes += $producto['cantidad_pendiente'];

                        // Solo mostrar productos con cantidades > 0 o si son favoritos
                        if ($producto['cantidad_total'] > 0 || $producto['cantidad_realizada'] > 0 || $producto['es_favorito']):
                    ?>
                            <tr>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm <?php echo $producto['es_favorito'] ? 'btn-warning' : 'btn-outline-secondary'; ?> btn-toggle-favorito" 
                                            data-producto-id="<?php echo $producto['id']; ?>"
                                            data-es-favorito="<?php echo $producto['es_favorito']; ?>"
                                            data-tooltip="<?php echo $producto['es_favorito'] ? 'Quitar de favoritos' : 'Añadir a favoritos'; ?>">
                                        <i class="fas fa-star"></i>
                                    </button>
                                </td>
                                <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                <td class="text-center"><?php echo formatearCantidad($producto['cantidad_total']); ?></td>
                                <td class="text-center"><?php echo formatearCantidad($producto['cantidad_entregada']); ?></td>
                                <td class="text-center">
                                    <span id="cantidad-realizada-<?php echo $producto['id']; ?>">
                                        <?php echo formatearCantidad($producto['cantidad_realizada']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span id="cantidad-pendiente-<?php echo $producto['id']; ?>" class="<?php echo $producto['cantidad_pendiente'] > 0 ? 'text-danger fw-bold' : 'text-success'; ?>">
                                        <?php echo formatearCantidad($producto['cantidad_pendiente']); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-primary btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalActualizarRealizado"
                                        data-producto-id="<?php echo $producto['id']; ?>"
                                        data-producto-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                        data-cantidad-total="<?php echo $producto['cantidad_total']; ?>"
                                        data-cantidad-realizada="<?php echo $producto['cantidad_realizada']; ?>"
                                        data-tooltip="Actualizar cantidad realizada">
                                        <i class="fas fa-edit me-1"></i>Actualizar
                                    </button>
                                </td>
                            </tr>
                        <?php
                        endif;
                    endforeach;

                    // Si no hay productos con cantidades
                    if (empty($resumen_productos) || ($total_demandados == 0 && $total_realizados == 0)):
                        ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-info-circle text-muted mb-3" style="font-size: 2rem;"></i>
                                <p class="mb-0">No hay productos pendientes de producción</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <!-- Fila de totales -->
                        <tr class="table-primary">
                            <td></td>
                            <td class="fw-bold">TOTALES</td>
                            <td class="text-center fw-bold"><?php echo formatearCantidad($total_demandados); ?></td>
                            <td class="text-center fw-bold"><?php echo formatearCantidad($total_entregados); ?></td>
                            <td class="text-center fw-bold"><?php echo formatearCantidad($total_realizados); ?></td>
                            <td class="text-center fw-bold <?php echo $total_pendientes > 0 ? 'text-danger' : 'text-success'; ?>">
                                <?php echo formatearCantidad($total_pendientes); ?>
                            </td>
                            <td></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



<!-- Modal Actualizar Cantidad Realizada -->
<div class="modal fade" id="modalActualizarRealizado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-industry me-2"></i>
                    Actualizar Cantidad Realizada
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="actualizar_cantidad_realizada">
                    <input type="hidden" name="producto_id" id="actualizar_producto_id">

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <span>Actualizando cantidad realizada para: <strong id="actualizar_producto_nombre"></strong></span>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Cantidad demandada:</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light" id="actualizar_cantidad_total"></span>
                                <span class="input-group-text"><i class="fas fa-box"></i></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Cantidad pendiente:</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light" id="actualizar_cantidad_pendiente"></span>
                                <span class="input-group-text"><i class="fas fa-hourglass-half"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="cantidad_realizada" class="form-label fw-bold">
                            <i class="fas fa-check-circle me-2"></i>Nueva cantidad realizada:
                        </label>
                        <input type="number" class="form-control form-control-lg" id="cantidad_realizada" name="cantidad_realizada" min="0" step="0.001" required>
                        <div class="form-text">
                            Indique el número total de unidades que ha realizado hasta ahora.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Agregar Producto -->
<div class="modal fade" id="modalAgregarProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>
                    Agregar Nuevo Producto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="agregar_producto">

                    <div class="mb-3">
                        <label for="nombre_producto" class="form-label">
                            <i class="fas fa-tag me-2"></i>Nombre del Producto
                        </label>
                        <input type="text" class="form-control" id="nombre_producto" name="nombre_producto" required>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion_producto" class="form-label">
                            <i class="fas fa-info-circle me-2"></i>Descripción (Opcional)
                        </label>
                        <textarea class="form-control" id="descripcion_producto" name="descripcion_producto" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="precio_producto" class="form-label">
                            <i class="fas fa-euro-sign me-2"></i>Precio
                        </label>
                        <input type="number" class="form-control" id="precio_producto" name="precio_producto" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Guardar Producto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Producto -->
<div class="modal fade" id="modalEditarProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    Editar Producto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="editar_producto">
                    <input type="hidden" name="id_producto" id="editar_id_producto">

                    <div class="mb-3">
                        <label for="editar_nombre_producto" class="form-label">
                            <i class="fas fa-tag me-2"></i>Nombre del Producto
                        </label>
                        <input type="text" class="form-control" id="editar_nombre_producto" name="nombre_producto" required>
                    </div>

                    <div class="mb-3">
                        <label for="editar_descripcion_producto" class="form-label">
                            <i class="fas fa-info-circle me-2"></i>Descripción (Opcional)
                        </label>
                        <textarea class="form-control" id="editar_descripcion_producto" name="descripcion_producto" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="editar_precio_producto" class="form-label">
                            <i class="fas fa-euro-sign me-2"></i>Precio
                        </label>
                        <input type="number" class="form-control" id="editar_precio_producto" name="precio_producto" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Actualizar Producto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Cliente -->
<div class="modal fade" id="modalEditarCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-edit me-2"></i>
                    Editar Cliente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="editar_cliente">
                    <input type="hidden" name="cliente_id" id="editar_cliente_id">

                    <div class="mb-3">
                        <label for="editar_nombre" class="form-label">
                            <i class="fas fa-user me-2"></i>Nombre
                        </label>
                        <input type="text" class="form-control" id="editar_nombre" name="nombre" required>
                    </div>

                    <div class="mb-3">
                        <label for="editar_telefono" class="form-label">
                            <i class="fas fa-phone me-2"></i>Teléfono
                        </label>
                        <input type="text" class="form-control" id="editar_telefono" name="telefono">
                    </div>

                    <div class="mb-3">
                        <label for="editar_email" class="form-label">
                            <i class="fas fa-envelope me-2"></i>Email
                        </label>
                        <input type="email" class="form-control" id="editar_email" name="email">
                    </div>

                    <div class="mb-3">
                        <label for="editar_producto_favorito" class="form-label">
                            <i class="fas fa-star me-2"></i>Producto Favorito
                        </label>
                        <select class="form-select" id="editar_producto_favorito" name="producto_favorito_id">
                            <option value="">-- Seleccionar producto favorito --</option>
                            <?php
                            $productos = obtenerProductos($pdo);
                            foreach ($productos as $producto):
                            ?>
                                <option value="<?php echo $producto['id']; ?>">
                                    <?php echo htmlspecialchars($producto['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Modal Eliminar Cliente -->
<div class="modal fade" id="modalEliminarCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-times me-2"></i>
                    Eliminar Cliente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="eliminar_cliente">
                    <input type="hidden" name="cliente_id" id="eliminar_cliente_id">

                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ¿Está seguro de que desea eliminar al cliente <strong id="eliminar_cliente_nombre"></strong>?
                    </div>

                    <p class="text-danger">
                        <small>Esta acción no se puede deshacer. Solo es posible eliminar clientes que no tengan pedidos asociados.</small>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-2"></i>Eliminar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Pedido -->
<div class="modal fade" id="modalEditarPedido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    Editar Pedido #<span id="editar_pedido_numero"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="editar_pedido">
                    <input type="hidden" name="pedido_id" id="editar_pedido_id">
                    <input type="hidden" name="cliente_id" value="<?php echo $cliente_id; ?>">

                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Importante:</strong> No podrá reducir la cantidad de un producto por debajo de la cantidad ya entregada.
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Precio</th>
                                    <th>Cantidad Solicitada</th>
                                    <th>Cantidad Entregada</th>
                                    <th>Nueva Cantidad</th>
                                </tr>
                            </thead>
                            <tbody id="editar_pedido_productos">
                                <!-- La tabla se llenará dinámicamente con JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



<!-- Modal Eliminar Producto -->
<div class="modal fade" id="modalEliminarProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-trash-alt me-2"></i>
                    Eliminar Producto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="eliminar_producto">
                    <input type="hidden" name="id_producto" id="eliminar_id_producto">

                    <p>
                        <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                        ¿Está seguro de que desea eliminar el producto <strong id="eliminar_nombre_producto"></strong>?
                    </p>
                    <p class="text-danger">
                        <small>Esta acción no se puede deshacer. Solo puede eliminar productos que no estén asociados a ningún pedido.</small>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-2"></i>Eliminar Producto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Agregar Productos para Cliente Seleccionado -->
<div class="modal fade" id="modalAgregarProductos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cart-plus me-2"></i>
                    Añadir Productos para <span id="cliente-nombre-seleccionado"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear_pedido">
                    <input type="hidden" name="cliente_id" id="cliente-id-seleccionado">

                    <h6 class="mb-3">
                        <i class="fas fa-boxes me-2"></i>
                        Selecciona los productos y cantidades:
                    </h6>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Precio</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $productos = obtenerProductos($pdo);
                                foreach ($productos as $producto):
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                        <td><?php echo number_format($producto['precio'], 2); ?> €</td>
                                        <td>
                                            <input type="number" class="form-control producto-cantidad" name="cantidad_<?php echo $producto['id']; ?>" min="0" value="0">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Crear Pedido con Productos
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Nuevo Pedido -->
<div class="modal fade" id="modalNuevoPedido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cart-plus me-2"></i>
                    Crear Nuevo Pedido
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear_pedido">
                    <input type="hidden" name="cliente_id" value="<?php echo $cliente_id; ?>">

                    <h6 class="mb-3">
                        <i class="fas fa-boxes me-2"></i>
                        Selecciona los productos y cantidades:
                    </h6>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Precio</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $productos = obtenerProductos($pdo);
                                foreach ($productos as $producto):
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                        <td><?php echo number_format($producto['precio'], 2); ?> €</td>
                                        <td>
                                            <input type="number" class="form-control producto-cantidad" name="cantidad_<?php echo $producto['id']; ?>" min="0" value="0" step="0.001">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar Pedido
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- REEMPLAZAR COMPLETAMENTE EL MODAL DE ENTREGA PARCIAL ACTUAL CON ESTE CÓDIGO -->
<!-- Este código debe ir en la parte inferior de vista.php, donde están todos los modales -->

<!-- Modal Entrega Parcial -->
<!-- Modal Entrega Parcial -->
<div class="modal fade" id="modalEntregaParcial" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-truck me-2"></i>
                    Registrar Entrega
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="formEntregaParcial">
                <input type="hidden" name="accion" value="registrar_entrega_parcial">
                <input type="hidden" name="detalle_pedido_id" id="entrega_parcial_detalle_id">
                <input type="hidden" name="cliente_id" id="entrega_parcial_cliente_id">
                <input type="hidden" name="pedido_id" id="entrega_parcial_pedido_id">
                <input type="hidden" name="volver_clientes" id="entrega_parcial_volver_clientes" value="0">

                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Registrando entrega para: <strong id="entrega_parcial_producto"></strong>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Cantidad pedida:</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light" id="cantidad_pedida"></span>
                                <span class="input-group-text"><i class="fas fa-boxes"></i></span>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Ya entregado:</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light" id="cantidad_ya_entregada"></span>
                                <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="cantidad_entrega" class="form-label fw-bold">
                            <i class="fas fa-box me-2"></i>Cantidad a entregar ahora
                        </label>
                        <input type="number" class="form-control form-control-lg" id="cantidad_entrega" name="cantidad"
                            min="0.001" step="0.001" required>
                        <small class="text-muted">Unidades disponibles para entregar: <span id="cantidad_disponible" class="fw-bold"></span></small>
                    </div>

                    <div class="mb-3">
                        <label for="notas_entrega" class="form-label">
                            <i class="fas fa-comment me-2"></i>Notas o comentarios (opcional)
                        </label>
                        <textarea class="form-control" id="notas_entrega" name="notas"
                            placeholder="Añade cualquier comentario relevante sobre esta entrega"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-success" id="btnRegistrarEntrega">
                        <i class="fas fa-check me-2"></i>Registrar Entrega
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Modal Corregir Entrega -->
<div class="modal fade" id="modalCorregirEntrega" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    Corregir Entrega
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <input type="hidden" name="accion" value="corregir_entrega_parcial">
                <input type="hidden" name="detalle_pedido_id" id="corregir_entrega_detalle_id">
                <input type="hidden" name="cliente_id" value="<?php echo $cliente_id; ?>">
                <input type="hidden" name="pedido_id" value="<?php echo $pedido_id; ?>">

                <div class="modal-body">
                    <p>
                        <strong>Producto:</strong>
                        <span id="corregir_entrega_producto"></span>
                    </p>
                    <div class="mb-3">
                        <label for="nueva_cantidad_entrega" class="form-label">
                            <i class="fas fa-box me-2"></i>Nueva Cantidad Entregada
                        </label>
                        <input type="number" class="form-control" id="nueva_cantidad_entrega" name="nueva_cantidad"
                            min="0" max="" step="0.001" required>
                    </div>
                    <div class="mb-3">
                        <label for="notas_correccion" class="form-label">
                            <i class="fas fa-notes-medical me-2"></i>Notas (opcional)
                        </label>
                        <textarea class="form-control" id="notas_correccion" name="notas"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Corregir Entrega
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Marcar Producto Entregado -->
<div class="modal fade" id="modalMarcarEntregado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-2"></i>
                    Marcar Producto como Entregado
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <input type="hidden" name="accion" value="marcar_producto_entregado">
                <input type="hidden" name="detalle_pedido_id" id="marcar_entregado_detalle_id">
                <input type="hidden" name="cliente_id" value="<?php echo $cliente_id; ?>">
                <input type="hidden" name="pedido_id" value="<?php echo $pedido_id; ?>">

                <div class="modal-body">
                    <p>
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        ¿Está seguro de que desea marcar
                        <strong id="marcar_entregado_producto"></strong>
                        como completamente entregado?
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Marcar Completado
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function formatearCantidadJS(cantidad) {
    // Convertir a número para asegurar la comparación
    cantidad = parseFloat(cantidad);
    
    // Verificar si la cantidad tiene decimales
    if (cantidad === Math.floor(cantidad)) {
        // Es un número entero (sin decimales)
        return cantidad.toFixed(0);
    } else {
        // Tiene decimales, mostramos hasta 3 decimales
        return cantidad.toFixed(3);
    }
}
    document.addEventListener('DOMContentLoaded', function() {
        const modalEntregaParcial = document.getElementById('modalEntregaParcial');
        if (modalEntregaParcial) {
            modalEntregaParcial.addEventListener('show.bs.modal', function(event) {
                // Obtener el botón que activó el modal
                const button = event.relatedTarget;

                // Extraer información del botón
                const detalleId = button.getAttribute('data-detalle-id');
                const producto = button.getAttribute('data-producto');
                const cantidadPedida = parseFloat(button.getAttribute('data-cantidad-pedida')) || 0;
                const cantidadEntregada = parseFloat(button.getAttribute('data-cantidad-entregada')) || 0;
                const clienteId = button.getAttribute('data-cliente-id');
                const pedidoId = button.getAttribute('data-pedido-id');

                // Calcular cantidad disponible para entregar (con 3 decimales)
                const cantidadDisponible = (cantidadPedida - cantidadEntregada).toFixed(3);

                // Actualizar elementos del modal
                document.getElementById('entrega_parcial_detalle_id').value = detalleId;
                document.getElementById('entrega_parcial_producto').textContent = producto;
                document.getElementById('entrega_parcial_cliente_id').value = clienteId;
                document.getElementById('entrega_parcial_pedido_id').value = pedidoId;
                document.getElementById('cantidad_pedida').textContent = formatearCantidadJS(cantidadPedida);
                document.getElementById('cantidad_ya_entregada').textContent = formatearCantidadJS(cantidadEntregada);
                document.getElementById('cantidad_disponible').textContent = formatearCantidadJS(cantidadDisponible);

                // Configurar campo de cantidad - sugerir la cantidad pendiente total
                const inputCantidad = document.getElementById('cantidad_entrega');
                inputCantidad.max = cantidadDisponible;
                inputCantidad.min = 0.001; // Para permitir decimales pequeños
                inputCantidad.value = ""; // Sugerir entregar todo lo pendiente
                inputCantidad.select(); // Seleccionar el valor para facilitar cambios

                // Limpiar el campo de notas
                document.getElementById('notas_entrega').value = '';

                // Determinar si estamos en la vista de clientes o en la vista de pedidos
                const urlParams = new URLSearchParams(window.location.search);
                const enVistaClientes = !urlParams.has('pedido_id');
                document.getElementById('entrega_parcial_volver_clientes').value = enVistaClientes ? '1' : '0';
            });
            

            // Validación del formulario
            const formEntregaParcial = document.getElementById('formEntregaParcial');
            formEntregaParcial.addEventListener('submit', function(event) {
                const cantidad = parseFloat(document.getElementById('cantidad_entrega').value);
                const disponible = parseFloat(document.getElementById('cantidad_disponible').textContent);
                
                if (isNaN(cantidad) || cantidad <= 0) {
                    event.preventDefault();
                    alert('Por favor, ingresa una cantidad válida mayor que cero.');
                    return false;
                }
                
                if (cantidad > disponible) {
                    event.preventDefault();
                    alert('La cantidad a entregar no puede ser mayor que la cantidad disponible.');
                    return false;
                }
                
                // Si todo está correcto, permitir el envío del formulario
                return true;
            });
        }

        // Modal de corrección de entrega
        const modalCorregirEntrega = document.getElementById('modalCorregirEntrega');
        if (modalCorregirEntrega) {
            modalCorregirEntrega.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const detalleId = button.getAttribute('data-detalle-id');
                const producto = button.getAttribute('data-producto');
                const cantidadEntregada = parseFloat(button.getAttribute('data-cantidad-entregada'));

                document.getElementById('corregir_entrega_detalle_id').value = detalleId;
                document.getElementById('corregir_entrega_producto').textContent = producto;

                const inputCantidad = document.getElementById('nueva_cantidad_entrega');
                inputCantidad.value = cantidadEntregada;
            });
        }

        // Modal de marcar producto como entregado
        const modalMarcarEntregado = document.getElementById('modalMarcarEntregado');
        if (modalMarcarEntregado) {
            modalMarcarEntregado.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const detalleId = button.getAttribute('data-detalle-id');
                const producto = button.getAttribute('data-producto');

                document.getElementById('marcar_entregado_detalle_id').value = detalleId;
                document.getElementById('marcar_entregado_producto').textContent = producto;
            });
        }

        // Modal Añadir Productos para Cliente
        const modalAgregarProductos = document.getElementById('modalAgregarProductos');
        if (modalAgregarProductos) {
            modalAgregarProductos.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const clienteId = button.getAttribute('data-cliente-id');
                const clienteNombre = button.getAttribute('data-cliente-nombre');

                document.getElementById('cliente-id-seleccionado').value = clienteId;
                document.getElementById('cliente-nombre-seleccionado').textContent = clienteNombre;
            });
        }

        // Modal Editar Producto
        const modalEditarProducto = document.getElementById('modalEditarProducto');
        if (modalEditarProducto) {
            modalEditarProducto.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const nombre = button.getAttribute('data-nombre');
                const descripcion = button.getAttribute('data-descripcion');
                const precio = button.getAttribute('data-precio');

                document.getElementById('editar_id_producto').value = id;
                document.getElementById('editar_nombre_producto').value = nombre;
                document.getElementById('editar_descripcion_producto').value = descripcion;
                document.getElementById('editar_precio_producto').value = precio;
            });
        }

        // Modal Eliminar Producto
        const modalEliminarProducto = document.getElementById('modalEliminarProducto');
        if (modalEliminarProducto) {
            modalEliminarProducto.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const nombre = button.getAttribute('data-nombre');

                document.getElementById('eliminar_id_producto').value = id;
                document.getElementById('eliminar_nombre_producto').textContent = nombre;
            });
        }


        const modalEliminarCliente = document.getElementById('modalEliminarCliente');
        if (modalEliminarCliente) {
            modalEliminarCliente.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const clienteId = button.getAttribute('data-id');
                const clienteNombre = button.getAttribute('data-nombre');

                document.getElementById('eliminar_cliente_id').value = clienteId;
                document.getElementById('eliminar_cliente_nombre').textContent = clienteNombre;
            });
        }
        const clienteCards = document.querySelectorAll('.cliente-card');
    
    clienteCards.forEach(card => {
        // Obtener el enlace de "Ver Pedidos" dentro de la tarjeta
        const verPedidosLink = card.querySelector('a[href*="cliente_id"]');
        
        if (verPedidosLink) {
            // Obtener la URL del enlace
            const linkUrl = verPedidosLink.getAttribute('href');
            
            // Hacer que toda la tarjeta sea clickable
            card.style.cursor = 'pointer';
            card.addEventListener('click', function(event) {
                // Prevenir que el click se propague a los botones de acción dentro de la tarjeta
                if (!event.target.closest('.cliente-acciones') && 
                    !event.target.closest('.btn-toggle-favorito') && 
                    !event.target.closest('.btn-outline-primary') && 
                    !event.target.closest('.btn-success')) {
                    window.location.href = linkUrl;
                }
            });
            
            // Efecto visual al pasar el cursor
            card.addEventListener('mouseenter', function() {
                if (!card.classList.contains('hover-active')) {
                    card.classList.add('hover-active');
                }
            });
            
            card.addEventListener('mouseleave', function() {
                card.classList.remove('hover-active');
            });
        }
    });

        // Modal Editar Pedido
        const modalEditarPedido = document.getElementById('modalEditarPedido');
        if (modalEditarPedido) {
            modalEditarPedido.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const pedidoId = button.getAttribute('data-pedido-id');

                document.getElementById('editar_pedido_id').value = pedidoId;
                document.getElementById('editar_pedido_numero').textContent = pedidoId;

                // Cargar los detalles del pedido mediante AJAX
                fetch(`index.php?ajax=get_pedido_detalles&pedido_id=${pedidoId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert('Error al cargar los detalles del pedido: ' + data.error);
                            return;
                        }

                        // Obtener todos los productos disponibles
                        fetch('index.php?ajax=get_productos')
                            .then(response => response.json())
                            .then(productosData => {
                                const productos = productosData.productos;
                                const detalles = data.detalles;
                                const tbodyElement = document.getElementById('editar_pedido_productos');

                                // Limpiar el contenido actual
                                tbodyElement.innerHTML = '';

                                // Para cada producto, crear una fila
                                productos.forEach(producto => {
                                    // Buscar si este producto está en el pedido
                                    const detalle = detalles.find(d => d.producto_id == producto.id);
                                    // Usar parseFloat en lugar de parseInt para permitir decimales
                                    const cantidadSolicitada = detalle ? parseFloat(detalle.cantidad) : 0;
                                    const cantidadEntregada = detalle ? parseFloat(detalle.cantidad_entregada) : 0;

                                    // Crear la fila
                                    const row = document.createElement('tr');
                                    row.innerHTML = `
                                <td>${producto.nombre}</td>
                                <td>${parseFloat(producto.precio).toFixed(2)} €</td>
                                <td>${cantidadSolicitada}</td>
                                <td>${cantidadEntregada}</td>
                                <td>
                                    <input type="number" 
                                           class="form-control" 
                                           name="cantidad_${producto.id}" 
                                           value="${cantidadSolicitada}"
                                           min="${cantidadEntregada}" 
                                           step="0.001"
                                           ${detalle && cantidadEntregada > 0 ? 'data-tiene-entregas="true"' : ''}
                                           required>
                                </td>
                            `;

                                    tbodyElement.appendChild(row);
                                });
                            })
                            .catch(error => {
                                console.error('Error al cargar productos:', error);
                                alert('Error al cargar la lista de productos. Por favor, inténtelo de nuevo.');
                            });
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al cargar los detalles del pedido. Por favor, inténtelo de nuevo.');
                    });
            });
        }
        const modalActualizarRealizado = document.getElementById('modalActualizarRealizado');

        if (modalActualizarRealizado) {
            modalActualizarRealizado.addEventListener('show.bs.modal', function(event) {
                // Obtener el botón que activó el modal
                const button = event.relatedTarget;

                // Extraer información del botón
                const productoId = button.getAttribute('data-producto-id');
                const productoNombre = button.getAttribute('data-producto-nombre');
                const cantidadTotal = parseInt(button.getAttribute('data-cantidad-total')) || 0;
                const cantidadRealizada = parseInt(button.getAttribute('data-cantidad-realizada')) || 0;

                // Calcular cantidad pendiente
                const cantidadPendiente = Math.max(0, cantidadTotal - cantidadRealizada);

                // Actualizar elementos del modal
                document.getElementById('actualizar_producto_id').value = productoId;
                document.getElementById('actualizar_producto_nombre').textContent = productoNombre;
                document.getElementById('actualizar_cantidad_total').textContent = cantidadTotal;
                document.getElementById('actualizar_cantidad_pendiente').textContent = cantidadPendiente;

                // Establece el valor actual en el campo
                document.getElementById('cantidad_realizada').value = cantidadRealizada;
            });
        }

        // Modal Editar Cliente
        const modalEditarCliente = document.getElementById('modalEditarCliente');
        if (modalEditarCliente) {
            modalEditarCliente.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const clienteId = button.getAttribute('data-id');
                const clienteNombre = button.getAttribute('data-nombre');
                const clienteTelefono = button.getAttribute('data-telefono');
                const clienteEmail = button.getAttribute('data-email');
                const productoFavoritoId = button.getAttribute('data-producto-favorito-id');

                document.getElementById('editar_cliente_id').value = clienteId;
                document.getElementById('editar_nombre').value = clienteNombre;
                document.getElementById('editar_telefono').value = clienteTelefono;
                document.getElementById('editar_email').value = clienteEmail;

                // Seleccionar el producto favorito
                const selectProducto = document.getElementById('editar_producto_favorito');
                if (productoFavoritoId) {
                    for (let i = 0; i < selectProducto.options.length; i++) {
                        if (selectProducto.options[i].value === productoFavoritoId) {
                            selectProducto.options[i].selected = true;
                            break;
                        }
                    }
                } else {
                    selectProducto.options[0].selected = true;
                }
            });
        }

        const tablasClickables = document.querySelectorAll('.tabla-clickable');
    tablasClickables.forEach(tabla => {
        const filas = tabla.querySelectorAll('tbody tr');
        
        filas.forEach(fila => {
            const enlace = fila.querySelector('a');
            if (enlace) {
                const url = enlace.getAttribute('href');
                
                fila.style.cursor = 'pointer';
                fila.addEventListener('click', function(event) {
                    // Solo navegar si no se hizo click en un botón o enlace
                    if (!event.target.closest('button') && !event.target.closest('a')) {
                        window.location.href = url;
                    }
                });
            }
        });
    });

    const seccionesColapsables = document.querySelectorAll('.seccion-colapsable');
    seccionesColapsables.forEach(seccion => {
        const encabezado = seccion.querySelector('.seccion-encabezado');
        const contenido = seccion.querySelector('.seccion-contenido');
        
        if (encabezado && contenido) {
            encabezado.style.cursor = 'pointer';
            
            // Añadir icono de toggle
            const toggleIcon = document.createElement('i');
            toggleIcon.className = 'fas fa-chevron-down ms-2';
            encabezado.appendChild(toggleIcon);
            
            encabezado.addEventListener('click', function() {
                // Toggle de la visibilidad del contenido
                if (contenido.style.display === 'none') {
                    contenido.style.display = 'block';
                    toggleIcon.className = 'fas fa-chevron-down ms-2';
                    seccion.classList.remove('collapsed');
                } else {
                    contenido.style.display = 'none';
                    toggleIcon.className = 'fas fa-chevron-right ms-2';
                    seccion.classList.add('collapsed');
                }
            });
        }
    });
    // Mejorar la interacción con los formularios
    const formulariosInteractivos = document.querySelectorAll('form.form-interactivo');
    formulariosInteractivos.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            // Destacar el campo activo
            input.addEventListener('focus', function() {
                this.closest('.mb-3').classList.add('campo-activo');
            });
            
            input.addEventListener('blur', function() {
                this.closest('.mb-3').classList.remove('campo-activo');
            });
            
            // Validación en tiempo real
            input.addEventListener('input', function() {
                if (this.required && this.value.trim() === '') {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            });
        });
    });

        const toggleFavoritosBtn = document.getElementById('toggleFavoritos');
    if (toggleFavoritosBtn) {
        toggleFavoritosBtn.addEventListener('click', function() {
            // Leer el estado actual del botón
            const mostrarSoloFavoritos = toggleFavoritosBtn.getAttribute('data-show-favs') === '1';
            // Navegar a la URL con el parámetro invertido
            window.location.href = 'index.php?mostrar_solo_favoritos=' + (mostrarSoloFavoritos ? '0' : '1');
        });
    }
        // Funcionalidad para el buscador de clientes
        const buscador = document.getElementById('buscadorClientes');
    if (buscador) {
        buscador.addEventListener('input', function() {
            const texto = this.value.toLowerCase().trim();
            const tarjetas = document.querySelectorAll('.cliente-card');
            
            tarjetas.forEach(tarjeta => {
                const nombreCliente = tarjeta.querySelector('.cliente-name').textContent.toLowerCase();
                const telefonoElement = tarjeta.querySelector('.cliente-contact:nth-child(2)');
                const emailElement = tarjeta.querySelector('.cliente-contact:nth-child(3)');
                
                const telefono = telefonoElement ? telefonoElement.textContent.toLowerCase() : '';
                const email = emailElement ? emailElement.textContent.toLowerCase() : '';
                
                const productoFavoritoElement = tarjeta.querySelector('.badge');
                const productoFavorito = productoFavoritoElement ? productoFavoritoElement.textContent.toLowerCase() : '';
                
                if (nombreCliente.includes(texto) || 
                    telefono.includes(texto) || 
                    email.includes(texto) ||
                    productoFavorito.includes(texto)) {
                    tarjeta.closest('.col-md-6').style.display = '';
                } else {
                    tarjeta.closest('.col-md-6').style.display = 'none';
                }
            });
        });
    }
    
    // Menú móvil
    const menuToggle = document.getElementById('menuToggle');
    const menuClose = document.getElementById('menuClose');
    const menuBackdrop = document.getElementById('menuBackdrop');
    const mobileMenu = document.getElementById('mobileMenu');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            mobileMenu.classList.add('show');
            menuBackdrop.classList.add('show');
            document.body.style.overflow = 'hidden';
        });
    }
    
    if (menuClose) {
        menuClose.addEventListener('click', function() {
            mobileMenu.classList.remove('show');
            menuBackdrop.classList.remove('show');
            document.body.style.overflow = '';
        });
    }
    
    if (menuBackdrop) {
        menuBackdrop.addEventListener('click', function() {
            mobileMenu.classList.remove('show');
            menuBackdrop.classList.remove('show');
            document.body.style.overflow = '';
        });
    }
    
    // Tooltips personalizados
    const elementosConTooltip = document.querySelectorAll('[data-tooltip]');
    elementosConTooltip.forEach(elemento => {
        elemento.classList.add('tooltip-personalizado');
    });
    
    // Manejar el redimensionamiento para evitar problemas de menú en diferentes orientaciones
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            mobileMenu.classList.remove('show');
            menuBackdrop.classList.remove('show');
            document.body.style.overflow = '';
        }
    });
    
    // Botones para marcar/desmarcar productos como favoritos
    const btnsFavorito = document.querySelectorAll('.btn-toggle-favorito');
    btnsFavorito.forEach(btn => {
        btn.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            
            // Cambiar apariencia inmediatamente (antes de la petición AJAX)
            const esFavorito = this.getAttribute('data-es-favorito') === '1';
            this.classList.toggle('btn-warning');
            this.classList.toggle('btn-outline-secondary');
            this.setAttribute('data-es-favorito', !esFavorito ? '1' : '0');
            
            // Proceder con la petición AJAX original
            const productoId = this.getAttribute('data-producto-id');
            
            fetch('index.php?ajax=toggle_favorito&producto_id=' + productoId + '&es_favorito=' + (!esFavorito ? 1 : 0))
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        // Si falla, revertir los cambios visuales
                        console.error('Error al cambiar estado de favorito');
                        this.classList.toggle('btn-warning');
                        this.classList.toggle('btn-outline-secondary');
                        this.setAttribute('data-es-favorito', esFavorito ? '1' : '0');
                    }
                })
                .catch(error => {
                    // Si falla, revertir los cambios visuales
                    console.error('Error:', error);
                    this.classList.toggle('btn-warning');
                    this.classList.toggle('btn-outline-secondary');
                    this.setAttribute('data-es-favorito', esFavorito ? '1' : '0');
                    alert('Ha ocurrido un error al cambiar el estado del favorito');
                });
        });
    });

    const isMobile = window.innerWidth < 768;
    
    if (isMobile) {
        // Hacer que los botones de acción del cliente tengan un área táctil mayor
        const botonesAccion = document.querySelectorAll('.btn-cliente-accion');
    botonesAccion.forEach(btn => {
        // Asegurar que el botón tenga posición relativa para centrado correcto
        btn.style.position = 'relative';
        
        // Encontrar el icono dentro del botón
        const icono = btn.querySelector('i');
        if (icono) {
            // Establecer estilos para centrado perfecto
            icono.style.position = 'absolute';
            icono.style.top = '50%';
            icono.style.left = '50%';
            icono.style.transform = 'translate(-50%, -50%)';
            icono.style.margin = '0';
        }
    });
    
    // Mejorar interacción en dispositivos móviles
    if (window.innerWidth < 768) {
        // Aumentar área táctil para todos los botones
        const todosLosBotones = document.querySelectorAll('.btn');
        todosLosBotones.forEach(btn => {
            if (btn.classList.contains('btn-sm')) {
                btn.classList.remove('btn-sm');
            }
        });
        
        // Reorganizar grupos de botones en tablas para mejor usabilidad
        const gruposBotones = document.querySelectorAll('.btn-group');
        gruposBotones.forEach(grupo => {
            // Cambiar de flex-row a flex-column
            grupo.classList.add('flex-column');
            grupo.style.width = '100%';
            
            // Asegurar que cada botón ocupe todo el ancho
            const botones = grupo.querySelectorAll('.btn');
            botones.forEach(boton => {
                boton.style.width = '100%';
                boton.style.marginBottom = '5px'; 
                boton.style.borderRadius = '.25rem';
            });
        });
    }
    
    // Evitar navegación indeseada al hacer clic en botones de acción
    document.querySelectorAll('.cliente-card').forEach(card => {
        const accionesBtns = card.querySelectorAll('.cliente-acciones button');
        accionesBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    });
        
        // Reorganizar botones en grupos para mejor usabilidad móvil
        const gruposBotones = document.querySelectorAll('.btn-group');
        gruposBotones.forEach(grupo => {
            grupo.classList.remove('btn-group');
            grupo.classList.add('d-flex', 'flex-column', 'gap-2');
            
            // Hacer que cada botón ocupe todo el ancho disponible
            const botones = grupo.querySelectorAll('.btn');
            botones.forEach(btn => {
                btn.classList.add('w-100');
                btn.style.marginBottom = '0.5rem';
            });
        });
        
        // Mejorar visibilidad de badges y estados
        const badges = document.querySelectorAll('.badge');
        badges.forEach(badge => {
            badge.style.fontSize = '0.8rem';
            badge.style.padding = '0.5em 0.85em';
        });
    }
    
    });
</script>
</body>

</html>