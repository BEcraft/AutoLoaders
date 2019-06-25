<?php

/**
 * Simple cargador de librerias.
 *
 * @author  BEcraft Gameplay <becraftmcpe@gmail.com>
 * @package Libreria
 */
class Libreria
{


    /**
     * Lista de proyectos cargados.
     */
    private $libros = [];


    /**
     * Lista de mensajes de la libreria.
     * @var array
     */
    const MENSAJES = [
        "clase.fantasma"       => "No se ha encontrado la clase '%s', puedes usar la funcion 'agregarLibro' para agregar un proyecto.",
        "espacio.fantasma"     => "El nombre de espacio '%s' no existe, verífica que todo esté correcto.",
        "espacio.incorrecto"   => "No se ha podido desifrar el nombre de espacio '%s' (muy corto).",
        "directorio.fantasma"  => "La ruta %s no existe, ¿Es está la ruta correcta?",
        "directorio.principal" => "La ruta %s para el directorio principal no existe."
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
    public function __construct(string $directorio = "", string $titulo = "", array $excluir = [])
    {
        if (!($this->registrar()))
        {
            throw new ErrorException("No se ha podido registrar el cargador.");
        }

        spl_autoload_extensions(".php");

        if ($directorio and $titulo)
        {
            $this->agregarLibro($directorio, $titulo, $excluir);
        }
    }



    /**
     * @param  string       $libro
     *
     * @return null | Libro
     */
    public function conseguirLibro(string $libro): ?Libro
    {
        if (!(isset($this->libros[$libro])))
        {
            return null;
        }

        return $this->libros[$libro];
    }



    /**
     *
     * Asigna el directorio de donde se cargarán las librerias.
     *
     * @param string $directorio
     */
    public function asignarDirectorioPrincipal(string $directorio): void
    {
        if (!(is_dir($directorio)))
        {
            $this->asignarError(sprintf(self::MENSAJES["directorio.principal"], $directorio)); return;
        }

        $this->directorioPrincipal = $directorio;
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
        return @spl_autoload_register([$this, "leerPagina"]);
    }



    /**
     * Asigna el último error que ha ocurrido.
     *
     * @param string $error Error ocurrido.
     */
    private function asignarError(string $error): void
    {
        $this->ultimoError = ($error . PHP_EOL);
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

        if (!($libro instanceof Libro))
        {
            $this->asignarError($libro ?? "¡ERROR DESCONOCIDO!"); return null;
        }


        if (!($libro->existe($pagina)) || ($leyendo = $libro->leyendo($pagina, ($archivo = basename(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]["file"])))))
        {

            if (!($leyendo))
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
        return isset($this->libros[$libro]);
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
        $componentes = (array) explode("\\", $busqueda);

        if (1 >= count($componentes))
        {
            return sprintf(self::MENSAJES["espacio.incorrecto"], implode(DIRECTORY_SEPARATOR, $componentes));
        }

        $titulo = array_shift($componentes);

        if (!($this->existe($titulo)))
        {

            if (!($this->conseguirDirectorioPrincipal()))
            {
                return sprintf(self::MENSAJES["espacio.fantasma"], $titulo);
            }

            if (is_dir($directorio = $this->conseguirDirectorioPrincipal() . $titulo))
            {
                $this->agregarLibro($directorio, $titulo);
            }

        }

        $pagina = implode("\\", $componentes);

        return $this->libros[$titulo];
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
     * @param  string $directorio Ruta al proyecto.
     * @param  string $titulo    Nombre de espacio del proyecto.
     * @param  string $excluir   Carpetas las cuales no se incluirán al cargar la el proyecto.
     *
     * @return bool
     */
    public function agregarLibro(string $directorio, string $titulo, array $excluir = []): bool
    {
        if (!(is_dir($directorio)))
        {
            trigger_error(sprintf(self::MENSAJES["directorio.fantasma"], $directorio), E_USER_WARNING); return false;
        }

        if (!(isset($this->libros[$titulo])))
        {
            $this->libros[$titulo] = new Libro($directorio, $titulo, $excluir); return true;
        }

        return false;
    }



    /**
     * Elimina un proyecto ya agregado.
     *
     * @param string $libro Nombre de espacio a eliminar.
     */
    public function eliminarLibro(string $libro): void
    {
        if (isset($this->libros[$libro]))
        {
            unset($this->libros[$libro]);
        }
    }



}


if (!(@include_once(__DIR__ . DIRECTORY_SEPARATOR . "Libro.php")))
{
    throw new Exception("[Error] No se ha encontrado el archivo Libro.php dentro del directorio '" . __DIR__ . "'");
}