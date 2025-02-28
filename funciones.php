<?php
// Function to generate PDF invoice
function generarFacturaPDF($pdo, $pedido_id) {
    // Get order data
    $stmt = $pdo->prepare("
        SELECT p.*, c.nombre as cliente_nombre, c.email as cliente_email, c.telefono as cliente_telefono
        FROM pedidos p
        JOIN clientes c ON p.cliente_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pedido) {
        throw new Exception("Pedido no encontrado");
    }
    
    // Get order details
    $stmt = $pdo->prepare("
        SELECT dp.*, p.nombre as producto_nombre, p.descripcion as producto_descripcion, p.precio
        FROM detalles_pedido dp
        JOIN productos p ON dp.producto_id = p.id
        WHERE dp.pedido_id = ?
    ");
    $stmt->execute([$pedido_id]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Directory for invoices
    $dir_facturas = 'facturas/';
    if (!is_dir($dir_facturas)) {
        mkdir($dir_facturas, 0755, true);
    }
    
    // Generate filename
    $nombre_archivo = $dir_facturas . 'factura_' . $pedido_id . '_' . date('Ymd_His') . '.pdf';
    
    // Here you would use a PDF library like FPDF, TCPDF, or mPDF to generate the PDF
    // This is a simplified example using FPDF
    
    // You would need to include the FPDF library and generate the PDF
    // For this example, we'll just create a placeholder file
    
    $contenido = "FACTURA #" . $pedido_id . "\n";
    $contenido .= "Fecha: " . date('Y-m-d H:i:s') . "\n\n";
    $contenido .= "Cliente: " . $pedido['cliente_nombre'] . "\n";
    $contenido .= "Email: " . $pedido['cliente_email'] . "\n";
    $contenido .= "Teléfono: " . $pedido['cliente_telefono'] . "\n\n";
    $contenido .= "DETALLES DEL PEDIDO:\n";
    
    $total = 0;
    foreach ($detalles as $detalle) {
        $subtotal = $detalle['cantidad'] * $detalle['precio'];
        $total += $subtotal;
        
        $contenido .= $detalle['producto_nombre'] . " x " . $detalle['cantidad'] . " - " . 
                     number_format($detalle['precio'], 2) . "€ = " . number_format($subtotal, 2) . "€\n";
    }
    
    $contenido .= "\nTOTAL: " . number_format($total, 2) . "€";
    
    // Write to file
    file_put_contents($nombre_archivo, $contenido);
    
    return $nombre_archivo;
}

// Other utility functions can go here

// Function to get delivery history
function obtenerHistorialEntregas($pdo, $detalle_pedido_id) {
    $stmt = $pdo->prepare("
        SELECT * FROM historial_entregas
        WHERE detalle_pedido_id = ?
        ORDER BY fecha_registro DESC
    ");
    $stmt->execute([$detalle_pedido_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>