<?php
function crearConexion($database,$user,$password){
    //crea la conexion con la base de datos pasando como parametro el nombre de la base de datos
    //el usuario y la contraseña5
    try {
        $opciones = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
        $dwes = new PDO("mysql:host=localhost;dbname=".$database, "$user", $password,$opciones);
        $dwes->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $dwes;
    } catch (PDOException $e) {
        $error = $e->getCode();
        $mensaje = $e->getMessage();
    }
}
function select($conexion,$valoresSeleccion,$tabla,$where = ['']){
    //Realiza la consulta select, con o sin where
    $consulta="SELECT ";
    for ($i=0; $i < count($valoresSeleccion); $i++) { 
        if($i < count($valoresSeleccion)-1){
            $consulta=$consulta.$valoresSeleccion[$i].' ,';
        } else {
            $consulta=$consulta.$valoresSeleccion[$i].' ';
        }
    }
    $consulta=$consulta.'FROM ';
    for ($i=0; $i < count($tabla); $i++) { 
        
        if($i < count($tabla)-1){
            $consulta=$consulta.$tabla[$i].' ,';
        } else {
            $consulta=$consulta.$tabla[$i].' ';
        }
    }
    if ($where[0]!=''){
        $consulta=$consulta.' WHERE ';
        for ($i=0; $i < count($where); $i++) { 
            if($i < count($where)-1){
                $consulta=$consulta.$where[$i].' AND ';
            } else {
                $consulta=$consulta.$where[$i].' ';
            }
        }
    }
    $resultado = $conexion->query($consulta);
    return $resultado;
}
function rellenarDesplegable($resultado, $value, $nombre,$selected=''){
    //Rellena el desplegable
    $row = $resultado->fetch();
    while ($row != null) {
        echo "<option value='${row[$value]}'";
        // Si se recibió un código de producto lo seleccionamos
        // en el desplegable usando selected='true'
        if ($selected!='' && $selected == $row[$value])
            echo "selected='true'";
        echo ">${row[$nombre]}</option>";
        $row = $resultado->fetch();
    }
    $mensaje='La consulta se ha realizado correctamente';
}
?>