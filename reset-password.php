<?php
session_start();
 
// Verifica se usuário está logado
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
 
// Inclui arquivo de configuração
require_once "config.php";
 
// Define variáveis e as inicializa com valor vazio
$new_password = $confirm_password = "";
$new_password_err = $confirm_password_err = "";
 
// Processa os dados quando o formulário for enviado
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Verifica a senha
    if(empty(trim($_POST["new_password"]))){
        $new_password_err = "Digite a nova senha.";     
    } elseif(strlen(trim($_POST["new_password"])) < 6){
        $new_password_err = "A senha deve conter, no mínimo, 6 caracteres.";
    } else{
        $new_password = trim($_POST["new_password"]);
    }
    
    // Verifica confirmação de senha
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Confirme a senha.";
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($new_password_err) && ($new_password != $confirm_password)){
            $confirm_password_err = "Senhas não conferem.";
        }
    }
        
    // Verifica erros de inserção antes de atualizar o db
    if(empty($new_password_err) && empty($confirm_password_err)){
        // Prepara um statement de UPDATE
        $sql = "UPDATE moderadores SET password = ? WHERE id = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            // Vincula variáveis como parâmetros ao statement preparado 
            mysqli_stmt_bind_param($stmt, "si", $param_password, $param_id);
            
            // Define parâmetros
            $param_password = password_hash($new_password, PASSWORD_DEFAULT);
            $param_id = $_SESSION["id"];
            
            // Tenta executar o statement preparado
            if(mysqli_stmt_execute($stmt)){
                // Senha atualizada com sucesso. Destrói a sessão e redireciona à página de login.
                session_destroy();
                header("location: login.php");
                exit();
            } else{
                echo "Ocorreu um erro. Tente novamente. Se o problema persistir, entre em contato com o <a href='mailto:rmgomes@prefeitura.sp.gov.br'>desenvolvedor</a>.";
            }
        }        
        // Statement de encerramento
        mysqli_stmt_close($stmt);
    }    
    // Encerra conexão
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Redefinição de senha</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
    <style type="text/css">
        body{ font: 14px sans-serif; }
        .wrapper{ width: 350px; padding: 20px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Redefinição de senha</h2>
        <p>Preencha os dados para alterar sua senha</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post"> 
            <div class="form-group <?php echo (!empty($new_password_err)) ? 'has-error' : ''; ?>">
                <label>Nova senha</label>
                <input type="password" name="new_password" class="form-control" value="<?php echo $new_password; ?>">
                <span class="help-block"><?php echo $new_password_err; ?></span>
            </div>
            <div class="form-group <?php echo (!empty($confirm_password_err)) ? 'has-error' : ''; ?>">
                <label>Redigite a nova senha</label>
                <input type="password" name="confirm_password" class="form-control">
                <span class="help-block"><?php echo $confirm_password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Enviar">
                <a class="btn btn-link" href="painel.php">Cancelar</a>
            </div>
        </form>
    </div>    
</body>
</html>