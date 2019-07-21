<?php


/**
 * Simple cargador de librerias.
 *
 * @author  BEcraft Gameplay <becraftmcpe@gmail.com>
 * @package Libreria
 */
class Libreria extends Volatile
{



    /**
     * Bandera del proyecto.
     * @var int
     */
    private static $bandera = 0x00;


    /**
     * Lista de mensajes de la libreria.
     * @var array
     */
    const MENSAJES = [
        "clase.fantasma"       => "No se ha encontrado la clase '%s', puedes usar la funcion 'agregarLibro' para agregar un proyecto.",
        "espacio.fantasma"     => "El nombre de espacio '%s' no existe, verífica que todo esté correcto.",
        "espacio.incorrecto"   => "No se ha podido desifrar el nombre de espacio '%s' (muy corto).",
        "directorio.fantasma"  => "La ruta %s no existe, ¿Es está la ruta correcta?",
        "directorio.principal" => "La ruta %s para el directorio principal no existe.",
        "libreria.fantasma"    => "No se ha podido encontrar la libreria '%s' en la ruta '%s'."
    ];


    /**
     * Último error que ha ocurrido.
     * @var string
     */
    private $ultimoError = "";


    /**
     * Directorio donde están los proyectos cargados.
     * @var string
     */
    private $directorioPrincipal = "";



    /**
     * Constructor de la clase.
     */
    public function __construct(string $direccion = "", string $titulo = "", array $excluir = [], int $bandera = 0x00)
    {
        if ( ! ($this->registrar()))
        {
            throw new ErrorException("No se ha podido registrar el cargador.");
        }

        spl_autoload_extensions(".php");

        if ($direccion and $titulo)
        {
            $this->agregarLibro($direccion, $titulo, $excluir);
        }

        self::$bandera |= $bandera;
    }



    /**
     * @return int
     */
    public static function conseguirBandera(): int
    {
        return self::$bandera;
    }



    /**
     * @param  string       $libro
     *
     * @return null | Libro
     */
    public function conseguirLibro(string $libro): ?Libro
    {
        return ($this->{$libro} ?? null);
    }



    /**
     * Asigna el directorio de donde se cargarán las librerias.
     *
     * @param string $directorio
     */
    public function asignarDirectorioPrincipal(string $directorio): bool
    {
        if ( ! (is_dir($directorio)))
        {
            $this->asignarError(sprintf(self::MENSAJES["directorio.principal"], $directorio)); return false;
        }

        $this->directorioPrincipal = $directorio; return true;
    }



    /**
     * Consigue el directorio principal para las librerias (Si existe).
     *
     * @return string
     */
    public function conseguirDirectorioPrincipal(): string
    {
        return $this->directorioPrincipal;
    }



    /**
     * Registra el cargador para las clases.
     *
     * @return bool
     */
    public function registrar(): bool
    {
        return spl_autoload_register([$this, "leerPagina"]);
    }



    /**
     * Asigna el último error que ha ocurrido.
     *
     * @param string $error Error ocurrido.
     */
    private function asignarError(string $error): void
    {
        $this->ultimoError = rtrim($error, PHP_EOL) . PHP_EOL;
    }



    /**
     * Cargador de clases.
     *
     * @param  string $busqueda Clase a cargar.
     *
     * @return bool             Valor dependiendo si la clase se cargó o ya estaba cargada o simplemente no existe.
     */
    private function leerPagina(string $busqueda): ?bool
    {
        $libro = $this->buscarPagina($busqueda, $pagina);

        if ( ! ($libro instanceof Libro))
        {
            $this->asignarError($libro ?? "¡ERROR DESCONOCIDO!"); return null;
        }

        if ((self::$bandera & MANTENER_LECTORES))
        {
            $archivo = basename(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]["file"]);
            $leyendo = $libro->leyendo($pagina, $archivo);
        }
        else
        {
            $archivo = "";
            $leyendo = false;
        }

        if ( ! ($libro->existe($pagina)) || $leyendo)
        {

            if ( ! ($leyendo))
            {
                $this->asignarError(sprintf(self::MENSAJES["clase.fantasma"], $pagina));
            }

            return null;
        }

