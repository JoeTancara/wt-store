# 🛍️ WT Store — Sistema de Catálogo y Ventas

Sistema web completo con PHP puro + MySQL + Bootstrap 5. Arquitectura MVC simple, modo claro/oscuro, roles de usuario.

---

## 📋 Requisitos

- PHP 7.4 o superior
- MySQL 5.7 / MariaDB 10+
- Servidor Apache/Nginx (XAMPP, WAMP, Laragon)
- Extensiones PHP: `mysqli`, `gd` o `imagick` (para imágenes)

---

## ⚙️ Instalación

### 1. Copiar archivos

Coloca la carpeta `catalogo/` en tu directorio web:
- **XAMPP:** `C:/xampp/htdocs/catalogo/`
- **Linux:** `/var/www/html/catalogo/`

### 2. Crear la base de datos

Importa el archivo `database.sql` desde phpMyAdmin o terminal:

```bash
mysql -u root -p < database.sql
```

O desde phpMyAdmin:
1. Crear base de datos `catalogo_db`
2. Importar `database.sql`

### 3. Configurar conexión

Edita `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // tu usuario MySQL
define('DB_PASS', '');          // tu contraseña MySQL
define('DB_NAME', 'catalogo_db');
define('BASE_URL', 'http://localhost/catalogo');  // URL de tu proyecto
```

### 4. Permisos de la carpeta uploads

```bash
chmod 755 uploads/productos/
# o en Windows, asegúrate que la carpeta sea escribible
```

### 5. Acceder al sistema

- **Catálogo público:** `http://localhost/catalogo/`
- **Panel admin:** `http://localhost/catalogo/login.php`

---

## 👤 Credenciales por defecto

| Rol | Email | Contraseña |
|-----|-------|------------|
| Administrador | admin@sistema.com | password |
| Vendedor | vendedor@sistema.com | password |

> ⚠️ **Cambia las contraseñas** después del primer inicio de sesión.

---

## 📁 Estructura del Proyecto

```
catalogo/
├── index.php                  # Catálogo público
├── login.php                  # Inicio de sesión
├── logout.php                 # Cerrar sesión
├── producto-detalle.php       # Detalle de producto
├── database.sql               # Schema + datos iniciales
│
├── config/
│   ├── database.php           # Conexión BD + constantes
│   └── auth.php               # Sesiones + helpers
│
├── models/
│   ├── Usuario.php
│   ├── Categoria.php
│   ├── Producto.php
│   ├── Venta.php
│   └── Egreso.php
│
├── controllers/
│   ├── AuthController.php
│   ├── ProductoController.php
│   ├── CategoriaController.php
│   ├── VentaController.php
│   ├── EgresoController.php
│   └── UsuarioController.php
│
├── views/
│   └── partials/
│       ├── header_public.php
│       ├── footer_public.php
│       ├── header_admin.php
│       ├── footer_admin.php
│       └── sidebar_admin.php
│
├── admin/
│   ├── dashboard.php          # Dashboard con stats y gráfica
│   ├── productos.php          # CRUD productos + imágenes
│   ├── categorias.php         # CRUD categorías (admin only)
│   ├── ventas.php             # POS + historial ventas
│   ├── egresos.php            # CRUD egresos (admin only)
│   ├── usuarios.php           # CRUD usuarios (admin only)
│   ├── perfil.php             # Editar perfil propio
│   ├── get_imagenes.php       # AJAX: imágenes de producto
│   └── get_venta_detalle.php  # AJAX: detalle de venta
│
├── assets/
│   ├── css/style.css          # Estilos + variables dark/light
│   └── js/app.js              # JavaScript: carrito, temas, etc.
│
└── uploads/
    └── productos/             # Imágenes subidas (auto-creado)
```

---

## 🎨 Funcionalidades

### Panel Administrador
- ✅ Dashboard con estadísticas y gráfica de ventas
- ✅ CRUD completo de productos con hasta 3 imágenes
- ✅ CRUD de categorías
- ✅ POS para realizar ventas (descuenta stock automáticamente)
- ✅ Historial de ventas con detalle
- ✅ Gestión de egresos
- ✅ Gestión de usuarios
- ✅ Edición de perfil

### Panel Vendedor
- ✅ Dashboard básico
- ✅ Ver y buscar productos
- ✅ Actualizar stock
- ✅ Realizar ventas (POS)
- ✅ Ver historial de ventas

### Catálogo Público
- ✅ Listado de productos con filtro por categoría
- ✅ Búsqueda de productos
- ✅ Vista detalle del producto con galería
- ✅ Indicador de stock

### Sistema
- ✅ Modo claro / oscuro (persiste en localStorage)
- ✅ Diseño responsive (mobile-first)
- ✅ Validación de formularios
- ✅ Sanitización de datos
- ✅ Control de roles (admin / vendedor)
- ✅ Sesiones seguras
- ✅ Tipo de pago: Efectivo / QR

---

## 🔐 Seguridad implementada

- Contraseñas hasheadas con `password_hash()` (bcrypt)
- Consultas con prepared statements (previene SQL Injection)
- Sanitización con `htmlspecialchars()` + `strip_tags()`
- Control de roles por sesión en cada página
- Validación de tipos de archivo en uploads

---

## 🛠️ Personalización

### Cambiar nombre del sistema
Busca y reemplaza `WT Store` en los archivos PHP.

### Cambiar colores
Edita las variables CSS en `assets/css/style.css`:
```css
:root {
  --accent: #4f46e5;  /* Color principal */
  --success: #10b981;
  --danger: #ef4444;
}
```

### Cambiar URL base
En `config/database.php`:
```php
define('BASE_URL', 'http://tudominio.com/catalogo');
```
