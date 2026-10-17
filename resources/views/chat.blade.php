<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Buddy - Learn English</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:linear-gradient(#d9f1ff,#fff4c9);min-height:100vh;font-family:'Comic Sans MS','Segoe UI',sans-serif}
.chat{height:58vh;overflow-y:auto;background:#fff;border-radius:22px;padding:14px;box-shadow:0 6px 18px #0002}
.b{padding:10px 15px;border-radius:20px;margin:7px 0;max-width:82%;font-size:1.2rem;width:fit-content}
.bot{background:#ffe29a}.me{background:#b5e3ff;margin-left:auto}
.lc{width:100%!important;max-width:100%!important;text-align:center}
.bnl{color:#6b5b2a;font-size:1.05rem}
</style>
</head>
<body>
<div class="container py-3" style="max-width:640px">
<h1 class="text-center">🐻 Buddy</h1>

@if(!$child)
  <div class="card p-4 text-center">
    <h3>Hi! What is your name?</h3>
    <div class="text-muted mb-2">হ্যালো! তোমার নাম কী?</div>
    <form method="POST" action="/start">@csrf
      <input name="name" class="form-control form-control-lg my-3 text-center" maxlength="30" required autofocus placeholder="✍️">
      <button class="btn btn-warning btn-lg">Let's play! 🎈</button>
    </form>
  </div>
@else
  <div class="d-flex justify-content-between align-items-center mb-2">
    <span class="badge bg-success fs-6" id="lvl">Level {{ $child->level }}</span>
    <span id="stars" class="fs-5">⭐ {{ $child->xp }}</span>
    <span class="badge bg-secondary" id="mode">mode: -</span>
  </div>
  <div class="progress mb-2" style="height:14px"><div id="bar" class="progress-bar bg-warning"></div></div>

  <div id="chat" class="chat">
    <div class="b bot">Hello {{ $child->name }}! I am Buddy 🐻<div class="bnl">হ্যালো {{ $child->name }}! আমি বাডি।</div></div>
  </div>

  <div class="my-2 d-flex flex-wrap gap-2 align-items-center">
    <button class="btn btn-warning btn-lg fw-bold" onclick="send('Teach me!',true)">📚 Learn English <small>/ ইংরেজি শিখি</small></button>
    <button class="btn btn-outline-success btn-lg" onclick="send('I feel happy',false,'😊')">😊</button>
    <button class="btn btn-outline-danger btn-lg" onclick="send('I feel sad',false,'😢')">😢</button>
    <button class="btn btn-outline-dark btn-lg" onclick="send('I feel angry',false,'😠')">😠</button>
    <button class="btn btn-outline-secondary btn-lg" onclick="send('I feel tired',false,'😴')">😴</button>
    <button class="btn btn-outline-primary btn-lg" onclick="send('Tell me a joke',false,'🎭')">🎭</button>
  </div>

  <div class="input-group">
    <input id="msg" class="form-control form-control-lg" maxlength="300" placeholder="✍️ ..." autocomplete="off">
    <button class="btn btn-danger btn-lg" onclick="listen()">🎤</button>
    <button class="btn btn-primary btn-lg" id="send">Send</button>
  </div>
  <div class="d-flex justify-content-between mt-2 small text-muted">
    <label><input type="checkbox" id="speak" checked> 🔊</label>
    <label><input type="checkbox" id="bnToggle" checked> 🇧🇩 বাংলা</label>
    <span id="ms"></span>
    <form method="POST" action="/reset" class="m-0">@csrf<button class="btn btn-link btn-sm p-0">New child</button></form>
  </div>

<script>
const chat=document.getElementById('chat'), msg=document.getElementById('msg');
const token=document.querySelector('meta[name=csrf-token]').content;
const H={'Content-Type':'application/json','X-CSRF-TOKEN':token,'Accept':'application/json'};
let level={{ $child->level }}, xp={{ $child->xp }};
let unit=null, order=[], qi=0, right=0;
const bnOn=()=>document.getElementById('bnToggle').checked;
const speakOn=()=>document.getElementById('speak').checked;
const PRAISE_BN=['দারুণ!','চমৎকার!','তুমি পেরেছো!'];
const TRY_BN='কোনো সমস্যা নেই! ভুল থেকেই আমরা শিখি।';

function el(tag,cls,text){const e=document.createElement(tag); if(cls) e.className=cls; if(text!==undefined) e.textContent=text; return e;}
function push(n){chat.appendChild(n); chat.scrollTop=chat.scrollHeight; return n;}
function add(text,cls,bn){const d=el('div','b '+cls,text);
  if(bn && bnOn()) d.append(el('div','bnl',bn)); return push(d);}

function pct(l,x){ if(l==1) return x/6*100; if(l==2) return (x-6)/9*100; return 100; }
function render(){ document.getElementById('lvl').textContent='Level '+level;
  document.getElementById('stars').textContent='⭐ '+xp;
  document.getElementById('bar').style.width=Math.min(100,Math.max(0,pct(level,xp)))+'%'; }
render();

function speak(t,lang='en-US',rate=.8){
  if(!speakOn()||!('speechSynthesis' in window)) return;
  if(lang!=='en-US' && !speechSynthesis.getVoices().some(v=>v.lang.toLowerCase().startsWith(lang.slice(0,2)))) return;
  const u=new SpeechSynthesisUtterance(t); u.lang=lang; u.rate=rate; u.pitch=1.15; speechSynthesis.speak(u);
}
function say(t){ if('speechSynthesis' in window) speechSynthesis.cancel();
  speak(t.replace(/[^\p{L}\p{N}\s.,!?']/gu,''),'en-US',.85); }
function playWord(it){ if('speechSynthesis' in window) speechSynthesis.cancel();
  speak(it.en); if(bnOn()) speak(it.bn,'bn-BD',.9); }

function norm(s){return s.toLowerCase().replace(/[^a-z\s]/g,'').trim();}
function closeEnough(heard,target){
  const t=norm(target).split(/\s+/), h=norm(heard).split(/\s+/);
  return t.filter(w=>h.includes(w)).length/t.length>=0.6;
}

function runUnit(U){
  unit=U;
  add('👂 Listen → 🎤 Say → 👆 Tap','bot','শোনো → বলো → ট্যাপ করো');
  meet(0);
}

function meet(k){
  const it=unit.items[k], c=el('div','b bot lc');
  c.append(el('div','small text-muted','👂 '+(k+1)+' / '+unit.items.length));
  const big=el('div','',it.emoji); big.style.fontSize='4.5rem'; c.append(big);
  const w=el('div','fw-bold',it.en); w.style.fontSize='2rem'; c.append(w);
  if(bnOn()){ const b=el('div','bnl',it.bn); b.style.fontSize='1.5rem'; c.append(b); }
  const fb=el('div','my-2'); fb.style.minHeight='1.6em'; c.append(fb);
  const row=el('div','d-flex justify-content-center gap-2 flex-wrap');
  const hear=el('button','btn btn-outline-primary btn-lg','🔊');
  const mic=el('button','btn btn-danger btn-lg','🎤');
  const nxt=el('button','btn btn-warning btn-lg','➡️'); nxt.style.display='none';
  hear.onclick=()=>playWord(it);
  mic.onclick=()=>{
    const SR=window.SpeechRecognition||window.webkitSpeechRecognition;
    if(!SR){ fb.textContent='🎤 Chrome / Edge'; nxt.style.display=''; return; }
    const rec=new SR(); rec.lang='en-US'; rec.maxAlternatives=5;
    fb.textContent='👂 ...'; mic.disabled=true;
    rec.onresult=e=>{
      const alts=[...e.results[0]].map(a=>a.transcript);
      const ok=alts.some(a=>closeEnough(a,it.en));
      fb.textContent= ok ? '🌟 Great speaking!' : '🙂 Almost! Listen and try again';
      if(ok){ if(bnOn()) fb.textContent+=' 👏 দারুণ!'; } else playWord(it);
    };
    rec.onerror=()=>{ fb.textContent='🎤 ❌'; };
    rec.onend=()=>{ mic.disabled=false; nxt.style.display=''; };
    rec.start();
  };
  nxt.onclick=()=>{ nxt.disabled=true; k+1<unit.items.length ? meet(k+1) : startQuiz(); };
  row.append(hear,mic,nxt); c.append(row); push(c);
  playWord(it);
}

function startQuiz(){
  order=[...unit.items.keys()].sort(()=>Math.random()-.5); qi=0; right=0;
  add('Now listen 👂 and tap 👆','bot','এবার শোনো আর ঠিক ছবিটি ট্যাপ করো');
  quiz();
}

function quiz(){
  const it=unit.items[order[qi]], c=el('div','b bot lc');
  c.append(el('div','small text-muted','👂 '+(qi+1)+' / '+order.length));
  const hear=el('button','btn btn-primary btn-lg mb-2','🔊 👂');
  hear.onclick=()=>{ if('speechSynthesis' in window) speechSynthesis.cancel(); speak(it.en); };
  const row=el('div','d-flex justify-content-center gap-2 flex-wrap');
  it.options.forEach(o=>{
    const b=el('button','btn btn-light btn-lg border',o.emoji); b.style.fontSize='2.6rem'; b.dataset.id=o.id;
    b.onclick=()=>pick(it,o,b,row,c); row.append(b);
  });
  c.append(hear,row); push(c);
  if('speechSynthesis' in window) speechSynthesis.cancel(); speak(it.en);
}

async function pick(it,o,b,row,c){
  row.querySelectorAll('button').forEach(x=>x.disabled=true);
  try{
    const r=await fetch('/answer',{method:'POST',headers:H,body:JSON.stringify({level:unit.level,id:it.id,choice:o.id})});
    if(!r.ok) throw 0; const j=await r.json();
    row.querySelectorAll('button').forEach(x=>{ if(+x.dataset.id===it.id) x.classList.replace('btn-light','btn-success'); });
    if(!j.correct) b.classList.replace('btn-light','btn-danger');
    c.append(el('div','fs-5 mt-2',j.feedback));
    const bn=j.correct ? PRAISE_BN[Math.floor(Math.random()*PRAISE_BN.length)] : TRY_BN;
    if(bnOn()) c.append(el('div','bnl',bn));
    say(j.feedback); if(!j.correct){ speak(it.en); } if(bnOn()) speak(bn,'bn-BD',.9);
    if(j.correct) right++;
    level=j.level; xp=j.xp; render();
    if(j.leveled_up) add('🎉 Level '+j.level+'! 🎉','bot','অভিনন্দন! তুমি লেভেল '+j.level+'-এ পৌঁছে গেছো!');
    qi++;
    const n=el('button','btn btn-warning btn-lg mb-2', qi<order.length ? '➡️' : '⭐ '+right+' / '+order.length);
    n.onclick=()=>{ n.remove(); if(qi<order.length) quiz(); else finishUnit(); };
    push(n);
  }catch(e){ add('Oops! Let us try again 😊','bot'); row.querySelectorAll('button').forEach(x=>x.disabled=false); }
}

function finishUnit(){
  add('You tried so hard! 💪 '+right+' / '+order.length+' ⭐','bot','তুমি অনেক চেষ্টা করেছো!');
  const n=el('button','btn btn-success btn-lg mb-2','▶️ More!'); n.onclick=()=>{ n.remove(); send('Teach me!',true); }; push(n);
}

async function send(text,silent,label){
  if(!text.trim()) return;
  if(!silent) add(label||text,'me'); msg.value='';
  const typing=add('Buddy is thinking... 🤔','bot');
  try{
    const r=await fetch('/chat',{method:'POST',headers:H,body:JSON.stringify({message:text})});
    const j=await r.json(); typing.remove();
    if(!r.ok) throw 0;
    level=j.level; xp=j.xp; render();
    document.getElementById('mode').textContent='mode: '+j.mode;
    document.getElementById('ms').textContent=j.ms+' ms ('+j.source+')';
    add(j.reply,'bot',j.bn);
    if(j.lesson) runUnit(j.lesson); else { say(j.reply); if(j.bn && bnOn()) speak(j.bn,'bn-BD',.9); }
  }catch(e){ typing.remove(); add('Oops! Let us try again 😊','bot'); }
}

function listen(){
  const SR=window.SpeechRecognition||window.webkitSpeechRecognition;
  if(!SR){ alert('Voice works in Chrome or Edge'); return; }
  const rec=new SR(); rec.lang='en-US';
  rec.onresult=e=>send(e.results[0][0].transcript);
  rec.start();
}
document.getElementById('send').onclick=()=>send(msg.value);
msg.addEventListener('keydown',e=>{ if(e.key==='Enter') send(msg.value); });
</script>
@endif
</div>
</body>
</html>