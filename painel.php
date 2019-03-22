<?php

session_start();
 
// Verifica se usuário está logado
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
?>
 
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" href="css/custom.css">
        <script src="js/vue.js"></script>
        <title>Painel Administrativo Moderar</title>        
    </head>
<body style="margin: 1em">

<?php 
// Inclui arquivo de configuração
require_once "config.php";

// Declaração de variáveis
$userLevel = ($_SESSION["admin"] == 1 ? "Administrador" : "Moderador");
$filtroWhere = " WHERE id_consulta IN (SELECT acesso_consulta FROM moderadores_consultas WHERE login = ". $_SESSION["id"] . ")";
$comentarios = [];
$consultas = [];

/* Muda o charset para UTF-8 */
if (!mysqli_set_charset($link, "utf8")) {
    printf("Erro ao definir charset: %s<br>", mysqli_error($link));
    exit();
}


// Query das consultas e dos comentários das consultas às quais o moderador tem acesso (se for admin, acessa todos)
$sqlComentarios = "SELECT memid, name, email, content, commentdate, public, trash, commentcontext, id_consulta FROM members";
$sqlConsultas = "SELECT id_consulta, nome_publico, data_cadastro, data_final, ativo FROM consultas";
if ($userLevel == "Moderador") {
    $sqlComentarios .= $filtroWhere;
    $sqlConsultas .= $filtroWhere;
}
$retornoComentarios = $link->query($sqlComentarios);
// Se houver comentarios, popula objeto "comentarios"
if($retornoComentarios->num_rows > 0) {    
    while ($row = $retornoComentarios->fetch_assoc()) {
        $objeto = new stdClass();
        $objeto->id = $row["memid"];
        $objeto->autor = $row["name"];
        $objeto->email = $row["email"];
        $objeto->comentario = htmlspecialchars($row['content']);
        $objeto->data = $row["commentdate"];
        $objeto->aprovado = $row["public"];
        $objeto->reprovado = $row["trash"];
        $objeto->trecho = $row["commentcontext"];
        $objeto->idConsulta = $row["id_consulta"];

        array_push($comentarios, $objeto);
    }
} else {
    echo "Não foram encontrados comentários. ";
}

$retornoConsultas = $link->query($sqlConsultas);
// Se houver consultas, popula objeto "consultas"
if($retornoConsultas->num_rows > 0) {
    while ($row = $retornoConsultas->fetch_assoc()) {
        $objeto = new stdClass();        
        $objeto->id = $row["id_consulta"];
        $objeto->nome = $row["nome_publico"];
        $objeto->inicio = $row["data_cadastro"];
        $objeto->termino = $row["data_final"];
        $objeto->status = $row["ativo"];
        
        array_push($consultas, $objeto);
    }
} else {
    echo "<p>Não há consultas autorizadas para moderação por este usuário.</p><a href='logout.php'>Sair</a>";
}

echo "<script>var rawComentarios = " . json_encode($comentarios) . ";</script>";
echo "<script>var rawConsultas = " . json_encode($consultas) . ";</script>";
echo "<script>var sessionIP = '" . $_SESSION["userip"] . "';</script>";

$link->close();

?>

