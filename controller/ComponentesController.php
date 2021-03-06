<?php
namespace App\Controller;

use App\Helper\ViewHelper;
use App\Helper\DbHelper;
use App\Model\Componentes;


class ComponentesController
{
    var $db;
    var $view;


    function __construct()
    {
        //Conexión a la BBDD
        $dbHelper = new DbHelper();
        $this->db = $dbHelper->db;

        //Instancio el ViewHelper
        $viewHelper = new ViewHelper();
        $this->view = $viewHelper;


    }

    //Listado de componentes
    public function index(){

        //Permisos
        $this->view->permisos("componentes");

        //Recojo las componentes de la base de datos
        $rowset = $this->db->query("SELECT * FROM Componentes WHERE review=0 ORDER BY fecha DESC");


        //Asigno resultados a un array de instancias del modelo
        $componentes = array();
        while ($row = $rowset->fetch(\PDO::FETCH_OBJ)){
            array_push($componentes,new Componentes($row));
        }

        $this->view->vista("admin","componentes/index", $componentes);
    }


    //Para activar o desactivar en admin
    public function activar($id){

        //Permisos
        $this->view->permisos("componentes");

        //Obtengo el componente
            $rowset = $this->db->query("SELECT * FROM Componentes WHERE id='$id' LIMIT 1");

        $row = $rowset->fetch(\PDO::FETCH_OBJ);
        $componente = new Componentes($row);

        if ($componente->activo == 1){
            echo "hola";
            //Desactivo el componente
            $consulta = $this->db->exec("UPDATE Componentes SET activo=0 WHERE id='$id'");

            //Mensaje y redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/componentes","#0277bd light-blue darken-3","El componente '<strong>$componente->titulo</strong>' se ha desactivado correctamente.") :
                $this->view->redireccionConMensaje("admin/componentes","#ef5350 red lighten-1","Hubo un error al guardar en la base de datos.");
        }

        else{
            //Activo el componente
            $consulta = $this->db->exec("UPDATE Componentes SET activo=1 WHERE id='$id'");

            //Mensaje y redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/componentes","#0277bd light-blue darken-3","El componente '<strong>$componente->titulo</strong>' se ha activado correctamente.") :
                $this->view->redireccionConMensaje("admin/componentes","#ef5350 red lighten-1","Hubo un error al guardar en la base de datos.");
        }

    }

    //Para mostrar o no en la home
    public function home($id){

        //Permisos
        $this->view->permisos("componentes");

        //Obtengo el componente
        $rowset = $this->db->query("SELECT * FROM Componentes WHERE id='$id' LIMIT 1");
        $row = $rowset->fetch(\PDO::FETCH_OBJ);
        $componente = new Componentes($row);

        if ($componente->home == 1){

            //Quito el componente de la home
            $consulta = $this->db->exec("UPDATE Componentes SET home=0 WHERE id='$id'");

            //Mensaje y redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/componentes","#0277bd light-blue darken-3","El componente '<strong>$componente->titulo</strong>' ya no se muestra en la home.") :
                $this->view->redireccionConMensaje("admin/componentes","#ef5350 red lighten-1","Hubo un error al guardar en la base de datos.");
        }

        else{

            //Muestro el componente en la home
            $consulta = $this->db->exec("UPDATE Componentes SET home=1 WHERE id='$id'");

            //Mensaje y redirección
            ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
                $this->view->redireccionConMensaje("admin/componentes","#0277bd light-blue darken-3","El componente '<strong>$componente->titulo</strong>' ahora se muestra en la home.") :
                $this->view->redireccionConMensaje("admin/componentes","#ef5350 red lighten-1","Hubo un error al guardar en la base de datos.");
        }

    }


    //Para borrar el componente
    public function borrar($id){

        //Permisos
        $this->view->permisos("componentes");

        //Obtengo el componente
        $rowset = $this->db->query("SELECT * FROM Componentes WHERE id='$id' LIMIT 1");
        $row = $rowset->fetch(\PDO::FETCH_OBJ);
        $componente = new Componentes($row);

        //Borro el componente
        $consulta = $this->db->exec("DELETE FROM Componentes WHERE id='$id'");

        //Borro la imagen asociada
        $archivo = $_SESSION['public']."img/".$componente->imagen;
        $texto_imagen = "";
        if (is_file($archivo)){
            unlink($archivo);
            $texto_imagen = " y se ha borrado la imagen asociada";
        }

        //Mensaje y redirección
        ($consulta > 0) ? //Compruebo consulta para ver que no ha habido errores
            $this->view->redireccionConMensaje("admin/componentes","#0277bd light-blue darken-3","El Componente se ha borrado correctamente$texto_imagen.") :
            $this->view->redireccionConMensaje("admin/componentes","#ef5350 red lighten-1","Hubo un error al guardar en la base de datos.");

    }

