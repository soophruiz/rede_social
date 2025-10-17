<?php
session_start();
include "conexao.php";

if(!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

$usuario_id = $_SESSION['id'];

/* ---------------- CONTADORES ---------------- */
if(isset($_GET['acao']) && $_GET['acao'] == 'contadores'){
    header('Content-Type: application/json; charset=utf-8');
    $id = intval($_SESSION['id']);

    // mensagens não lidas (ajuste o nome da coluna se for diferente)
    $q1 = $con->query("SELECT COUNT(*) AS total FROM mensagens WHERE destinatario_id = $id AND lida = 0");
    $mensagens = ($q1 && $q1->num_rows > 0) ? intval($q1->fetch_assoc()['total']) : 0;

    // notificações não lidas (usa a coluna 'lida', igual ao seu outro trecho)
    $q2 = $con->query("SELECT COUNT(*) AS total FROM notificacoes WHERE usuario_id = $id AND lida = 0");
    $notificacoes = ($q2 && $q2->num_rows > 0) ? intval($q2->fetch_assoc()['total']) : 0;

    echo json_encode([
        'mensagens' => $mensagens,
        'notificacoes' => $notificacoes
    ]);
    exit;
}


/* ---------------- DADOS DO USUÁRIO LOGADO ---------------- */
$stmtUser = $con->prepare("SELECT id, nome, foto_perfil FROM usuarios WHERE id = ?");
$stmtUser->bind_param("i", $usuario_id);
$stmtUser->execute();
$usuario = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

if(!$usuario) {
    echo "Usuário não encontrado. Faça login novamente.";
    session_destroy();
    exit;
}

/* ---------------- POSTAR ---------------- */
if(isset($_POST['acao']) && $_POST['acao'] === 'postar') {
    $conteudo = trim($_POST['conteudo']);
    $imagem = null;

    if(!empty($_FILES['imagem']['name'])) {
        if ($_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
            $dir = __DIR__ . "/postagens/";
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $nomeArquivo = uniqid() . "." . $ext;
            $imagem = "postagens/" . $nomeArquivo;
            move_uploaded_file($_FILES['imagem']['tmp_name'], $dir . $nomeArquivo);
        }
    }

    $stmt = $con->prepare("INSERT INTO postagens (usuario_id, conteudo, imagem) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $usuario_id, $conteudo, $imagem);
    $stmt->execute();
    header("Location: feed.php");
    exit;
}

/* ---------------- EXCLUIR ---------------- */
if(isset($_GET['excluir'])) {
    $id_post = intval($_GET['excluir']);
    $stmt = $con->prepare("DELETE FROM postagens WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $id_post, $usuario_id);
    $stmt->execute();
    header("Location: feed.php");
    exit;
}

/* ---------------- EDITAR ---------------- */
if(isset($_POST['acao']) && $_POST['acao'] === 'editar') {
    $id_post = intval($_POST['id_post']);
    $nova_legenda = trim($_POST['nova_legenda']);
    $stmt = $con->prepare("UPDATE postagens SET conteudo = ? WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("sii", $nova_legenda, $id_post, $usuario_id);
    $stmt->execute();
    header("Location: feed.php");
    exit;
}

/* ---------------- CURTIR ---------------- */
if(isset($_GET['curtir'])) {
    $id_post = intval($_GET['curtir']);
    $check = $con->prepare("SELECT * FROM curtidas WHERE usuario_id = ? AND postagem_id = ?");
    $check->bind_param("ii", $usuario_id, $id_post);
    $check->execute();
    $res = $check->get_result();

    if($res->num_rows == 0) {
        $stmt = $con->prepare("INSERT INTO curtidas (usuario_id, postagem_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $usuario_id, $id_post);
        $stmt->execute();

        $stmtPost = $con->prepare("SELECT usuario_id FROM postagens WHERE id = ?");
        $stmtPost->bind_param("i", $id_post);
        $stmtPost->execute();
        $donoPostRow = $stmtPost->get_result()->fetch_assoc();
        $stmtPost->close();

        if($donoPostRow) {
            $donoPost = $donoPostRow['usuario_id'];
            if($donoPost != $usuario_id) {
                $tipo = 'curtida';
                $stmtNotif = $con->prepare("INSERT INTO notificacoes (usuario_id, remetente_id, tipo, referencia_id) VALUES (?, ?, ?, ?)");
                $stmtNotif->bind_param("iisi", $donoPost, $usuario_id, $tipo, $id_post);
                $stmtNotif->execute();
            }
        }
    } else {
        $del = $con->prepare("DELETE FROM curtidas WHERE usuario_id = ? AND postagem_id = ?");
        $del->bind_param("ii", $usuario_id, $id_post);
        $del->execute();
    }
    header("Location: feed.php");
    exit;
}

/* ---------------- COMENTAR ---------------- */
if(isset($_POST['acao']) && $_POST['acao'] === 'comentar') {
    $id_post = intval($_POST['id_post']);
    $conteudo = trim($_POST['conteudo']);
    if($conteudo != "") {
        $stmt = $con->prepare("INSERT INTO comentarios (postagem_id, usuario_id, conteudo) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $id_post, $usuario_id, $conteudo);
        $stmt->execute();

        $stmtPost = $con->prepare("SELECT usuario_id FROM postagens WHERE id = ?");
        $stmtPost->bind_param("i", $id_post);
        $stmtPost->execute();
        $donoPost = $stmtPost->get_result()->fetch_assoc()['usuario_id'];
        $stmtPost->close();

        if($donoPost != $usuario_id) {
            $tipo = 'comentario';
            $stmtNotif = $con->prepare("INSERT INTO notificacoes (usuario_id, remetente_id, tipo, referencia_id) VALUES (?, ?, ?, ?)");
            $stmtNotif->bind_param("iisi", $donoPost, $usuario_id, $tipo, $id_post);
            $stmtNotif->execute();
        }
    }
    header("Location: feed.php");
    exit;
}

/* ---------------- BUSCAR NOTIFICAÇÕES ---------------- */
if(isset($_GET['acao']) && $_GET['acao'] === 'notificacoes') {
    $sqlN = "SELECT n.*, u.nome FROM notificacoes n
              JOIN usuarios u ON n.remetente_id = u.id
              WHERE n.usuario_id = ? AND n.lida = 0
              ORDER BY n.data_hora DESC";
    $stmt = $con->prepare($sqlN);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $resN = $stmt->get_result();
    $notifs = [];
    while($n = $resN->fetch_assoc()) {
        $msg = '';
        if($n['tipo'] == 'comentario') $msg = "{$n['nome']} comentou em sua postagem.";
        if($n['tipo'] == 'mensagem') $msg = "{$n['nome']} enviou uma mensagem.";
        if($n['tipo'] == 'curtida') $msg = "{$n['nome']} curtiu sua postagem.";
        $notifs[] = ['id'=>$n['id'], 'msg'=>$msg, 'data'=>$n['data_hora']];
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($notifs);
    exit;
}

/* ---------------- BUSCAR USUÁRIOS ---------------- */
if(isset($_GET['acao']) && $_GET['acao'] === 'buscar_usuarios') {
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $like = "%{$q}%";
    $stmt = $con->prepare("SELECT id, nome, foto_perfil FROM usuarios WHERE nome LIKE ? ORDER BY nome LIMIT 8");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $res = $stmt->get_result();
    $users = [];
    while($u = $res->fetch_assoc()) {

        // Checa se já segue
        $stmtSeg = $con->prepare("SELECT 1 FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
        $stmtSeg->bind_param("ii", $usuario_id, $u['id']);
        $stmtSeg->execute();
        $jaSegue = $stmtSeg->get_result()->num_rows > 0;
        $stmtSeg->close();

        $users[] = [
            'id' => $u['id'],
            'nome' => $u['nome'],
            'foto_perfil' => $u['foto_perfil'] ? $u['foto_perfil'] : 'perfil/default.png',
            'segue' => $jaSegue
        ];
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($users);
    exit;
}

/* ---------------- SEGUIR / DEIXAR DE SEGUIR ---------------- */
if(isset($_POST['acao']) && $_POST['acao'] === 'seguir_toggle') {
    $id_alvo = intval($_POST['id_alvo']);
    $check = $con->prepare("SELECT * FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
    $check->bind_param("ii", $usuario_id, $id_alvo);
    $check->execute();
    $res = $check->get_result();
    if($res->num_rows > 0) {
        $del = $con->prepare("DELETE FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
        $del->bind_param("ii", $usuario_id, $id_alvo);
        $del->execute();
        echo "unfollowed";
    } else {
        $ins = $con->prepare("INSERT INTO seguidores (seguidor_id, seguido_id) VALUES (?, ?)");
        $ins->bind_param("ii", $usuario_id, $id_alvo);
        $ins->execute();
        echo "followed";
    }
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Feed</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/rede_social/style_feed.css?v=<?php echo time(); ?>">


<script>
function buscarNotificacoes(){
    fetch('feed.php?acao=notificacoes')
        .then(res=>res.json())
        .then(data=>{
            const div=document.getElementById('notificacoes');
            if(!data || data.length===0){ div.innerHTML='🔔 Nenhuma nova notificação.'; }
            else{
                let html='<b> Novas notificações:</b><ul style="margin:8px 0 0 14px;padding:0;">';
                data.forEach(n=>{html+=`<li style="margin-bottom:6px;">${n.msg} <small style="color:#888;">(${n.data})</small></li>`;});
                html+='</ul>'; div.innerHTML=html;
            }
        });
}
window.onload = function(){
    buscarNotificacoes();
    atualizarContadores(); 

    setInterval(buscarNotificacoes, 5000);
    setInterval(atualizarContadores, 5000);

    document.addEventListener('click', function(e){
        const sr = document.getElementById('search-results');
        const inp = document.getElementById('user-search-input');
        if(!sr) return;
        if(!sr.contains(e.target) && e.target !== inp){
            sr.style.display = 'none';
        }
    });
};


let searchTimeout = null;
function buscarUsuarios(q){
    if(q.trim()===''){
        const sr=document.getElementById('search-results');
        if(sr) sr.style.display='none';
        return;
    }
    if(searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(()=>{
        fetch('feed.php?acao=buscar_usuarios&q=' + encodeURIComponent(q))
            .then(res=>res.json())
            .then(data=>{
                const sr = document.getElementById('search-results');
                if(!sr) return;
                if(!data || data.length===0){
                    sr.innerHTML = '<div style="padding:12px;color:#777;">Nenhum usuário encontrado</div>';
                    sr.style.display = 'block';
                    return;
                }
                let html = '';
                data.forEach(u=>{
    const btnText = u.segue ? 'Deixar de seguir' : 'Seguir';
    html += `<div class="result-item">
                <div onclick="window.location='perfil_pesquisado.php?id=${u.id}'" style="display:flex;align-items:center;cursor:pointer;">
                    <img src="${u.foto_perfil}" alt="Foto">
                    <div style="flex:1;margin-left:8px;">
                        <strong>${u.nome}</strong>
                        <div style="color:#777;font-size:13px">Ver perfil</div>
                    </div>
                </div>
                <button class="follow-btn" onclick="event.stopPropagation();toggleSeguir(${u.id}, this)">${btnText}</button>
             </div>`;
});
                sr.innerHTML = html;
                sr.style.display = 'block';
            });
    }, 250);
}

function toggleSeguir(id, btn){
    const formData = new FormData();
    formData.append('acao', 'seguir_toggle');
    formData.append('id_alvo', id);
    fetch('feed.php', {method:'POST', body:formData})
        .then(res=>res.text())
        .then(resp=>{
            if(resp === 'followed'){ btn.textContent = 'Deixar de seguir'; }
            else if(resp === 'unfollowed'){ btn.textContent = 'Seguir'; }
        });
}

function abrirComentario(id) {
  const form = document.getElementById('form-coment' + id);
  if (form) {
    form.style.display = form.style.display === 'block' ? 'none' : 'block';
    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
}
</script>
</head>

<body>
    
<!-- SIDEBAR -->
<div class="sidebar">
    <div class="brand">
        <b>PIXIE</b>
    </div>
    <nav>
        <a href="feed.php">Feed</a>
        <a href="perfil.php">Perfil</a>
        <a href="chat.php" id="menu-mensagens">Mensagens</a>
        <a href="todas_notificacoes.php" id="menu-notificacoes">Notificações</a>
        <a href="logout.php">Sair</a>
    </nav>
</div>

<div class="main-wrap">
    <div class="header-row">
        <h2>Bem-vindo, <?php echo htmlspecialchars($usuario['nome']); ?>!</h2>
        <div class="search-panel">
            <input id="user-search-input" type="text" placeholder="Buscar usuários..." oninput="buscarUsuarios(this.value)" autocomplete="off">
            <div id="search-results" class="search-results" style="display:none;"></div>
        </div>
    </div>

    <div id="notificacoes"> Carregando notificações...</div>

    <div id="nova-postagem">
        <h3>Nova Postagem</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="acao" value="postar">
            <textarea name="conteudo" placeholder="Escreva algo..." required></textarea>
            <input type="file" name="imagem" accept="image/*">
            <button type="submit">Postar</button>
        </form>
    </div>

    <h3>Feed</h3>

<?php
$sql = "SELECT p.id, p.usuario_id, p.conteudo, p.imagem, p.criado_em,
               u.nome, u.foto_perfil,
               (SELECT COUNT(*) FROM curtidas c WHERE c.postagem_id = p.id) AS total_curtidas
        FROM postagens p
        JOIN usuarios u ON p.usuario_id = u.id
        ORDER BY p.id DESC";
$res = $con->query($sql);

if(!$res || $res->num_rows==0){ echo "<p style='text-align:center;color:#666'>Nenhuma postagem ainda.</p>";}
else{
    while($post=$res->fetch_assoc()){
        echo "<div class='post'>";
        echo "<div class='post-header'>";
        $fotoPF = !empty($post['foto_perfil']) ? $post['foto_perfil'] : 'perfil/default.png';
        echo "<img src='".htmlspecialchars($fotoPF)."' alt='Foto de perfil'>";
        echo "<div class='info'><b>".htmlspecialchars($post['nome'])."</b><small style='color:#777;'> ".date("d/m/Y H:i",strtotime($post['criado_em']))."</small></div>";
        echo "</div>";
        echo "<p>".nl2br(htmlspecialchars($post['conteudo']))."</p>";
        if(!empty($post['imagem'])) echo "<img src='".htmlspecialchars($post['imagem'])."' style='max-width:100%;border-radius:8px;margin-bottom:10px;'><br>";

        echo "<form class='inline' action='feed.php' method='get' style='display:inline-block;margin-right:8px;'>
                <input type='hidden' name='curtir' value='".$post['id']."'>
                <button type='submit'>Curtir</button>
              </form> <span style='vertical-align:middle;'>(".$post['total_curtidas'].")</span>";

        echo " | <a href='#' onclick=\"abrirComentario(".$post['id'].")\">
                      <button type='submit'>Comentar</button>
              </a>";

        if($usuario_id==$post['usuario_id']){
            echo " | <a href='feed.php?excluir=".$post['id']."' onclick='return confirm(\"Excluir esta postagem?\")'>
                          <button type='submit'>Excluir</button>
                  </a>";
            echo " | <a href='#' onclick=\"document.getElementById('form-editar".$post['id']."').style.display='block'\">
                         <button type='submit'>Editar legenda</button>
                  </a>";
        }

        echo "<form id='form-editar".$post['id']."' method='POST' style='display:none;margin-top:10px;'>
                <input type='hidden' name='acao' value='editar'>
                <input type='hidden' name='id_post' value='".$post['id']."'>
                <textarea name='nova_legenda' required>".htmlspecialchars($post['conteudo'])."</textarea>
                <button type='submit'>Salvar</button>
              </form>";

        $sqlC = "SELECT c.conteudo,c.criado_em,u.nome FROM comentarios c
                  JOIN usuarios u ON c.usuario_id=u.id
                  WHERE c.postagem_id={$post['id']} ORDER BY c.id ASC";
        $resC = $con->query($sqlC);
        while($com=$resC->fetch_assoc()){
            $dataComent=date("d/m/Y H:i",strtotime($com['criado_em']));
            echo "<div class='comentario'>
                    <b>".htmlspecialchars($com['nome'])."</b> <small style='color:#555;'>($dataComent)</small><br>
                    ".htmlspecialchars($com['conteudo'])."
                  </div>";
        }

        echo "<form id='form-coment".$post['id']."' method='POST' style='display:none;margin-top:10px;'>
                <input type='hidden' name='acao' value='comentar'>
                <input type='hidden' name='id_post' value='".$post['id']."'>
                <input type='text' name='conteudo' placeholder='Escreva um comentário...' required>
                <button type='submit'>Enviar</button>
              </form>";

        echo "</div>";
    }
}
?>
</div>
<script>
function atualizarContadores(){
    fetch('feed.php?acao=contadores')
        .then(res => res.json())
        .then(data => {
            const spanMsg = document.getElementById('contador-mensagens');
            const spanNot = document.getElementById('contador-notificacoes');
            if(spanMsg) spanMsg.textContent = `(${data.mensagens})`;
            if(spanNot) spanNot.textContent = `(${data.notificacoes})`;
        })
        .catch(err => console.error('Erro contador:', err));
}

</script>
</body>
</html>