<!-- logo topo -->
<div class="container" id="app">
    <div class="page-header row">
        <div class="col-8 my-4">
            <h1>
                <span style="color:tomato;">participe</span><span style="color:lightgrey">.gestãourbanaSP</span><span> - Moderação</span>
            </h1>
        </div>
        <!-- <div class="logmenu"> -->
        <div class="col-4 text-right">
            <span class="navbar-text"><?php echo htmlspecialchars($_SESSION["username"]) . " - " . $userLevel; ?></span>            
            <br>
            <a href="reset-password.php" class="btn btn-warning">Alterar sua senha</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
    <!-- <div class="row"> -->
        
    <!-- </div> -->
    <br>
    <!-- navegeção: moderação, identificação e consultas -->
    <ul class="nav nav-pills mb-5" id="pills-tab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="pills-consultas-tab" data-toggle="pill" href="#pills-consultas" role="tab" v-on:click="window.location.reload()" aria-controls="pills-consultas" aria-selected="false">Consultas</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="pills-moderacao-tab" ref="btmodera" data-toggle="pill" href="#pills-moderacao" role="tab" aria-controls="pills-moderacao" aria-selected="false">Moderação</a>
        </li>
        <!-- <li class="nav-item">
            <a class="nav-link" id="pills-identificacao-tab" data-toggle="pill" href="#pills-identificacao" role="tab" aria-controls="pills-identificacao" aria-selected="true">Identificação</a>
        </li> -->
    </ul><!-- fim navegação: moderação, identificação e consultas -->
    
    <!-- conteúdo da tab: identificação --> 
    <div class="tab-content" id="pills-tabContent">

        <!-- conteúdo das tabs: consultas -->
        <div class="tab-pane fade show active" id="pills-consultas" role="tabpanel" aria-labelledby="pills-consultas-tab">
            <p class="h2 mb-5">Lista geral de consultas</p>

            <table class="table text-center">
                <thead class="thead-dark">
                    <tr>
                        <th scope="col">Id</th>
                        <th class="text-left" scope="col">Nome</th>
                        <th scope="col">Início</th>
                        <th scope="col">Término</th>
                        <th scope="col">Status</th>
                        <th scope="col">Pendente</th>
                        <th scope="col">Aprovados</th>
                        <th scope="col">Reprovados</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="consulta in consultas" v-on:click="carregaConsulta(consulta.id)" href="#" class="list-group-item-action cursor-pointer">
                        <th class="align-middle" scope="row">{{ consulta.id }}</th>
                        <th class="text-left align-middle">{{ consulta.nome }}</th>
                        <td class="align-middle"><small>{{ consulta.inicio.split("-")[2] + "/" + consulta.inicio.split("-")[1] + "/" +consulta.inicio.split("-")[0] }}</small></td>
                        <td class="align-middle"><small>{{ consulta.termino.split("-")[2] + "/" + consulta.termino.split("-")[1] + "/" +consulta.termino.split("-")[0] }}</small></td>
                        <td class="align-middle"><span :class="{ 'badge': true, 'badge-success': consulta.status == 1, 'badge-danger': consulta.status != 1 }">{{ (consulta.status == 1 ? "Aberta" : "Encerrada") }}</span></td>
                        <td class="align-middle"><span class="badge badge-pill badge-warning">{{ calculaComentarios(consulta.id, 2) }}</span></td>
                        <td class="align-middle"><span class="badge badge-pill badge-success">{{ calculaComentarios(consulta.id, 1) }}</span></td>
                        <td class="align-middle"><span class="badge badge-pill badge-danger">{{ calculaComentarios(consulta.id, 0) }}</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- fim conteúdo das tabs: consultas -->

        <!-- conteúdo das tabs: moderação -->
        <div class="tab-pane fade" id="pills-moderacao" role="tabpanel" aria-labelledby="pills-moderacao-tab">

            <p class="h2 mb-5">{{ consultaAtual.nome }}</p>
            <!-- Botão para exportar CSV -->
            <div class="text-right">
                <div class="btn-group">
                    <button type="button" class="btn btn-info" v-on:click="exportarCSV()">Exportar CSV</button>
                    <button type="button" class="btn btn-info dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span class="sr-only">Ver opções</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="#" v-on:click="exportarCSV()">Todos os comentários</a>
                        <a class="dropdown-item" href="#" v-on:click="exportarCSV(true)">Somente aprovados</a>
                    </div>
                </div>
            </div>
            <!-- fim Botão para exportar CSV -->

            <!-- navegação de pendente, aprovado, reprovado -->
            <nav>
                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-home" role="tab" aria-controls="nav-home" aria-selected="true">Pendentes <span class="badge badge-warning">{{ pendentes.length }}</span></a>
                    <a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#nav-profile" role="tab" aria-controls="nav-profile" aria-selected="false">Aprovados <span class="badge badge-success">{{ aprovados.length }}</span></a>
                    <a class="nav-item nav-link" id="nav-contact-tab" data-toggle="tab" href="#nav-contact" role="tab" aria-controls="nav-contact" aria-selected="false">Reprovados <span class="badge badge-danger">{{ reprovados.length }}</span></a>
                </div>
            </nav>
            <!-- fim navegação de pendente, aprovado, reprovado -->

            <!-- conteúdo pendente-, aprovado e reprovado -->
            <div class="tab-content" id="nav-tabContent">
                <!-- conteúdo de pendente -->
                <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
                    <div class="card border-warning mt-3" v-for="(comentario, index) in pendentes">
                        <div class="card-header bg-warning">
                            <b>Comentado por:</b> {{ comentario.autor }} - {{ comentario.email }} <b>em</b> {{ comentario.data }}
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">{{ comentario.trecho }}</h5>
                            <p class="card-text">{{ comentario.comentario }}</p>
                            <!-- <a href="#" class="btn btn-success float-right">Aprovar</a> -->
                            <button class="btn btn-success float-right m-1" type="button" v-on:click="moderarComentario(comentario.id, true, index)">Aprovar</button>
                            <button class="btn btn-danger float-right m-1" type="button" v-on:click="moderarComentario(comentario.id, false, index)">Reprovar</button>
                        </div>
                    </div>
                    <div class="card border-warning mt-3" v-if="pendentes.length === 0">
                        <div class="card-header bg-warning">
                            <b>Não há comentários pendentes.</b>
                        </div>
                    </div>
                </div>

                <!-- fim comentário pendente -->

                <!-- comentário aprovado -->
                <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                    <!-- <div class="card border-success mt-3" v-for="comentario in consultaAtual.comentarios.aprovados"> -->
                    <div class="card border-success mt-3" v-for="(comentario, index) in aprovados">
                        <div class="card-header bg-success text-white">
                            <b>Comentado por:</b> {{ comentario.autor }} - {{ comentario.email }} <b>em</b> {{ comentario.data }}
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">{{ comentario.trecho }}</h5>
                            <p class="card-text">{{ comentario.comentario }}</p>
                            <button class="btn btn-danger float-right m-1" type="button" v-on:click="moderarComentario(comentario.id, false, index)">Mover para Reprovados</button>
                        </div>
                    </div>
                    <div class="card border-success mt-3" v-if="aprovados.length === 0">
                        <div class="card-header bg-success text-white">
                            <b>Não há comentários aprovados.</b>
                        </div>
                    </div>
                </div>
                <!-- fim comentário aprovado -->

                <!-- comentário reprovado -->
                <div class="tab-pane fade" id="nav-contact" role="tabpanel" aria-labelledby="nav-contact-tab">
                    <div class="card border-danger mt-3" v-for="(comentario, index) in reprovados">
                        <div class="card-header bg-danger text-white">
                            <b>Comentado por:</b> {{ comentario.autor }} - {{ comentario.email }} <b>em</b> {{ comentario.data }}
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">{{ comentario.trecho }}</h5>
                            <p class="card-text">{{ comentario.comentario }}</p>
                            <button class="btn btn-success float-right m-1" type="button" v-on:click="moderarComentario(comentario.id, true, index)">Mover para Aprovados</button>
                        </div>
                    </div>
                    <div class="card border-danger mt-3" v-if="reprovados.length === 0">
                        <div class="card-header bg-danger text-white">
                            <b>Não há comentários reprovados.</b>
                        </div>
                    </div>
                </div>
                <!-- fim comentário reprovado -->
            </div>
            <!-- fim conteúdo pendente, aprovado e reprovado -->
        </div>
        <!-- fim conteúdo das tabs: moderação -->
    </div>
