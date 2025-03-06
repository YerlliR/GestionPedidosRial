<?php
// Function to handle invoice download
function manejarDescargaFactura($pdo, $pedido_id, $cliente_id) {
    try {
        // Check if the order is completed
        $stmt = $pdo->prepare("SELECT archivo_factura, estado FROM pedidos WHERE id = ?");
        $stmt->execute([$pedido_id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Change the file_exists check
        $archivo_factura = $pedido['archivo_factura'] ?? '';
        
        if (!$pedido) {
            error_log("Pedido no encontrado");
            header("Location: index.php?error=pedido_no_encontrado");
            exit;
        }

        if ($pedido['estado'] != 'completado') {
            error_log("El pedido no está completado. Estado actual: " . $pedido['estado']);
            header("Location: index.php?cliente_id=" . $cliente_id . "&pedido_id=" . $pedido_id . "&error=pedido_no_completado");
            exit;
        }

        // Check if the file exists
        if (empty($archivo_factura) || !file_exists($archivo_factura)) {
            error_log("Archivo de factura no existe. Intentando regenerar.");
            try {
                $archivo_factura = generarFacturaPDF($pdo, $pedido_id);
            } catch (Exception $e) {
                error_log("Error al regenerar la factura: " . $e->getMessage());
                header("Location: index.php?cliente_id=" . $cliente_id . "&pedido_id=" . $pedido_id . "&error=generacion_factura_fallida");
                exit;
            }
        }

        // Check again if the file exists
        if (!file_exists($archivo_factura)) {
            error_log("Archivo de factura aún no existe después de regeneración");
            header("Location: index.php?cliente_id=" . $cliente_id . "&pedido_id=" . $pedido_id . "&error=factura_no_disponible");
            exit;
        }

        // Prepare PDF download
        $nombre_archivo = basename($archivo_factura);
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $nombre_archivo . '"');
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        header('Cache-Control: private');
        header('Pragma: private');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-Length: ' . filesize($archivo_factura));
        
        // Read and display the file directly
        readfile($archivo_factura);
        exit;
    } catch (Exception $e) {
        // Handle any unexpected error
        error_log("Error inesperado al descargar factura: " . $e->getMessage());
        header("Location: index.php?cliente_id=" . $cliente_id . "&pedido_id=" . $pedido_id . "&error=error_descarga");
        exit;
    }
}
?>