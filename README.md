# moderacao-participe

Necessário criar arquivo config.php:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'usuario');
define('DB_PASSWORD', 'senha');
define('DB_NAME', 'nome_do_banco');
define('DB_CHARSET', 'charset_utilizado');

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
 
if($link === false){
    die("ERRO: Não foi possível conectar. " . mysqli_connect_error());
}
?>
```
