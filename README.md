# Sistema de Gestión de Pedidos

Un sistema completo para la gestión de pedidos, clientes y productos desarrollado en PHP con una base de datos MySQL.

## Descripción

Este sistema permite gestionar pedidos, clientes y productos de forma eficiente. Está diseñado para pequeñas y medianas empresas que necesitan llevar un control de sus pedidos, entrega de productos, facturación y relación con clientes.

## Características principales

- **Gestión de clientes**: Agregar, editar y eliminar información de clientes.
- **Gestión de productos**: Administrar catálogo de productos con precios y descripciones.
- **Gestión de pedidos**: Crear pedidos asociados a clientes, con múltiples productos y cantidades.
- **Seguimiento de entregas**: Registrar entregas parciales o completas de productos.
- **Generación de facturas**: Crear automáticamente facturas en PDF cuando un pedido se completa.
- **Interfaz responsiva**: Diseño adaptable a diferentes dispositivos (PC, tabletas, móviles).

## Estructura del proyecto

```
├── config.php                 # Configuración global de la aplicación
├── db_connect.php             # Conexión a la base de datos
├── funciones.php              # Funciones generales del sistema
├── clientes_funciones.php     # Funciones para gestión de clientes
├── productos_funciones.php    # Funciones para gestión de productos
├── pedidos_funciones.php      # Funciones para gestión de pedidos
├── entregas_funciones.php     # Funciones para registro de entregas
├── facturas_funciones.php     # Funciones para generación de facturas
├── index.php                  # Controlador principal de la aplicación
├── vista.php                  # Vista principal (interfaz de usuario)
├── style.css                  # Estilos CSS de la aplicación
└── /facturas/                 # Directorio para almacenar facturas generadas
```

## Módulos del sistema

### Módulo de Clientes
- Agregar nuevos clientes
- Editar información existente
- Eliminar clientes (si no tienen pedidos asociados)
- Asignar productos favoritos a clientes
- Visualizar estadísticas de entrega de productos

### Módulo de Productos
- Crear nuevos productos
- Editar productos existentes
- Eliminar productos (si no están en uso en pedidos)
- Gestionar precios y descripciones

### Módulo de Pedidos
- Crear pedidos para clientes
- Editar pedidos existentes
- Visualizar historial de pedidos por cliente
- Seguimiento del estado de los pedidos (pendiente, en proceso, parcial, completado)

### Módulo de Entregas
- Registrar entregas parciales
- Marcar productos como completamente entregados
- Corregir errores en entregas
- Actualización automática del estado del pedido según entregas

### Módulo de Facturas
- Generación automática de facturas PDF
- Descarga de facturas para pedidos completados

## Tecnologías utilizadas

- **Backend**: PHP 7.4+
- **Base de datos**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript
- **Frameworks/Librerías**: Bootstrap 5, Font Awesome
- **Generación de PDFs**: Implementación básica (expandible con FPDF, TCPDF o mPDF)

## Instalación

1. Clonar o descargar los archivos en el directorio del servidor web
2. Crear una base de datos MySQL con las tablas necesarias (esquema proporcionado abajo)
3. Configurar los parámetros de conexión en `config.php` y `db_connect.php`
4. Asegurarse de que el directorio `/facturas/` tenga permisos de escritura
5. Acceder a la aplicación a través del navegador

## Estructura de la base de datos

### Tabla `clientes`
```sql
CREATE TABLE clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  telefono VARCHAR(20),
  email VARCHAR(100),
  producto_favorito_id INT,
  FOREIGN KEY (producto_favorito_id) REFERENCES productos(id)
);
```

### Tabla `productos`
```sql
CREATE TABLE productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  descripcion TEXT,
  precio DECIMAL(10, 2) NOT NULL
);
```

### Tabla `pedidos`
```sql
CREATE TABLE pedidos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  estado ENUM('pendiente', 'en_proceso', 'parcial', 'completado') NOT NULL DEFAULT 'pendiente',
  fecha_creacion DATETIME NOT NULL,
  fecha_actualizacion DATETIME,
  archivo_factura VARCHAR(255),
  FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);
```

### Tabla `detalles_pedido`
```sql
CREATE TABLE detalles_pedido (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id INT NOT NULL,
  producto_id INT NOT NULL,
  cantidad INT NOT NULL,
  cantidad_entregada INT NOT NULL DEFAULT 0,
  entregado TINYINT(1) NOT NULL DEFAULT 0,
  fecha_ultima_entrega DATETIME,
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
  FOREIGN KEY (producto_id) REFERENCES productos(id)
);
```

### Tabla `historial_entregas`
```sql
CREATE TABLE historial_entregas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  detalle_pedido_id INT NOT NULL,
  cantidad INT NOT NULL,
  notas TEXT,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (detalle_pedido_id) REFERENCES detalles_pedido(id)
);
```

## Flujo de trabajo

1. **Gestión de clientes y productos**: Registrar clientes y productos en el sistema
2. **Creación de pedidos**: Crear pedidos para clientes seleccionando productos y cantidades
3. **Seguimiento de entregas**: Registrar entregas parciales o completas de los productos
4. **Completado de pedidos**: El sistema actualiza automáticamente el estado de los pedidos
5. **Facturación**: Generación automática de facturas para pedidos completados

## Seguridad

El sistema implementa:
- Uso de PDO con sentencias preparadas para prevenir inyección SQL
- Validación y sanitización de entrada de datos
- Manejo de errores con mensajes apropiados
- Protección contra acceso no autorizado a datos

## Consideraciones

- Las contraseñas de la base de datos están incluidas en los archivos para desarrollo, pero deben ser sustituidas en un entorno de producción.
- El sistema básico de generación de facturas PDF debe ser ampliado según las necesidades específicas.
- Se recomienda implementar un sistema de autenticación de usuarios para mayor seguridad.

## Personalización y extensión

El sistema está diseñado para ser fácilmente adaptable:
- Modificar `vista.php` y `style.css` para cambiar la apariencia
- Añadir nuevas funcionalidades creando archivos de funciones adicionales
- Integrar con sistemas de pagos, envío de correos, o notificaciones

## Créditos

Desarrollado como una herramienta de gestión para pequeñas y medianas empresas con foco en la usabilidad y eficiencia.
