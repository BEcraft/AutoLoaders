<?php


# Simple ejemplo de como usar el cargador de librerias:
require_once(dirname(__DIR__, 1) . "/threaded_autoLoader/Libreria.php");


# Cargador de librerias:
$libreria = new Libreria("", "", [], MANTENER_LECTORES);


# 1: Directorio.
# 2: Nombre de espacio.

# ----------------------[       #1      ]-[ #2 ]
$libreria->agregarLibro(__DIR__ . "/app/", "app");


# Otra opcion seria asignar un directorio donde se encuentran todas las librerias, ejemplo:
//$libreria->asignarDirectorioPrincipal(__DIR__ . "librerias/");


use app\a\AA;
use app\a\b\BB;
use app\a\b\c\CC;


$cc = new CC();


# Conseguir x libreria:
$libro = $libreria->conseguirLibro("app");


# Ver que archivos han incluido x clase:
$lectores = $libro->conseguirLectores(CC::class);


var_dump($cc, $libro, $lectores);