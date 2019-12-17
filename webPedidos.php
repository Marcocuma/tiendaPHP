<?php
if(!session_id()){
    session_start();
}
include 'libreriaBaseDeDatos.php';
if(!$_SESSION['active']){
    header('Location: formularioSesion.php');
}
function crearLista($resultado){
    //Muestra el numero de unidades del producto seleccionado y un input para seleccionar cuantos quieres
    $row = $resultado->fetch();
    while ($row != null) {
        echo "<p>Este producto ";
        echo "tiene <strong>".$row['unidades']."</strong> unidades, introduce cuantos quieres añadir ";
        echo "<input type='text' name='unidades' size='3' /></p>";
        $row = $resultado->fetch();
    }
    echo '<input type="submit" name="anadir" value="Añadir"/>';
}



function decrementarUnidades($conexion,$linea){
    //decrementa el numero de unidades en el stock si es posible
    $result=select($conexion,['unidades'],['stock'],["producto='".$linea['producto']."'","tienda='".$_SESSION['tienda']."'"]);
    $result=$result->fetch();
    $result=(int)$result[0];
    $diferencia=$result-((int)$linea['unidades']);
    if($diferencia<0){
        throw new Exception('No puedes seleccionar mas productos de los que hay');
    }
    $update="update stock set unidades=$diferencia where producto='".$linea['producto']."' and tienda='".$_SESSION['tienda']."'";
    $filas=$conexion->exec($update);
    if($filas==0){
        throw new Exception('No se ha podido modificar el stock del producto');
    }
    return true;
}

function mostrarLineas($conexion){
    //Muestra las lineas de pedido que existen en ese momento
    echo "<h2>Lineas de pedido</h2>";
    foreach($_SESSION['lineasPedido'] as $value ){
        $resultadoProducto=select($conexion,['nombre_corto'],['producto'],["cod='".$value['producto']."'"]);
        $resultadoProducto=$resultadoProducto->fetch();
        echo "<p>".$resultadoProducto[0]." - ".$value["unidades"];
    }
}


function insertarLineasPedido($conexion){
    //inserta las lineas del pedido
    foreach($_SESSION['lineasPedido'] as $value ){
        $result=decrementarUnidades($conexion,$value);
        $insert="insert into lineaPedido values(".$_SESSION['idPedido'].",'".$_SESSION['tienda']."','".$value['producto']."',".$value['unidades'].")";
        print $insert;
        $num=$conexion->exec($insert);
    }
    return true;
}

function insertarPedidoYLineas($conexion){
    //inserta el pedido y llama a la funcion que inserta las lineas
    $conexion->beginTransaction();
    try {
        $insert="insert into pedido values(".$_SESSION['idPedido'].",'".$_SESSION['tienda']."',".time().",'".$_SESSION['usuario']."')";
        print $insert;
        $num=$conexion->exec($insert);
        insertarLineasPedido($conexion);
        $conexion->commit();
        return true;
    } catch (Throwable $th) {
        $conexion->rollback();
        return false;
    }
}
$conexion=crearConexion("dwes","marco","marco");
$tiendas=select($conexion,['cod','nombre'],
['tienda']);
var_dump($_POST);
var_dump($_SESSION);
//Comprueba si hay una sesion abierta, inserta los datos en la base de datos y la cierra
if (isset($_POST['terminarPedido'])){
    $resultado=insertarPedidoYLineas($conexion);
    print $resultado;
    if($resultado){
        echo "<p id='green'>Se ha realizado el pedido correctamente</p>";
    }else
        echo "<p id='red'>No se ha podido realizar el pedido</p>";
    unset($_SESSION['lineasPedido']);
    unset($_SESSION['idPedido']);
    unset($_SESSION['tienda']);
}
//Se asigna tienda a una variable global
if(isset($_POST['tienda']))
    $_SESSION['tienda']=$_POST['tienda'];
//Se asigna el codigo de producto a una variable global
if (isset($_POST['seleccionarProducto']))
    $_SESSION['producto']=$_POST['producto'];
//Comprueba si existe un pedido abierto, si no lo crea cogiendo el ultimo numero de pedido e
//incrementandolo en 1, y despues añade la linea utilizando las variables definidas anteriormente (tienda,producto).
if (isset($_POST['anadir'])){
    //Crea el pedido
    if (!isset($_SESSION['idPedido'])){
        $result=select($conexion,['max(idPedido)'],['pedido']);
        $resultPedido=$result->fetch();
        $idPedido=$resultPedido[0];
        if(is_null($idPedido)){
            $idPedido=1;
        } else {
            $idPedido=((int)$idPedido)+1;
        }
        $_SESSION['idPedido']=$idPedido;
    }
    if (!isset($_SESSION['lineasPedido'])){
        $_SESSION['lineasPedido']=Array();
    }
    $lineaPedido=array(
        'producto' => $_SESSION['producto'],
        'unidades' => $_POST['unidades']
    );
    $_SESSION['lineasPedido'][]=$lineaPedido;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Web Pedidos</title>
    <link rel="stylesheet" href="main.css">
</head>
<body>
    <!-- Una vez seleccionada la tienda no se vuelve a mostrar el formulario -->
    <?php if (!isset($_SESSION['tienda'])): ?>
    <form id="seleccionTienda" action=<?php echo $_SERVER['PHP_SELF'];?> method="post">
        <span>Tiendas: </span>
        <select name="tienda">
            <?php
                rellenarDesplegable($tiendas,'cod','nombre',$tienda);
            ?>
        </select>
        <input type="submit" name="seleccionarTienda"/>
    </form>
    <?php endif; ?>
    <!--El siguiente formulario se muestra si se ha añadido a la variable sesion la tienda-->
    <?php if (isset($_SESSION['tienda'])): ?>
        <form id="seleccionProducto" action=<?php echo $_SERVER['PHP_SELF'];?> method="post">
            <span>Producto: </span>
            <select name="producto">
            <?php
                $productos=select($conexion,['producto.cod','producto.nombre_corto','stock.unidades'],['producto','stock'],
                ['stock.tienda='.$_SESSION['tienda'],'stock.producto = producto.cod']);
                rellenarDesplegable($productos,'cod','nombre_corto');
            ?>
            </select>
        <input type="submit" name="seleccionarProducto"/>
        </form>
    <?php endif; ?>
    <?php if (isset($_POST['producto'])): ?>
        <!--El siguiente formulario se muestra si se ha añadido a la variable sesion el producto seleccionado-->
        <form id="seleccionUnidades" action=<?php echo $_SERVER['PHP_SELF'];?> method="post">
            <span>Producto: </span>
            <?php
                $unidades=select($conexion,['unidades','producto'],['stock'],['producto='."'".$_SESSION['producto']."'",'tienda='.$_SESSION['tienda']]);
                crearLista($unidades);
            ?>
        </form>
    <?php endif; ?>
    <?php if (isset($_SESSION['idPedido'])): ?>
        <!--El siguiente boton se muestra cuando exista el id del pedido en la variable sesion-->
    <form id="terminarPedido" action=<?php echo $_SERVER['PHP_SELF'];?> method="post">
        <input type="submit" name="terminarPedido" value="Terminar Pedido"/>
    </form>
    <div id="listaPedido">
        <?php
            mostrarLineas($conexion);
        ?>
    </div>
    <?php endif; ?>
</body>
</html>