</div>
    
<script type="text/javascript" src="js/jquery-3.3.1.min.js"></script>
<script type="text/javascript" src="js/lodash.min.js"></script>
<script type="text/javascript" src="js/popper.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script>
    const consultaMaisRecente = function() {
        let abertas = rawConsultas.filter(function(val){return val.status === "1"})
        let maisRecente = abertas[0];
        for (i in abertas) {
            let inicioI = new Date(abertas[i].inicio);
            let inicioRec = new Date(maisRecente.inicio);
            if(inicioI.getTime() > inicioRec.getTime())
                maisRecente = abertas[i];                    
        }
        return maisRecente;
    };
</script>
<!-- Vue.js -->
<script>    
    var app = new Vue({
        el: '#app',
        data: {
            moderaConsulta: {},            
            consultasAbertas: rawConsultas.filter(function(val){return val.status === "1"}),
            consultaAtual: consultaMaisRecente(),
            aprovados: [],
            reprovados: [],
            pendentes: []
        },
        computed: {
            consultas: function() {
                return _.orderBy(rawConsultas, 'status').reverse();
            }
        },
        mounted: function() {
            this.carregaConsulta(this.consultaAtual.id, true);
        },
        methods: {            
            calculaComentarios: function (consultaID, categoria) {
                // Calcula e retorna a quantidade de comentários da consulta informada
                let pendentes = 0;
                let aprovados = 0, reprovados = 0;
                for (i in rawComentarios) {
                    // Percorre comentarios e acrescenta à contagem
                    if(rawComentarios[i].idConsulta === consultaID) {
                        pendentes++;
                        aprovados += parseInt(rawComentarios[i].aprovado);
                        reprovados += parseInt(rawComentarios[i].reprovado);
                    }
                };
                pendentes -= aprovados+reprovados;
                switch (categoria) {
                    case 0:
                        return reprovados;
                        break;
                    case 1:
                        return aprovados;
                        break;
                    default:
                        return pendentes;
                        break;
                }
            },
            comentariosDaConsulta: function (consultaID) {
                // Retorna os comentarios da consulta informada
                let comentarios = {
                    reprovados: [],
                    aprovados: [],
                    pendentes: []
                };
                for (i in rawComentarios) {
                    if(rawComentarios[i].idConsulta === consultaID) {
                        if(parseInt(rawComentarios[i].aprovado) === 1)
                            comentarios.aprovados.push(rawComentarios[i]);
                        else if(parseInt(rawComentarios[i].reprovado) === 1)
                            comentarios.reprovados.push(rawComentarios[i]);
                        else
                            comentarios.pendentes.push(rawComentarios[i]);
                    }
                }
                return comentarios;
            },
            carregaConsulta: function (consultaID, skipClick = false, retPend = false) {
                this.consultaAtual = this.consultas.filter(function(val){return val.id === consultaID})[0];
                this.consultaAtual.comentarios = this.comentariosDaConsulta(this.consultaAtual.id);
                // Limpa os arrays para inserir os novos itens
                this.aprovados = [];
                this.reprovados = [];
                this.pendentes = [];

                // Para cada categoria, insere o comentário no respectivo array
                for (c in this.consultaAtual.comentarios.aprovados){                    
                    this.aprovados.push(this.consultaAtual.comentarios.aprovados[c]);
                }
                for (c in this.consultaAtual.comentarios.reprovados){                    
                    this.reprovados.push(this.consultaAtual.comentarios.reprovados[c]);
                }
                for (c in this.consultaAtual.comentarios.pendentes){                    
                    this.pendentes.push(this.consultaAtual.comentarios.pendentes[c]);
                }

                if (!skipClick)
                    this.$refs.btmodera.click();
                if (retPend)
                    return this.consultaAtual.pendentes;
            },
            exportarCSV: function(aprovados) {
                window.location = ("exportar-csv.php?consulta="+this.consultaAtual.id+"&ip="+sessionIP+"&aprovados="+(aprovados ? '1' : '0'));
            },
            /** 
                MODERAÇÃO DE COMENTÁRIOS 
            */
            moderarComentario: function (idComentario, aprovar, cIndex) {
                // Modera comentário e atualiza lista
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        // Comentário moderado com sucesso. Atualiza lista
                        if(aprovar)
                            app.aprovados.push(app.reprovados.splice(cIndex, 1)[0]);
                        else
                            app.reprovados.push(app.aprovados.splice(cIndex, 1)[0]);
                    }
                };
                xhttp.open("POST", "moderar.php?memid="+idComentario+"&aprovar="+(aprovar ? '1' : '0')+"&consulta="+this.consultaAtual.id+"&ip="+sessionIP, true);
                xhttp.send();
            }
        }
    });
</script>

</body>
</html>