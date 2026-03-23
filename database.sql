-- ============================================================
-- SISTEMA CATÁLOGO Y VENTAS — Base de Datos Completa
-- ============================================================

CREATE DATABASE IF NOT EXISTS catalogo_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE catalogo_db;

-- ============================================================
-- TABLA: usuarios
-- ============================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'vendedor') NOT NULL DEFAULT 'vendedor',
    estado TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: categorias
-- ============================================================
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: productos
-- ============================================================
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    precio_compra DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    precio_venta DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: producto_imagenes
-- ============================================================
CREATE TABLE IF NOT EXISTS producto_imagenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    ruta_imagen VARCHAR(255) NOT NULL,
    orden TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: ventas
-- estado: 1=activa, 0=anulada
-- ============================================================
CREATE TABLE IF NOT EXISTS ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tipo_pago ENUM('efectivo', 'qr') NOT NULL DEFAULT 'efectivo',
    estado TINYINT(1) NOT NULL DEFAULT 1,
    motivo_anulacion VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: venta_detalle
-- ============================================================
CREATE TABLE IF NOT EXISTS venta_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: egresos
-- ============================================================
CREATE TABLE IF NOT EXISTS egresos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    concepto VARCHAR(255) NOT NULL,
    monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    fecha DATE NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DATOS INICIALES
-- Password para ambos usuarios: password
-- ============================================================
INSERT INTO usuarios (nombre, email, password, rol, estado) VALUES
('JOE', 'tancarajoe@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1),
('Vendedor', 'vendedor@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendedor', 1);



USE catalogo_db;

-- 1. Columnas nuevas en productos
ALTER TABLE productos
  ADD COLUMN IF NOT EXISTS stock_docenas      INT NOT NULL DEFAULT 0     AFTER stock,
  ADD COLUMN IF NOT EXISTS stock_unidades     INT NOT NULL DEFAULT 0     AFTER stock_docenas,
  ADD COLUMN IF NOT EXISTS precio_docena      DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER precio_venta,
  ADD COLUMN IF NOT EXISTS unidades_por_docena INT NOT NULL DEFAULT 12   AFTER precio_docena;

-- Migrar stock existente → todo a unidades sueltas
UPDATE productos SET stock_unidades = stock WHERE stock_unidades = 0;

-- 2. Columna tipo_unidad en venta_detalle
ALTER TABLE venta_detalle
  ADD COLUMN IF NOT EXISTS tipo_unidad ENUM('unidad','docena') NOT NULL DEFAULT 'unidad' AFTER cantidad;

-- 3. Tabla banners del carrusel
CREATE TABLE IF NOT EXISTS banner_carousel (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  titulo         VARCHAR(150) NOT NULL DEFAULT '',
  subtitulo      VARCHAR(255) NOT NULL DEFAULT '',
  imagen         VARCHAR(255) NOT NULL DEFAULT '',
  enlace         VARCHAR(255) NOT NULL DEFAULT '',
  orden          INT          NOT NULL DEFAULT 0,
  activo         TINYINT(1)   NOT NULL DEFAULT 1,
  fecha_creacion TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabla configuración del sitio
CREATE TABLE IF NOT EXISTS configuracion_sitio (
  clave  VARCHAR(80)  NOT NULL PRIMARY KEY,
  valor  TEXT         NOT NULL DEFAULT '',
  label  VARCHAR(150) NOT NULL DEFAULT '',
  tipo   ENUM('text','textarea','boolean','color') NOT NULL DEFAULT 'text'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Valores por defecto
INSERT IGNORE INTO configuracion_sitio (clave, valor, label, tipo) VALUES
  ('nombre_tienda',    'WT Store',   'Nombre de la tienda',     'text'),
  ('descripcion_hero', 'Explora nuestro catálogo completo con los mejores productos.', 'Descripción del hero', 'textarea'),
  ('telefono',         '',           'Teléfono de contacto',    'text'),
  ('whatsapp',         '',           'Número WhatsApp',         'text'),
  ('direccion',        '',           'Dirección',               'text'),
  ('color_acento',     '#4f46e5',    'Color principal',         'color'),
  ('mostrar_precio',   '0',          'Mostrar precio público',  'boolean'),
  ('footer_texto',     'Sistema de Catálogo y Ventas', 'Texto del footer', 'text');



USE catalogo_db;


CREATE TABLE IF NOT EXISTS producto_colores (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  producto_id INT NOT NULL,
  color       VARCHAR(80) NOT NULL,
  hex_code    VARCHAR(7)  NOT NULL DEFAULT '#6b7280',
  docenas     INT         NOT NULL DEFAULT 0,
  unidades    INT         NOT NULL DEFAULT 0,
  activo      TINYINT(1)  NOT NULL DEFAULT 1,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
  UNIQUE KEY uq_prod_color (producto_id, color)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


ALTER TABLE venta_detalle
  ADD COLUMN IF NOT EXISTS color_id  INT         NULL AFTER tipo_unidad,
  ADD COLUMN IF NOT EXISTS color_nombre VARCHAR(80) NULL AFTER color_id;
