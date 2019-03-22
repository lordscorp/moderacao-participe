<?php 
session_start();
 
// Verifica se usuário está logado
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
// Inclui arquivo de configuração
require_once "config.php";

// Declaração de variáveis
$idComentario = $_REQUEST["memid"];
$aprovar = $_REQUEST["aprovar"];
$operador = $_SESSION["id"];
$idConsulta = $_REQUEST["consulta"];
$ip = $_REQUEST["ip"];


if($_SERVER["REQUEST_METHOD"] == "POST"){
    if ($aprovar == '1') { // APROVA COMENTÁRIO
        $sqlModera = "UPDATE members SET `public`='1', `trash`='0' WHERE  `memid`=" . $idComentario;
    }
    else { // REPROVA COMENTÁRIO
        $sqlModera = "UPDATE members SET `public`='0', `trash`='1' WHERE  `memid`=" . $idComentario;
    }
    $retorno = $link->query($sqlModera);

    echo $retorno;

    $sqlLog = "INSERT INTO log_moderacao (`operador`, `operacao`, `consulta`, `member`, `ip_origem`) VALUES ('" . $operador . "', '" . ($aprovar == '1' ? 'public' : 'trash') . "', '" . $idConsulta . "', '" . $idComentario . "', '" . $ip . "');";
    $retornoLog = $link->query($sqlLog);
}
?>