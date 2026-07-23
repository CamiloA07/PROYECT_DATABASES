# Proyecto Bases de Datos - Curso de Bases de Datos

Proyecto académico que consolida el desarrollo de cinco sistemas de base de datos individuales, implementados con stack LAMP (Linux, Apache, MySQL, PHP).

## Descripción

Este proyecto integra cinco bases de datos independientes en una sola aplicación web (`index.php`), cada una implementando los siguientes conceptos fundamentales de bases de datos:

- **CRUD completo** (Crear, Leer, Actualizar, Eliminar) mediante formularios web
- **Trigger** (disparador)
- **Stored Procedure** (procedimiento almacenado)
- **View** (vista)
- **Transacción** (transaction con commit/rollback)

## Bases de datos incluidas

1. **pizzamaster_db** - Gestión de pedidos de pizzería
2. **zapateria_db** - Gestión de inventario de zapatería
3. **veterinaria_db** - Gestión de citas veterinarias
4. **Biblioteca** - Gestión de préstamos de libros
5. **Zoologico** - Gestión de animales y cuidados

## Tecnologías utilizadas

- **Backend:** PHP (PDO para conexión a base de datos)
- **Base de datos:** MySQL
- **Servidor:** Apache
- **Entorno:** Ubuntu (VirtualBox)

## Estructura del proyecto
--

  proyectos/

  ├── index.php (Aplicación consolidada con las 5 bases de datos)
  └── README.md

  
--
## Instalación y ejecución

1. Clonar este repositorio en tu servidor Apache:

git clone https://github.com/CamiloA07/PROYECT_DATABASES.git

2. Crear las bases de datos en MySQL (ejecutar los scripts SQL correspondientes a cada proyecto).

3. Configurar las credenciales de conexión PDO dentro de `index.php` según tu entorno local.

4. Acceder desde el navegador a `http://localhost/proyectos/index.php`

## Autor

Camilo Ayala
