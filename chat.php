<?php
session_start();
include "conexao.php";

if(!isset($_SESSION['id'])) { header("Location: index.php"); exit; }
$usuario_id = intval($_SESSION['id']);

// atualiza último acesso
mysqli_query($con, "UPDATE usuarios SET ultimo_acesso = NOW() WHERE id='$usuario_id'");

/* ---------- AÇÕES POST ---------- */
// enviar mensagem
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['acao']) && $_POST['acao']=='enviar'){
    $msg = trim($_POST['mensagem']);
    $dest = intval($_POST['destinatario']);
    if($msg != '' && $dest>0){
        $msg_esc = mysqli_real_escape_string($con, $msg);
        mysqli_query($con, "INSERT INTO mensagens (id_remetente,id_destinatario,conteudo,data_hora,lida) VALUES ('$usuario_id','$dest','$msg_esc',NOW(),0)");
    }
    exit;
}

// reagir (toggle)
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['acao']) && $_POST['acao']=='reagir'){
    $msg_id = intval($_POST['msg_id']);
    $reacao = substr(mysqli_real_escape_string($con, $_POST['reacao']), 0, 20);
    if($msg_id>0 && $reacao!==''){
        // busca se já existe qualquer reação do usuário nessa mensagem
        $check = mysqli_query($con, "SELECT id, reacao FROM mensagens_reacoes WHERE id_mensagem='$msg_id' AND id_usuario='$usuario_id' LIMIT 1");
        if($check && mysqli_num_rows($check)>0){
            $row = mysqli_fetch_assoc($check);
            if($row['reacao'] === $reacao){
                // se clicou no mesmo emoji → remove
                mysqli_query($con, "DELETE FROM mensagens_reacoes WHERE id='{$row['id']}'");
            } else {
                // se clicou em emoji diferente → atualiza
                mysqli_query($con, "UPDATE mensagens_reacoes SET reacao='$reacao' WHERE id='{$row['id']}'");
            }
        } else {
            // se não tinha reagido → insere
            mysqli_query($con, "INSERT INTO mensagens_reacoes (id_mensagem,id_usuario,reacao) VALUES ('$msg_id','$usuario_id','$reacao')");
        }
    }
    exit;
}

/* ---------- AÇÕES GET ---------- */