        return $libro->leer($pagina, $archivo);
    }



    /**
     * Verífica la existencia de cierto nombre de espacio.

     * @param  string $libro Nombre de espacio.
     *
     * @return bool
     */
    public function existe(string $libro): bool
    {
        return isset($this->{$libro});
    }



    /**
     * Consigue x libreria por un nombre de espacio, muy útil cuando
     * el nombre de espacio principal es algo como "auto\Loader" y no "autoLoader".
     *
     * @param string $titulo Nombre de espacio de la clasea buscar.
     */
    public function encontrarLibro(string $titulo): string
    {
        foreach (array_keys((array) $this) as $libro)
        {
            if (strpos($titulo, $libro) === false)
            {
                continue;
            }

            $similar = substr($titulo, 0, strlen($libro));

            if ($similar === $libro)
            {
                return $similar;
            }
        }

        return "";
    }



    /**
     * Conseguir el almacenamiento para cierta clase.
     *
     * @param string $busqueda Clase con nombre de espacio a buscar.
     * @param mixed  $pagina   Nombre de la clase a buscar.
     *
     * @return string | Libro  Si hay error retorna una cadena de texto, de lo contrario retorna la clase Libro.
     */
    private function buscarPagina(string $busqueda, &$pagina)
    {
        $titulo = $this->encontrarLibro($busqueda);

        if ($titulo !== "")
        {
            $componentes = (array) explode("\\", substr($busqueda, strlen($titulo . "\\")));

            if (1 >= count($componentes))
            {
                return sprintf(self::MENSAJES["espacio.incorrecto"], implode(DIRECTORY_SEPARATOR, $componentes));
            }

        }

        if ( ! ($this->existe($titulo)) && $titulo)
        {

            if ( ! ($this->conseguirDirectorioPrincipal()))
            {
                return sprintf(self::MENSAJES["espacio.fantasma"], $titulo);
            }

            $directorio = $this->conseguirDirectorioPrincipal() . $titulo;

            if ( ! (is_dir($directorio)))
            {
                return sprintf(self::MENSAJES["libreria.fantasma"], $titulo, $directorio);
            }

            $this->agregarLibro($titulo, $directorio);

        }

        $pagina = implode("\\", ($componentes ?? []));

        return $this->{$titulo};
    }



    /**
     * Consigue el último error del código.
     *
     * @return string Error.
     */
    public function conseguirError(): string
    {
        return $this->ultimoError;
    }



    /**
     * Agrega un nuevo proyecto.
     *
     * @param  string $direccion Ruta al proyecto.
     * @param  string $titulo    Nombre de espacio del proyecto.
     * @param  string $excluir   Carpetas las cuales no se incluirán al cargar la el proyecto.
     *
     * @return bool
     */
    public function agregarLibro(string $direccion, string $titulo, array $excluir = []): bool
    {
        if ( ! (is_dir($direccion)))
        {
            trigger_error(sprintf(self::MENSAJES["directorio.fantasma"], $direccion), E_USER_WARNING); return false;
        }

        $titulo = rtrim($titulo, "\\/");

        if ( ! (isset($this->{$titulo})))
        {
            $this->{$titulo} = new Libro($direccion, $titulo, $excluir);
        }

        return true;
    }



    /**
     * Elimina un proyecto ya agregado.
     *
     * @param string $libro Nombre de espacio a eliminar.
     */
    public function eliminarLibro(string $libro): void
    {
        if ($this->existe($libro))
        {
            unset($this->{$libro});
        }
    }



}


define("MANTENER_LECTORES", 0x02);


if (!(extension_loaded("pthreads")))
{
    throw new Exception("[Error] La extención 'pthreads' no está cargada.");
}



if (!(@include_once(__DIR__ . DIRECTORY_SEPARATOR . "Libro.php")))
{
    throw new Exception("[Error] No se ha encontrado el archivo Libro.php en la ruta '" . __DIR__ . "'");
}