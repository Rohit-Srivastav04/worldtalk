(() => {
  const root = document.getElementById('chat-app');
  if (!root) return;

  const wsUrl = root.dataset.ws;
  const csrf = root.dataset.csrf;
  const me = JSON.parse(root.dataset.me || '{}');
  const feed = document.getElementById('message-feed');
  const input = document.getElementById('message-input');
  const statusEl = document.getElementById('conn-status');
  const onlineEl = document.getElementById('online-count');
  const typingEl = document.getElementById('typing-line');
  const presenceList = document.getElementById('presence-list');
  const soundOn = { value: localStorage.getItem('wt_sound') !== '0' };

  let ws = null;
  let oldestId = null;
  let stickToBottom = true;
  let typingTimer = null;
  const seen = new Set();
  const audio = (() => {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      return () => {
        if (!soundOn.value) return;
        const o = ctx.createOscillator();
        const g = ctx.createGain();
        o.connect(g); g.connect(ctx.destination);
        o.frequency.value = 880; g.gain.value = 0.03;
        o.start(); o.stop(ctx.currentTime + 0.08);
      };
    } catch { return () => {}; }
  })();

  function setStatus(text, ok) {
    statusEl.textContent = text;
    statusEl.className = 'status ' + (ok === true ? 'ok' : ok === false ? 'bad' : '');
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  function renderMessage(m) {
    if (!m || !m.id || seen.has(m.id)) return;
    seen.add(m.id);
    if (!oldestId || m.id < oldestId) oldestId = m.id;
    const el = document.createElement('article');
    el.className = 'msg';
    el.dataset.id = m.id;
    el.innerHTML = `
      <div><span class="who">${escapeHtml(m.sender?.username || 'User')}</span>
      <span class="when">${escapeHtml(m.created_at || '')}</span></div>
      <div class="body">${escapeHtml(m.content || '')}</div>
      <div class="ops">
        <button type="button" data-act="copy">Copy</button>
        <button type="button" data-act="report">Report</button>
      </div>`;
    el.querySelector('[data-act="copy"]').onclick = () => navigator.clipboard?.writeText(m.content || '');
    el.querySelector('[data-act="report"]').onclick = async () => {
      await fetch(`/api/v1/messages/${m.id}/report`, {
        method: 'POST', headers: {'Content-Type':'application/json','X-CSRF-TOKEN': csrf},
        body: JSON.stringify({reason: 'abuse', csrf})
      });
      alert('Reported. Thank you.');
    };
    feed.appendChild(el);
    if (stickToBottom) feed.scrollTop = feed.scrollHeight;
  }

  async function loadHistory(before) {
    const q = before ? `?before=${before}&limit=40` : '?limit=40';
    const res = await fetch('/api/v1/rooms/global/messages' + q).then(r => r.json());
    if (!res.ok) return;
    const prevHeight = feed.scrollHeight;
    const frag = document.createDocumentFragment();
    const temp = [];
    for (const m of res.messages) {
      if (seen.has(m.id)) continue;
      temp.push(m);
    }
    if (before) {
      feed.innerHTML = '';
      seen.clear();
      oldestId = null;
      [...temp, ...Array.from(feed.children)].forEach(() => {});
      // simpler: prepend by reloading all known is hard; just insert at top
    }
    if (before) {
      const holder = document.createElement('div');
      for (const m of temp) {
        if (seen.has(m.id)) continue;
        seen.add(m.id);
        if (!oldestId || m.id < oldestId) oldestId = m.id;
        const el = document.createElement('article');
        el.className = 'msg';
        el.innerHTML = `<div><span class="who">${escapeHtml(m.sender?.username || 'User')}</span><span class="when">${escapeHtml(m.created_at || '')}</span></div><div class="body">${escapeHtml(m.content || '')}</div>`;
        holder.appendChild(el);
      }
      feed.insertBefore(holder, feed.firstChild);
      while (holder.firstChild) feed.insertBefore(holder.firstChild, holder);
      holder.remove();
      feed.scrollTop = feed.scrollHeight - prevHeight;
    } else {
      temp.forEach(renderMessage);
      feed.scrollTop = feed.scrollHeight;
    }
  }

  async function connect() {
    setStatus('Connecting…');
    const ticketRes = await fetch('/api/v1/ws/ticket').then(r => r.json()).catch(() => null);
    if (!ticketRes?.ok) {
      setStatus('Auth required', false);
      return;
    }
    ws = new WebSocket(wsUrl);
    ws.onopen = () => {
      ws.send(JSON.stringify({event: 'auth', ticket: ticketRes.ticket}));
    };
    ws.onmessage = (ev) => {
      let data;
      try { data = JSON.parse(ev.data); } catch { return; }
      const event = data.event;
      if (event === 'auth_success' || event === 'connected') {
        setStatus('Live', true);
        ws.send(JSON.stringify({event: 'join_room', room: 'global'}));
      } else if (event === 'auth_error') {
        setStatus('Auth failed', false);
      } else if (event === 'new_message') {
        renderMessage(data.message);
        audio();
      } else if (event === 'online_count') {
        onlineEl.textContent = `${data.count} online`;
      } else if (event === 'presence_sync') {
        presenceList.innerHTML = (data.users || []).map(u => `<li>${escapeHtml(u.name)}</li>`).join('');
        onlineEl.textContent = `${(data.users || []).length} online`;
      } else if (event === 'typing_update') {
        typingEl.hidden = !(data.users || []).length;
        typingEl.textContent = (data.users || []).length ? `${data.users.join(', ')} typing…` : '';
      } else if (event === 'message_deleted') {
        const node = feed.querySelector(`[data-id="${data.id}"]`);
        if (node) node.remove();
      } else if (event === 'pong') {
        /* ok */
      }
    };
    ws.onclose = () => {
      setStatus('Reconnecting…', false);
      setTimeout(connect, 1500);
    };
    ws.onerror = () => setStatus('Socket error', false);
  }

  document.getElementById('composer').addEventListener('submit', async (e) => {
    e.preventDefault();
    const content = input.value.trim();
    if (!content) return;
    const uuid = crypto.randomUUID ? crypto.randomUUID() : String(Date.now());
    input.value = '';
    if (ws && ws.readyState === 1) {
      ws.send(JSON.stringify({event: 'send_message', content, uuid, room: 'global'}));
    } else {
      const res = await fetch('/api/v1/messages', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN': csrf},
        body: JSON.stringify({content, uuid, csrf})
      }).then(r => r.json());
      if (res.ok) renderMessage(res.message);
      else alert(res.error || 'Send failed');
    }
  });

  input.addEventListener('input', () => {
    if (!ws || ws.readyState !== 1) return;
    ws.send(JSON.stringify({event: 'typing_start', room: 'global'}));
    clearTimeout(typingTimer);
    typingTimer = setTimeout(() => ws.send(JSON.stringify({event: 'typing_stop', room: 'global'})), 1200);
  });

  feed.addEventListener('scroll', () => {
    stickToBottom = feed.scrollTop + feed.clientHeight >= feed.scrollHeight - 40;
  });

  document.getElementById('load-older').onclick = () => oldestId && loadHistory(oldestId);
  document.getElementById('btn-users').onclick = () => document.getElementById('presence-drawer').classList.toggle('open');
  document.getElementById('btn-theme').onclick = () => {
    const next = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('wt_theme', next);
  };
  document.getElementById('btn-sound').onclick = () => {
    soundOn.value = !soundOn.value;
    localStorage.setItem('wt_sound', soundOn.value ? '1' : '0');
  };
  const emojiPanel = document.getElementById('emoji-panel');
  document.getElementById('emoji-btn').onclick = () => { emojiPanel.hidden = !emojiPanel.hidden; };
  emojiPanel.addEventListener('click', (e) => {
    if (e.target === emojiPanel) {
      const pick = (emojiPanel.textContent || '').trim().split(/\s+/)[0];
    }
    const t = e.target;
    if (t.nodeType === 3) return;
  });
  emojiPanel.addEventListener('click', () => {});
  // click emoji characters
  emojiPanel.onclick = (e) => {
    const sel = window.getSelection();
    // simpler: split on spaces via event offset — use buttons instead rebuild
  };
  emojiPanel.innerHTML = ['😀','😂','❤️','🔥','👋','🌍','✨','🎉','🙏','💯','😎','🤝']
    .map(e => `<button type="button" class="icon-btn">${e}</button>`).join('');
  emojiPanel.querySelectorAll('button').forEach(btn => btn.onclick = () => {
    input.value += btn.textContent;
    input.focus();
  });

  setInterval(() => {
    if (ws && ws.readyState === 1) ws.send(JSON.stringify({event: 'ping'}));
  }, 25000);

  // REST polling fallback when WebSocket unavailable (e.g. Windows without Workerman)
  let lastPollId = 0;
  async function poll() {
    try {
      const res = await fetch('/api/v1/rooms/global/messages?limit=30').then(r => r.json());
      if (!res.ok) return;
      for (const m of res.messages) {
        if (m.id > lastPollId) {
          renderMessage(m);
          lastPollId = Math.max(lastPollId, m.id);
        }
      }
      const st = await fetch('/api/v1/stats').then(r => r.json());
      if (st.ok) onlineEl.textContent = `${st.stats.online ?? 0} online`;
    } catch {}
  }

  loadHistory().then(() => {
    connect();
    setInterval(poll, 2000);
  });
})();
