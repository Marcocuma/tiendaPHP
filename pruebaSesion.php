<?php
if(!session_id()){
    session_start();
}
function comprobarTiempo(){
    if(isset($_SESSION['tiempo'])&&$_SESSION['tiempo']!=0){
        $resultado=time()-$_SESSION['tiempo'];
        if($resultado>=30){
            $_SESSION['contador']=1;
            $_SESSION['tiempo']=0;
            return true;
        } else {
            return false;
        }
    }
    return true;
}
function buscarUsuario($conexion){
    if (isset($_POST['user'])&&isset($_POST['pass'])){
        $_SESSION['usuario']=md5($_POST['user']);
        $_SESSION['contra']=md5($_POST['pass']);
        $resultado = select($conexion,['usuario'],['usuarios'],["contrasena='".$_SESSION['contra']."'"]);
        $user=$resultado->fetch();
        $user=md5($user[0]);
        return $user;
    }
    return '';
}
function redireccion(){
        //if($_SESSION['redireccion']==true){
        if(comprobarTiempo()){
            header('Location: webPedidos.php');
        }
}
include "libreriaBaseDeDatos.php";
$conexion=crearConexion("dwes","marco","marco");
if(isset($_SESSION['contador'])&&($_SESSION['contador']==3)){
        $_SESSION['tiempo']=time();
}

if(buscarUsuario($conexion)==$_SESSION['usuario']&&(comprobarTiempo())){
    $_SESSION['active']=true;
    unset($_SESSION['contador']);
    redireccion();
} else {
    $_SESSION['active']=false;
    include 'formularioSesion.php';
    if(!isset($_SESSION['contador'])){
        $_SESSION['contador']=1;
    } else {
        $_SESSION['contador']++;
    }
    if(!comprobarTiempo()){
        print 'Debes esperar '.(30-($resultado=time()-$_SESSION['tiempo'])).' segundos';
    }else{
        print 'Llevas '.$_SESSION['contador']."intentos <br>";
    }
}
?>