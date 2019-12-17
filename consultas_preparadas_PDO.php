<!-- Factorizar codigo -->
<?php
$error;
$mensaje = '';
function crearConexion(){
    //crea la conexion con la base de datos
    global $error,$mensaje;
    try {
        $opciones = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
        $dwes = new PDO("mysql:host=localhost;dbname=dwes", "marco", "marco",$opciones);
        $dwes->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $dwes;
    } catch (PDOException $e) {
        $error = $e->getCode();
        $mensaje = $e->getMessage();
        echo $error;
    }
}
function rellenarDesplegable($resultado,$valor,$selected){
    //Rellena el desplegable
    $row = $resultado->fetch();
    while ($row != null) {
        echo "<option value='${row[$valor]}'";
        // Si se recibió un código de producto lo seleccionamos
        // en el desplegable usando selected='true'
        if (isset($selected) && $selected == $row[$valor])
            echo " selected='true'";
        echo ">${row['nombre_corto']}</option>";
        $row = $resultado->fetch();
    }
    $mensaje='La consulta se ha realizado correctamente';
}
function pintarFormulario($resultado,$producto){
    //Pinta el formulario en la web
    echo "<form id='form_actualizar' action=".$_SERVER['PHP_SELF']." method='post'>";
    $row = $resultado->fetch();
    while ($row != null) {
    // Metemos ocultos el código de producto y los de las tiendas
        echo "<input type='hidden' name='producto' value='$producto'/>";
        echo "<input type='hidden' name='tienda[]' value='" . $row['cod'] . "'/>";
        echo "<p>Tienda ${row['nombre']}: ";
    // El número de unidades ahora va en un cuadro de texto
        echo "tiene ".$row['unidades']." unidades, introduce el nuevo número  ";
        echo "<input type='text' name='unidades[]' size='4' /></p>";
        //echo "value='" . $row['unidades'] . "'/> unidades.</p>";
        $row = $resultado->fetch();
    }
    echo "<input type='submit' value='Actualizar' name='actualizar'/>";
    echo "</form>";
}
function actualizarUnidades($conexion,$producto,$unidades,$tienda){
    //actualiza el valor del stock del producto en cada tienda en la base de datos
    $sql = <<<SQL
    UPDATE stock SET unidades=:unidades 
    WHERE tienda=:tienda AND producto='$producto'
SQL;
    $consulta = $conexion->prepare($sql);
    // La ejecutamos dentro de un bucle, tantas veces como tiendas haya
    for ($i = 0; $i < count($tienda); $i++) {
        $consulta->bindParam(":unidades", $unidades[$i]);
        $consulta->bindParam(":tienda", $tienda[$i]);
        $consulta->execute();
    }
}
function comprobarError($mensaje,$error){
    //comprueba si la variable error esta definida y muestra el mensaje
    if (isset($error))
        echo "<p>Se ha producido un error! $mensaje</p>";
    else {
        echo $mensaje;
        unset($dwes);
    }
}
function parametrosNoVacios($unidades,$tienda){
    //Comprueba que los inputs tienen valor
    foreach ($unidades as $value) {
        if ($value=='') {
            return false;
        }
    }
    foreach ($tienda as $value) {
        if ($value=='') {
            return false;
        }
    }
    return true;
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
    print($consulta);
    $resultado = $conexion->query($consulta);
    return $resultado;
}




if (isset($_POST['producto']))
    $producto = $_POST['producto'];
    $dwes=crearConexion();
if (!isset($error)) {
// Comprobamos si tenemos que actualizar los valores
    if (isset($_POST['actualizar'])) {
        if (parametrosNoVacios($_POST['unidades'],$_POST['tienda'])){
            actualizarUnidades($dwes,$producto,$_POST['unidades'],$_POST['tienda']);
            $mensaje = "Se ha ejecutado correctamente";
        } else {
            $error = "Se ha producido un error";
        }
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<!-- Desarrollo Web en Entorno Servidor -->
<!-- Tema 3 : Trabajar -->
        <div id="encabezado">
            <h1>Ejercicio: Utilización de excepciones en PDO</h1>
            <form id="form_seleccion" action=<?php echo $_SERVER['PHP_SELF'];?> method="post">
                <span>Producto: </span>
                <select name="producto">
                    <?php
                        $resultado = select($dwes,['cod','nombre_corto'],['producto']);
                        if ($resultado) {
                            rellenarDesplegable($resultado,'cod',$producto);
                        }
                    ?>
                </select>
                <input type="submit" value="Mostrar stock" name="enviar"/>
            </form>
        </div>
        <div id="contenido">
            <h2>Stock del producto en las tiendas:</h2>
            <?php
            if (!isset($error) && isset($producto)) {
                $resultado=select($dwes,['tienda.cod','tienda.nombre','stock.unidades'],
                ['tienda','stock'],["stock.tienda=tienda.cod","stock.producto='".$producto."'"]);
                if ($resultado) {
                    pintarFormulario($resultado,$producto);
                }
            }
            ?>
        </div>
        <div id="pie">
            <?php
                comprobarError($mensaje,$error);
            ?>
        </div>
    </body>
</html>