// atualizar conversa 
if(isset($_GET['acao']) && $_GET['acao']=='atualizar' && isset($_GET['destinatario'])){
    $dest = intval($_GET['destinatario']);
    $sql = "SELECT m.*, u.nome FROM mensagens m JOIN usuarios u ON m.id_remetente = u.id
            WHERE (id_remetente='$usuario_id' AND id_destinatario='$dest')
               OR (id_remetente='$dest' AND id_destinatario='$usuario_id')
            ORDER BY m.data_hora ASC";
    $res = mysqli_query($con, $sql);
    $ult_id = 0;
    $ultima_data = '';

    while($m = mysqli_fetch_assoc($res)){
        $data_msg = date("Y-m-d", strtotime($m['data_hora']));
        $hora = date("H:i", strtotime($m['data_hora']));
        $cls = ($m['id_remetente']==$usuario_id) ? 'meu-msg' : 'outro-msg';
        $conteudo = nl2br(htmlspecialchars($m['conteudo']));
        $id_msg = intval($m['id']);

        // separador de data
        if($data_msg != $ultima_data){
            $sep = ($data_msg == date("Y-m-d")) 
                ? "Hoje" 
                : (($data_msg == date("Y-m-d", strtotime("-1 day"))) ? "Ontem" : date("d/m/Y", strtotime($data_msg)));
            echo "<div class='data-separador'>".htmlspecialchars($sep)."</div>";
            $ultima_data = $data_msg;
        }

        // pegar reações agrupadas e se o usuário já reagiu
        $resReacoes = mysqli_query($con, "
            SELECT reacao, COUNT(*) AS cnt, SUM(id_usuario = '$usuario_id') AS minha
            FROM mensagens_reacoes
            WHERE id_mensagem = '$id_msg'
            GROUP BY reacao
        ");

        $reacoes_html = "";
        while($rr = mysqli_fetch_assoc($resReacoes)){
            $r_txt = htmlspecialchars($rr['reacao']);
            $cnt = intval($rr['cnt']);
            $minha = intval($rr['minha']);
            $active = $minha ? 'reacao-ativa' : '';
            // escape single quotes for JS
            $r_js = str_replace("'", "\'", $r_txt);
            $reacoes_html .= "<button class='btn-reacao $active' onclick=\"reagir({$id_msg}, '{$r_js}')\">{$r_txt} <span class='cnt'>{$cnt}</span></button> ";
        }

        // action row: reaction quick picks (emojis)
        $quick = "<span class='quick-reac'>
    <button class='btn-quick' onclick=\"reagir({$id_msg}, '❤️')\">❤️</button>
    <button class='btn-quick' onclick=\"reagir({$id_msg}, '👍')\">👍</button>
    <button class='btn-quick' onclick=\"reagir({$id_msg}, '😂')\">😂</button>
    <button class='btn-quick' onclick=\"reagir({$id_msg}, '😮')\">😮</button>
</span>";

        echo "<div class='msg-row $cls' data-id='$id_msg'>
                <div class='msg-bubble'>
                    <div class='msg-texto'>{$conteudo}</div>
                    <div class='msg-meta'><span class='msg-hora'>{$hora}</span></div>
                </div>
                <div class='msg-reacoes'>{$reacoes_html}{$quick}</div>
              </div>";
        $ult_id = $id_msg;
    }

    // marca como lidas as mensagens recebidas
    mysqli_query($con, "UPDATE mensagens SET lida=1 WHERE id_destinatario='$usuario_id' AND id_remetente='$dest' AND lida=0");

    echo "<span id='ult_id' style='display:none;'>$ult_id</span>";
    exit;
}

// lista de conversas (centro) — retorna HTML de itens
if(isset($_GET['acao']) && $_GET['acao']=='conversas'){
    // pega interlocutores e última mensagem
    $sql = "SELECT
                IF(m.id_remetente = '$usuario_id', m.id_destinatario, m.id_remetente) AS interlocutor,
                u.nome, u.foto_perfil,
                MAX(m.data_hora) AS ultima_data
            FROM mensagens m
            JOIN usuarios u ON u.id = IF(m.id_remetente = '$usuario_id', m.id_destinatario, m.id_remetente)
            WHERE m.id_remetente = '$usuario_id' OR m.id_destinatario = '$usuario_id'
            GROUP BY interlocutor
            ORDER BY ultima_data DESC";
    $res = mysqli_query($con, $sql);
    $out = "";
    while($row = mysqli_fetch_assoc($res)){
        $inter = intval($row['interlocutor']);
        $nome = htmlspecialchars($row['nome']);
        $foto = $row['foto_perfil'] ? htmlspecialchars($row['foto_perfil']) : 'perfil/default.png';

        // buscar ultima mensagem entre eles
        $r2 = mysqli_query($con, "SELECT conteudo, data_hora, id_remetente FROM mensagens 
                                  WHERE (id_remetente IN ($usuario_id,$inter) AND id_destinatario IN ($usuario_id,$inter))
                                  ORDER BY data_hora DESC LIMIT 1");
        $preview = '';
        $hora = '';
        $is_from_me = false;
        if($r2 && $r2a = mysqli_fetch_assoc($r2)){
            $preview = mb_strimwidth(strip_tags($r2a['conteudo']), 0, 60, '...');
            $hora = date("d/m H:i", strtotime($r2a['data_hora']));
            $is_from_me = ($r2a['id_remetente'] == $usuario_id);
        }

        // unread count
        $r3 = mysqli_query($con, "SELECT COUNT(*) AS unread FROM mensagens WHERE id_remetente='$inter' AND id_destinatario='$usuario_id' AND lida=0");
        $unread = 0;
        if($r3 && $r3a = mysqli_fetch_assoc($r3)) $unread = intval($r3a['unread']);

        $cls_unread = $unread>0 ? 'conv-item unread' : 'conv-item';
        $dot = $unread>0 ? "<span class='dot'></span>" : "";

        $out .= "<div class='$cls_unread' data-id='$inter' onclick='abrirConversa($inter)'>
                    <img src='$foto' class='conv-foto'>
                    <div class='conv-info'>
                        <div class='conv-top'>
                            <strong class='conv-nome'>$nome</strong>
                            <div class='conv-right'>
                                <small class='conv-hora'>$hora</small>
                                ".($unread>0 ? "<span class='badge-unread'>$unread</span>" : "")."
                            </div>
                        </div>
                        <div class='conv-txt'>".htmlspecialchars(($is_from_me? 'Você: ' : '').$preview)."</div>
                    </div>
                    $dot
                 </div>";
    }

    // se não tiver conversas mostra para abrir uma
    if(trim($out) === ""){
        $ru = mysqli_query($con, "SELECT id,nome,foto_perfil FROM usuarios WHERE id!='$usuario_id' ORDER BY nome ASC");
        while($u = mysqli_fetch_assoc($ru)){
            $foto = $u['foto_perfil'] ? htmlspecialchars($u['foto_perfil']) : 'perfil/default.png';
            $out .= "<div class='conv-item' data-id='{$u['id']}' onclick='abrirConversa({$u['id']})'>
                        <img src='$foto' class='conv-foto'>
                        <div class='conv-info'>
                          <div class='conv-top'><strong class='conv-nome'>".htmlspecialchars($u['nome'])."</strong><span class='conv-hora'></span></div>
                          <div class='conv-txt' style='color:#999;'>Clique para iniciar conversa</div>
                        </div>
                     </div>";
        }
    }

    echo $out;
    exit;
}

// listar usuários para modal (HTML)
if(isset($_GET['acao']) && $_GET['acao']=='usuarios'){
    $ru = mysqli_query($con, "SELECT id,nome,foto_perfil FROM usuarios WHERE id!='$usuario_id' ORDER BY nome ASC");
    $out = "";
    while($u = mysqli_fetch_assoc($ru)){
        $id = intval($u['id']);
        $nome = htmlspecialchars($u['nome']);
        $foto = $u['foto_perfil'] ? htmlspecialchars($u['foto_perfil']) : 'perfil/default.png';
        $out .= "<div class='user-item' data-id='$id' onclick='selecionarUsuarioModal(this)'>
                    <img src='$foto' style='width:40px;height:40px;border-radius:50%;object-fit:cover;margin-right:8px'>
                    <div style='flex:1'>
                        <strong>$nome</strong><br>
                        <small style='color:#777'>Clique para selecionar</small>
                    </div>
                  </div>";
    }
    echo $out ?: "<div style='padding:10px;color:#888'>Nenhum usuário encontrado</div>";
    exit;
}

// notificacoes (JSON) — pequenas notificações por novas mensagens
if(isset($_GET['acao']) && $_GET['acao']=='notificacoes'){
    // retornar últimas 6 notificações de mensagens recebidas 
    $sql = "SELECT m.*, u.nome FROM mensagens m JOIN usuarios u ON m.id_remetente=u.id
            WHERE m.id_destinatario = '$usuario_id' AND m.lida = 0
            ORDER BY m.data_hora DESC LIMIT 6";
    $res = mysqli_query($con, $sql);
    $out = [];
    while($r = mysqli_fetch_assoc($res)){
        $out[] = [
            'nome' => $r['nome'],
            'conteudo' => mb_strimwidth(strip_tags($r['conteudo']), 0, 80, '...'),
            'hora' => date("H:i", strtotime($r['data_hora']))
        ];
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>PIXIE — Mensagens</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="style_chat.css">
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="brand">
        <img src="logo.png" alt="Logo">
        <b>PIXIE</b>
    </div>
    <nav>
        <a href="feed.php">Feed</a>
        <a href="todas_notificacoes.php">Notificações</a>
        <a href="chat.php" class="active">Mensagens</a>
        <a href="perfil.php">Perfil</a>
        <a href="logout.php">Sair</a>
    </nav>
</div>

<div class="main">
    <div class="col-convs">
        <div class="header-convs">
            <h3>Mensagens</h3>
            <button class="btn-new" onclick="openNewModal()">Nova mensagem</button>
        </div>
        <div id="lista-conversas">
        </div>
    </div>

    <div class="col-chat">
        <div class="chat-top" id="chat-top">
            <div style="flex:1;">
                <strong id="chat-nome">Selecione uma conversa</strong><br>
                <small id="chat-sub" style="color:#777">Última mensagem aparecerá aqui</small>
            </div>
            <div id="chat-actions"></div>
        </div>

        <div class="chat-body" id="chat">
            <div style="text-align:center;color:#888;padding:40px;">Nenhuma conversa selecionada</div>
        </div>

        <div class="chat-compose">
            <input type="text" id="msg_text" placeholder="Escreva uma mensagem..." />
            <button onclick="enviarMensagem()">Enviar</button>
            <button onclick="openNewModal()" title="Nova mensagem">✚</button>
        </div>
    </div>
</div>

<!-- modal new -->
<div id="modal-new" style="display:none;">
    <div class="modal-card">
        <h4>Nova mensagem</h4>
        <div id="lista-usuarios-modal" style="max-height:300px;overflow:auto;margin-bottom:12px;"></div>
        <div style="display:flex;gap:8px;">
            <input type="text" id="nova-msg-text" placeholder="Mensagem..." style="flex:1;padding:8px;border-radius:8px;border:1px solid #ffb3d9">
            <button onclick="criarEnviar()">Enviar</button>
            <button onclick="closeNewModal()" style="background:#eee;color:#333">Cancelar</button>
        </div>
    </div>
</div>

<!-- notif painel -->
<div id="notif-panel" class="notif-panel" style="display:none"></div>

<script>
let conversaAtual = 0;
let lastUltIdChat = 0;

// carregar lista de conversas
function carregarConversas(){
    fetch('chat.php?acao=conversas')
    .then(r => r.text())
    .then(html => {
        document.getElementById('lista-conversas').innerHTML = html;
    })
    .catch(err => { console.error('err carregarConversas', err); });
}

// abrir conversa
function abrirConversa(id){
    conversaAtual = id;
    // ajustar topo com nome/foto
    const item = document.querySelector(".conv-item[data-id='"+id+"']");
    if(item){
        const nome = item.querySelector('.conv-nome').textContent;
        const hora = item.querySelector('.conv-hora') ? item.querySelector('.conv-hora').textContent : '';
        document.getElementById('chat-nome').textContent = nome;
        document.getElementById('chat-sub').textContent = hora;
        // remove destaque (visual)
        item.classList.remove('unread');
    }
    atualizarChat();
    // polling mais rápido quando conversa aberta
    if(window.pollChat) clearInterval(window.pollChat);
    window.pollChat = setInterval(()=>{ if(conversaAtual) atualizarChat(); }, 2000);
}

// atualizar chat atual
function atualizarChat(){
    if(!conversaAtual) return;
    fetch('chat.php?acao=atualizar&destinatario=' + conversaAtual)
    .then(r=>r.text())
    .then(html=>{
        document.getElementById('chat').innerHTML = html;
        const c = document.getElementById('chat');
        c.scrollTop = c.scrollHeight;
        // atualizar conversas
        carregarConversas();

        const ult_span = document.getElementById('ult_id');
        if(ult_span){
            const novo = parseInt(ult_span.textContent) || 0;
            if(lastUltIdChat && novo > lastUltIdChat){
                // se chegou nova e não foi enviada por mim, tocar
                tocarNotificacao();
            }
            lastUltIdChat = novo;
        }
    })
    .catch(err => { console.error('err atualizarChat', err); });
}

// enviar mensagem
function enviarMensagem(){
    const texto = document.getElementById('msg_text').value.trim();
    if(!texto || !conversaAtual) return;
    const fd = new FormData();
    fd.append('acao','enviar');
    fd.append('mensagem', texto);
    fd.append('destinatario', conversaAtual);
    fetch('chat.php',{method:'POST',body:fd})
    .then(()=> {
        document.getElementById('msg_text').value = '';
        setTimeout(atualizarChat, 300);
        setTimeout(carregarConversas, 500);
    })
    .catch(err => { console.error('err enviarMensagem', err); });
}

// reagir (toggle)
function reagir(msg_id, reacao){
    const fd = new FormData();
    fd.append('acao','reagir');
    fd.append('msg_id', msg_id);
    fd.append('reacao', reacao);
    fetch('chat.php',{method:'POST',body:fd}).then(()=> {
        setTimeout(atualizarChat, 200);
        setTimeout(carregarConversas, 400);
    }).catch(err => { console.error('err reagir', err); });
}

// polling conversas e notificações
function iniciarPolling(){
    carregarConversas();
    window.pollConvs = setInterval(carregarConversas, 4000);
    verificarNotificacoes();
    window.pollNotifs = setInterval(verificarNotificacoes, 5000);
}

// notificações
function verificarNotificacoes(){
    fetch('chat.php?acao=notificacoes')
    .then(r=>r.json())
    .then(data=>{
        const panel = document.getElementById('notif-panel');
        if(data && data.length>0){
            panel.style.display = 'block';
            panel.innerHTML = '';
            data.forEach(m=>{
                const d = document.createElement('div');
                d.className = 'notif-item';
                d.textContent = `${m.nome}: ${m.conteudo} (${m.hora})`;
                panel.appendChild(d);
            });
            tocarNotificacao();
            carregarConversas();
        } else {
            panel.style.display = 'none';
        }
    })
    .catch(err => { console.error('err verificarNotificacoes', err); });
}

function tocarNotificacao(){
    try { const audio = new Audio('notificacao.mp3'); audio.play(); } catch(e){}
}

// modal new
function openNewModal(){
    document.getElementById('modal-new').style.display = 'flex';
    fetch('chat.php?acao=usuarios').then(r=>r.text()).then(html=>{
        document.getElementById('lista-usuarios-modal').innerHTML = html;
        // adiciona evento para seleção
        document.querySelectorAll('#lista-usuarios-modal .user-item').forEach(el=>{
            el.addEventListener('click', function(){ selecionarUsuarioModal(el); });
        });
    }).catch(err => { console.error('err openNewModal', err); });
}
function closeNewModal(){ document.getElementById('modal-new').style.display = 'none'; }
function startConversation(id){
    closeNewModal();
    abrirConversa(id);
    setTimeout(carregarConversas,300);
}
function criarEnviar(){
    const selected = document.querySelector('#lista-usuarios-modal .user-item.selecionado');
    if(!selected) return alert('Selecione um usuário clicando no nome');
    const id = selected.getAttribute('data-id');
    const msg = document.getElementById('nova-msg-text').value.trim();
    if(!msg) return alert('Escreva a mensagem');
    const fd = new FormData();
    fd.append('acao','enviar'); fd.append('mensagem', msg); fd.append('destinatario', id);
    fetch('chat.php',{method:'POST',body:fd}).then(()=> { closeNewModal(); setTimeout(()=>{ abrirConversa(id); carregarConversas(); },400); });
}

// selecionar usuário no modal
function selecionarUsuarioModal(el){
    document.querySelectorAll('#lista-usuarios-modal .user-item').forEach(x=>x.classList.remove('selecionado'));
    el.classList.add('selecionado');
}

document.addEventListener('click', function(e){
    const conv = e.target.closest('.conv-item');
    if(conv){
        const id = conv.getAttribute('data-id');
        abrirConversa(id);
        document.querySelectorAll('.conv-item').forEach(x=>x.classList.remove('active'));
        conv.classList.add('active');
    }
});

window.onload = function(){
    iniciarPolling();
    setTimeout(()=>{ const first = document.querySelector('.conv-item'); if(first) first.click(); }, 800);
};
</script>
</body>
</html>
