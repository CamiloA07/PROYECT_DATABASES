-- ============================================================
--  BASE DE DATOS: Negocio de Empanadas
--  Incluye: Tablas, Triggers, Procedimientos Almacenados, Vistas
-- ============================================================

DROP DATABASE IF EXISTS empanadas_db;
CREATE DATABASE empanadas_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE empanadas_db;

-- ============================================================
-- TABLAS
-- ============================================================

CREATE TABLE Usuario (
    id_usuario   INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL,
    apellido     VARCHAR(100) NOT NULL,
    cargo        VARCHAR(80)  NOT NULL,
    rol          ENUM('admin','empleado') NOT NULL DEFAULT 'empleado',
    salario      DECIMAL(10,2) NOT NULL CHECK (salario > 0),
    fecha_pago   DATE         NOT NULL,
    contrasena   VARCHAR(255) NOT NULL,
    activo       TINYINT(1)   NOT NULL DEFAULT 1
);

CREATE TABLE Cliente (
    id_cliente  INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(150) NOT NULL,
    telefono    VARCHAR(20),
    direccion   VARCHAR(255)
);

CREATE TABLE Producto (
    id_producto  INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(120) NOT NULL,
    descripcion  TEXT,
    precio       DECIMAL(10,2) NOT NULL CHECK (precio > 0),
    categoria    VARCHAR(80)  NOT NULL,
    activo       TINYINT(1)   NOT NULL DEFAULT 1
);

CREATE TABLE Inventario (
    id_inventario  INT AUTO_INCREMENT PRIMARY KEY,
    id_producto    INT          NOT NULL,
    cantidad       INT          NOT NULL DEFAULT 0 CHECK (cantidad >= 0),
    cantidad_minima INT         NOT NULL DEFAULT 5,
    fecha_ingreso  DATE         NOT NULL,
    fecha_caducidad DATE        NOT NULL,
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto)
);

CREATE TABLE Venta (
    id_venta      INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente    INT          NOT NULL,
    id_usuario    INT          NOT NULL,
    fecha         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total         DECIMAL(10,2) NOT NULL DEFAULT 0 CHECK (total >= 0),
    metodo_pago   ENUM('efectivo','tarjeta','transferencia') NOT NULL DEFAULT 'efectivo',
    FOREIGN KEY (id_cliente)  REFERENCES Cliente(id_cliente),
    FOREIGN KEY (id_usuario)  REFERENCES Usuario(id_usuario)
);

CREATE TABLE Detalle_Venta (
    id_detalle      INT AUTO_INCREMENT PRIMARY KEY,
    id_venta        INT          NOT NULL,
    id_producto     INT          NOT NULL,
    cantidad        INT          NOT NULL CHECK (cantidad > 0),
    precio_unitario DECIMAL(10,2) NOT NULL CHECK (precio_unitario > 0),
    subtotal        DECIMAL(10,2) GENERATED ALWAYS AS (cantidad * precio_unitario) STORED,
    FOREIGN KEY (id_venta)    REFERENCES Venta(id_venta),
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto)
);

CREATE TABLE Egresos (
    id_egreso    INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario   INT          NOT NULL,
    categoria    VARCHAR(80)  NOT NULL,
    descripcion  TEXT         NOT NULL,
    monto        DECIMAL(10,2) NOT NULL CHECK (monto > 0),
    fecha        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
);

