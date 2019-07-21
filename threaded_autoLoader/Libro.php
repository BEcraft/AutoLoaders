<?php


/**
 * @author BEcraft Gameplay <becraftmcpe@gmail.com>
 * @package Libro
 */
class Libro extends Threaded
{



    /**
     * @var string
     */
    private $titulo;


    /**
     * Dirección del proyecto.
     * @var string
     */
    private $directorio;


    /**
     * Carácteres disponibles para una carpeta.
     * @var string
     */
    private const CARPETA = "\w+\s+.\"\+*|<>";



    public function __construct(string $directorio, string $titulo, array $excluir = [])
    {

        if (defined("MANTENER_LECTORES") === false)
        {
            throw new Exception("Debes instanciar la clase 'Libreria' en primer lugar.");
        }

        $this->directorio = rtrim($directorio, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->titulo     = $titulo;
        $archivos         = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->directorio, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);

        if ($excluir)
        {
            $busqueda = "{.(" . implode("|", preg_replace("{[^" . self::CARPETA . "]}", "", $excluir)) . ")}i";
        }

        foreach ($archivos as $archivo)
        {
            if ($archivo->isDir() || $archivo->getExtension() !== "php" || (isset($busqueda) && preg_match($busqueda, dirname($archivo->getRealPath())) > 0))
            {
                continue;
            }

            $libro               = new Threaded();
            $libro["directorio"] = $archivo->getRealPath();
            $libro["leyendo"]    = new Threaded();

            $this->{$this->conseguirIdentificador($archivo->getRealPath())} = $libro;
        }

    }



    /**
     * Consigue todos los archivos que han requerido a cierta clase.
     *
     * @return null | Threaded
     */
    public function conseguirLectores(string $pagina): Threaded
    {
        if ((Libreria::conseguirBandera() & MANTENER_LECTORES))
        {
            $pagina = substr($pagina, strlen($this->titulo) + 1, strlen($pagina));

            if ($this->existe($pagina))
            {
                return $this->{$pagina}["leyendo"];
            }

        }

        return new Threaded();
    }



    /**
     * Conseguir la ID para cierto archivo.
     *
     * @return string
     */
    public function conseguirIdentificador(string $directorio): string
    {
        return str_replace([DIRECTORY_SEPARATOR, ".php"], ["\\", ""], substr($directorio, strlen($this->directorio), strlen($directorio)));
    }



    /**
     * Incluye cierta clase (si existe).

     * @param string $pagina Clase a incluir.
     * @param string $lector Archivo al que se incluirá la clase.
     *
     * @return bool
     */
    public function leer(string $pagina, string $lector): bool
    {
        $pagina = str_replace(DIRECTORY_SEPARATOR, "\\", $pagina);

        if ( ! ($this->existe($pagina)))
        {
            return false;
        }

        if ((Libreria::conseguirBandera() & MANTENER_LECTORES))
        {
            $this->{$pagina}["leyendo"][$lector] = time();
        }

        return include_once($this->{$pagina}["directorio"]);
    }



    /**
     * Verífica si cierto archivo incluyó cierta clase.
     *
     * @return bool
     */
    public function leyendo(string $pagina, string $lector): bool
    {
        return isset($this->{$pagina}["leyendo"][$lector]);
    }



    /**
     * Verífica si cierta clase existe.
     *
     * @return bool
     */
    public function existe(string $pagina): bool
    {
        return isset($this->{$pagina});
    }



}