    //Para crear un componente
    public function crear(){

        //Permisos
        $this->view->permisos("componentes");
        //Creo un nuevo usuario vacío
        $componente = new Componentes();
        //Llamo a la ventana de edición
        $this->view->vista("admin","componentes/editar", $componente);
    }

    //Para editar un componente
    public function editar($id){

        //Permisos
        $this->view->permisos("componentes");

        //Si ha pulsado el botón de guardar
        if (isset($_POST["guardar"])){

            //Recupero los datos del formulario
            $titulo = filter_input(INPUT_POST, "titulo", FILTER_SANITIZE_STRING);
            $entradilla = filter_input(INPUT_POST, "entradilla", FILTER_SANITIZE_STRING);
            $autor = filter_input(INPUT_POST, "autor", FILTER_SANITIZE_STRING);
            $fecha = filter_input(INPUT_POST, "fecha", FILTER_SANITIZE_STRING);
            $texto = filter_input(INPUT_POST, "texto", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            //Formato de fecha para SQL
            $fecha = \DateTime::createFromFormat("d-m-Y", $fecha)->format("Y-m-d H:i:s");

            //Genero slug (url amigable)
            $slug = $this->view->getSlug($titulo);

            //Imagen
            $imagen_recibida = $_FILES['imagen'];
            $imagen = ($_FILES['imagen']['name']) ? $_FILES['imagen']['name'] : "";
            $imagen_subida = ($_FILES['imagen']['name']) ? '/var/www/html'.$_SESSION['public']."img/".$_FILES['imagen']['name'] : "";
            $texto_img = ""; //Para el mensaje

            //Si el componente es nuevo
            if ($id == "nuevo"){
                //Creo una nuevo componente
                $consulta = $this->db->exec("INSERT INTO Componentes 
                    (titulo, entradilla, autor, fecha, texto, slug, imagen) VALUES 
                    ('$titulo','$entradilla','$autor','$fecha','$texto','$slug','$imagen')");

                //Subo la imagen
                if ($imagen){
                    if (is_uploaded_file($imagen_recibida['tmp_name']) && move_uploaded_file($imagen_recibida['tmp_name'], $imagen_subida)){
                        $texto_img = " La imagen se ha subido correctamente.";
                    }
                    else{
                        $texto_img = "Hubo un problema al subir la imagen.";
                    }
                }

                //Mensaje y redirección
                ($consulta > 0) ?
                    $this->view->redireccionConMensaje("admin/componentes","#0277bd light-blue darken-3","El componente '<strong>$titulo</strong>' se creado correctamente.$texto_img") :
                    $this->view->redireccionConMensaje("admin/componentes","#ef5350 red lighten-1","Hubo un error al guardar en la base de datos.");
            }
            //Si no es nuevo
            else{
                //Actualizo el componente
                $this->db->exec("UPDATE Componentes SET 
                    titulo='$titulo',entradilla='$entradilla',autor='$autor',
                    fecha='$fecha',texto='$texto',slug='$slug' WHERE id='$id'");

                //Subo y actualizo la imagen
                if ($imagen){
                    if (is_uploaded_file($imagen_recibida['tmp_name']) && move_uploaded_file($imagen_recibida['tmp_name'], $imagen_subida)){
                        $texto_img = " La imagen se ha subido correctamente.";
                        $this->db->exec("UPDATE Componentes SET imagen='$imagen' WHERE id='$id'");
                    }
                    else{
                        $texto_img = " Hubo un problema al subir la imagen.";
                    }
                }

                //Mensaje y redirección
                $this->view->redireccionConMensaje("admin/componentes","#0277bd light-blue darken-3","El componente '<strong>$titulo</strong>' se guardado correctamente.$texto_img");

            }
        }

        //Si no, obtengo el componente y muestro la ventana de edición
        else{

            //Obtengo la noticia
            $rowset = $this->db->query("SELECT * FROM Componentes WHERE id='$id' LIMIT 1");
            $row = $rowset->fetch(\PDO::FETCH_OBJ);
            $componente = new Componentes($row);

            //Llamo a la ventana de edición
            $this->view->vista("admin","componentes/editar", $componente);
        }

    }

}
