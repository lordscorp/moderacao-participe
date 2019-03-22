<?php
// Inicia a sessão
session_start();
 
// Verifica se o usuário já está logado. Se sim, redireciona à página do Painel
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: painel.php");
    exit;
}
 
// Inclui arquivo de configuração
require_once "config.php";

// Registra IP do usuário para gravar no log
function getRealIpAddr(){
    if (!empty($_SERVER['HTTP_CLIENT_IP'])){
      $ip=$_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else{
      $ip=$_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}
$userIP = getRealIpAddr();
 
// Define as variáveis e as inicializa vazias
$username = $password = "";
$username_err = $password_err = "";
 
// Processa os dados do formulário ao submeter
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Verifica se o e-mail foi preenchido
    if(empty(trim($_POST["username"]))){
        $username_err = "Digite o e-mail.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    // Verifica se a senha foi preenchida
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Valida o login/senha
    if(empty($username_err) && empty($password_err)){
        // Prepara um statement 'SELECT'
        $sql = "SELECT id, username, password, admin FROM moderadores WHERE username = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            // Atribui as variáveis ao statement como parâmetros
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Define os parâmetros
            $param_username = $username;
            
            // Tenta executar o statement preparado
            if(mysqli_stmt_execute($stmt)){
                // Armazena o resultado
                mysqli_stmt_store_result($stmt);
                
                // Verifica se o login existe. Se sim, verifica a senha
                if(mysqli_stmt_num_rows($stmt) == 1){  
                    // Vincula as variáveis resultantes
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $admin);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            // Senha correta, inicia uma nova sessão
                            session_start();

                            // Registra hora do login na tabela log_moderacao
                            mysqli_query($link, "INSERT INTO log_moderacao (operador, operacao, ip_origem) VALUES (" . $id . ", 'login', '" . $userIP . "')");
                            
                            // Armazena os dados em variáveis da sessão
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["admin"] = $admin;
                            $_SESSION["userip"] = $userIP;
                            
                            // Redireciona o usuário à página do Painel
                            header("location: painel.php");
                        } else{
                            // Exibe mensagem de erro se a senha estiver incorreta
                            $password_err = "A senha digitada está incorreta.";
                        }
                    }
                } else{
                    // Se o login não existir...
                    $username_err = "E-mail não cadastrado.";
                }
            } else{
                echo "Erro de sistema! Contate <a href='mailto:rmgomes@prefeitura.sp.gov.br'>o desenvolvedor</a>.";
            }
        }
        
        // Close statement
        mysqli_stmt_close($stmt);
    }
    
    // Close connection
    mysqli_close($link);
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Participe - Moderação</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
    <style type="text/css">
        body{ font: 14px sans-serif; }
        .wrapper{ width: 350px; padding: 20px; }
    </style>
</head>
<body>
    <div class="wrapper" style="margin: 2em auto;">
        <h2>Participe - Moderação</h2>
        <p>Digite o e-mail e senha</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group <?php echo (!empty($username_err)) ? 'has-error' : ''; ?>">
                <label>E-mail</label>
                <input type="text" name="username" autocomplete="username" class="form-control" value="<?php echo $username; ?>">
                <span class="help-block"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                <label>Senha</label>
                <input type="password" name="password" autocomplete="current-password" class="form-control">
                <span class="help-block"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Login">
            </div>
        </form>
    </div>    
</body>
</html>