-- Tabla de log de alertas de inventario bajo
CREATE TABLE Log_Inventario (
    id_log      INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT  NOT NULL,
    mensaje     TEXT NOT NULL,
    fecha       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TRIGGERS
-- ============================================================

DELIMITER $$

-- 1. Al insertar un detalle de venta: descuenta inventario y actualiza total de venta
CREATE TRIGGER trg_after_insert_detalle
AFTER INSERT ON Detalle_Venta
FOR EACH ROW
BEGIN
    -- Actualizar total de la venta
    UPDATE Venta
    SET total = (SELECT SUM(subtotal) FROM Detalle_Venta WHERE id_venta = NEW.id_venta)
    WHERE id_venta = NEW.id_venta;

    -- Descontar del inventario (FIFO: el lote más próximo a vencer)
    UPDATE Inventario
    SET cantidad = cantidad - NEW.cantidad
    WHERE id_producto = NEW.id_producto
      AND cantidad >= NEW.cantidad
    ORDER BY fecha_caducidad ASC
    LIMIT 1;

    -- Alertar si el inventario queda bajo el mínimo
    IF (SELECT cantidad FROM Inventario
        WHERE id_producto = NEW.id_producto
        ORDER BY fecha_caducidad ASC LIMIT 1) <
       (SELECT cantidad_minima FROM Inventario
        WHERE id_producto = NEW.id_producto LIMIT 1) THEN
        INSERT INTO Log_Inventario (id_producto, mensaje)
        VALUES (NEW.id_producto,
                CONCAT('⚠️ Stock bajo para producto id=', NEW.id_producto,
                       '. Revisar inventario.'));
    END IF;
END$$

-- 2. Validar que la venta tenga un cliente real (no NULL) antes de insertar
CREATE TRIGGER trg_before_insert_venta
BEFORE INSERT ON Venta
FOR EACH ROW
BEGIN
    IF NEW.id_cliente IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: Una venta no puede existir sin cliente.';
    END IF;
END$$

-- 3. Evitar que el salario de un empleado quede por debajo del mínimo ($1 160 000 COP)
CREATE TRIGGER trg_before_update_salario
BEFORE UPDATE ON Usuario
FOR EACH ROW
BEGIN
    IF NEW.salario < 1160000 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: El salario no puede ser inferior al mínimo legal vigente.';
    END IF;
END$$

-- 4. Alerta cuando se ingresa un producto con fecha de caducidad < 3 días
CREATE TRIGGER trg_before_insert_inventario
BEFORE INSERT ON Inventario
FOR EACH ROW
BEGIN
    IF NEW.fecha_caducidad < DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN
        INSERT INTO Log_Inventario (id_producto, mensaje)
        VALUES (NEW.id_producto,
                CONCAT('⚠️ Producto id=', NEW.id_producto,
                       ' ingresado con caducidad próxima: ', NEW.fecha_caducidad));
    END IF;
END$$

DELIMITER ;

-- ============================================================
-- PROCEDIMIENTOS ALMACENADOS
-- ============================================================

DELIMITER $$

-- SP 1: Registrar una venta completa (encabezado + detalle)
CREATE PROCEDURE sp_registrar_venta(
    IN p_id_cliente   INT,
    IN p_id_usuario   INT,
    IN p_metodo_pago  VARCHAR(20),
    IN p_id_producto  INT,
    IN p_cantidad     INT,
    OUT p_id_venta    INT
)
BEGIN
    DECLARE v_precio DECIMAL(10,2);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Verificar stock suficiente
    IF (SELECT COALESCE(SUM(cantidad),0) FROM Inventario WHERE id_producto = p_id_producto) < p_cantidad THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stock insuficiente para realizar la venta.';
    END IF;

    -- Obtener precio actual del producto
    SELECT precio INTO v_precio FROM Producto WHERE id_producto = p_id_producto;

    -- Insertar encabezado de venta
    INSERT INTO Venta (id_cliente, id_usuario, metodo_pago)
    VALUES (p_id_cliente, p_id_usuario, p_metodo_pago);

    SET p_id_venta = LAST_INSERT_ID();

    -- Insertar detalle (el trigger actualiza el total e inventario)
    INSERT INTO Detalle_Venta (id_venta, id_producto, cantidad, precio_unitario)
    VALUES (p_id_venta, p_id_producto, p_cantidad, v_precio);

    COMMIT;
END$$

-- SP 2: Registrar egreso
CREATE PROCEDURE sp_registrar_egreso(
    IN p_id_usuario  INT,
    IN p_categoria   VARCHAR(80),
    IN p_descripcion TEXT,
    IN p_monto       DECIMAL(10,2)
)
BEGIN
    INSERT INTO Egresos (id_usuario, categoria, descripcion, monto)
    VALUES (p_id_usuario, p_categoria, p_descripcion, p_monto);
END$$

-- SP 3: Pagar empleados (actualiza fecha_pago y registra el egreso)
CREATE PROCEDURE sp_pagar_empleados()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_id INT;
    DECLARE v_salario DECIMAL(10,2);
    DECLARE v_nombre VARCHAR(200);
    DECLARE cur CURSOR FOR
        SELECT id_usuario, CONCAT(nombre,' ',apellido), salario
        FROM Usuario WHERE activo = 1;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;
    loop_pago: LOOP
        FETCH cur INTO v_id, v_nombre, v_salario;
        IF done THEN LEAVE loop_pago; END IF;

        -- Registrar egreso por pago de nómina
        INSERT INTO Egresos (id_usuario, categoria, descripcion, monto)
        VALUES (1, 'Nómina', CONCAT('Pago de salario a ', v_nombre), v_salario);

        -- Actualizar fecha de pago al mes siguiente
        UPDATE Usuario SET fecha_pago = DATE_ADD(fecha_pago, INTERVAL 1 MONTH)
        WHERE id_usuario = v_id;
    END LOOP;
    CLOSE cur;
END$$

-- SP 4: Login de usuario (retorna rol si credenciales correctas)
CREATE PROCEDURE sp_login(
    IN  p_nombre     VARCHAR(100),
    IN  p_contrasena VARCHAR(255),
    OUT p_rol        VARCHAR(20),
    OUT p_id         INT
)
BEGIN
    SELECT rol, id_usuario INTO p_rol, p_id
    FROM Usuario
    WHERE nombre = p_nombre
      AND contrasena = SHA2(p_contrasena, 256)
      AND activo = 1
    LIMIT 1;
END$$

-- SP 5: Reponer inventario
CREATE PROCEDURE sp_reponer_inventario(
    IN p_id_producto     INT,
    IN p_cantidad        INT,
    IN p_fecha_caducidad DATE
)
BEGIN
    INSERT INTO Inventario (id_producto, cantidad, fecha_ingreso, fecha_caducidad)
    VALUES (p_id_producto, p_cantidad, CURDATE(), p_fecha_caducidad);
END$$

DELIMITER ;

-- ============================================================
-- VISTAS
-- ============================================================

-- Vista 1: Reporte de ventas del día con detalle
CREATE VIEW v_ventas_hoy AS
SELECT
    v.id_venta,
    v.fecha,
    CONCAT(u.nombre,' ',u.apellido) AS empleado,
    c.nombre                        AS cliente,
    p.nombre                        AS producto,
    dv.cantidad,
    dv.precio_unitario,
    dv.subtotal,
    v.total,
    v.metodo_pago
FROM Venta v
JOIN Usuario      u  ON v.id_usuario  = u.id_usuario
JOIN Cliente      c  ON v.id_cliente  = c.id_cliente
JOIN Detalle_Venta dv ON v.id_venta   = dv.id_venta
JOIN Producto     p  ON dv.id_producto = p.id_producto
WHERE DATE(v.fecha) = CURDATE();

-- Vista 2: Inventario con alertas de stock bajo
CREATE VIEW v_inventario_alertas AS
SELECT
    i.id_inventario,
    p.nombre          AS producto,
    p.categoria,
    i.cantidad        AS stock_actual,
    i.cantidad_minima AS stock_minimo,
    i.fecha_caducidad,
    DATEDIFF(i.fecha_caducidad, CURDATE()) AS dias_para_vencer,
    CASE
        WHEN i.cantidad < i.cantidad_minima             THEN '🔴 Stock bajo'
        WHEN DATEDIFF(i.fecha_caducidad,CURDATE()) <= 3 THEN '🟡 Próximo a vencer'
        ELSE '🟢 OK'
    END AS estado
FROM Inventario i
JOIN Producto p ON i.id_producto = p.id_producto
ORDER BY estado DESC, dias_para_vencer ASC;

-- Vista 3: Resumen financiero mensual (ingresos vs egresos)
CREATE VIEW v_resumen_financiero AS
SELECT
    DATE_FORMAT(fecha,'%Y-%m') AS mes,
    'Ingreso'                  AS tipo,
    SUM(total)                 AS monto
FROM Venta
GROUP BY mes
UNION ALL
SELECT
    DATE_FORMAT(fecha,'%Y-%m') AS mes,
    'Egreso'                   AS tipo,
    SUM(monto)                 AS monto
FROM Egresos
GROUP BY mes
ORDER BY mes DESC, tipo;

-- Vista 4: Productos más vendidos
CREATE VIEW v_productos_top AS
SELECT
    p.id_producto,
    p.nombre,
    p.categoria,
    SUM(dv.cantidad)  AS total_vendido,
    SUM(dv.subtotal)  AS ingresos_generados
FROM Detalle_Venta dv
JOIN Producto p ON dv.id_producto = p.id_producto
GROUP BY p.id_producto, p.nombre, p.categoria
ORDER BY total_vendido DESC;

-- Vista 5: Egresos agrupados por categoría
CREATE VIEW v_egresos_por_categoria AS
SELECT
    categoria,
    COUNT(*)         AS num_registros,
    SUM(monto)       AS total_gastado,
    MIN(fecha)       AS primer_egreso,
    MAX(fecha)       AS ultimo_egreso
FROM Egresos
GROUP BY categoria
ORDER BY total_gastado DESC;

-- ============================================================
-- DATOS INICIALES (SEEDS)
-- ============================================================

-- Admin (contraseña: admin123)
INSERT INTO Usuario (nombre, apellido, cargo, rol, salario, fecha_pago, contrasena)
VALUES ('Carlos', 'Gómez', 'Administrador', 'admin', 2500000, '2025-06-01',
        SHA2('admin123', 256));

-- Empleados
INSERT INTO Usuario (nombre, apellido, cargo, rol, salario, fecha_pago, contrasena) VALUES
('Laura',  'Pérez',  'Vendedora', 'empleado', 1300000, '2025-06-01', SHA2('emp001', 256)),
('Miguel', 'Torres', 'Cocinero',  'empleado', 1400000, '2025-06-01', SHA2('emp002', 256));

-- Clientes
INSERT INTO Cliente (nombre, telefono, direccion) VALUES
('Ana Rodríguez',  '3101234567', 'Calle 10 #5-20'),
('Pedro Martínez', '3209876543', 'Carrera 8 #12-15'),
('Sin definir',    NULL,         NULL);  -- cliente genérico para ventas rápidas

-- Productos
INSERT INTO Producto (nombre, descripcion, precio, categoria) VALUES
('Empanada de pipián',  'Rellena de papas con ají amarillo', 2500, 'Salada'),
('Empanada de pollo',   'Rellena de pollo desmechado',       3000, 'Salada'),
('Empanada de queso',   'Rellena de queso campesino',        2500, 'Salada'),
('Empanada de dulce',   'Rellena de arequipe',               2500, 'Dulce'),
('Ají verde',           'Salsa de ají casera (50ml)',        1000, 'Complemento');

-- Inventario inicial
INSERT INTO Inventario (id_producto, cantidad, cantidad_minima, fecha_ingreso, fecha_caducidad) VALUES
(1, 100, 10, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 DAY)),
(2,  80, 10, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 DAY)),
(3,  60, 10, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 DAY)),
(4,  50, 10, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 DAY)),
(5,  30,  5, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY));

-- Egreso de ejemplo (arriendo)
INSERT INTO Egresos (id_usuario, categoria, descripcion, monto)
VALUES (1, 'Arriendo', 'Pago mensual local comercial', 800000);

SELECT '✅ Base de datos empanadas_db creada exitosamente.' AS resultado;
