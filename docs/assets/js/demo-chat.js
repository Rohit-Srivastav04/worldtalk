(() => {
  const feed = document.getElementById('message-feed');
  const nameInput = document.getElementById('name-input');
  const msgInput = document.getElementById('message-input');
  const onlineEl = document.getElementById('online-count');
  const KEY = 'worldtalk_demo_feed_v1';
  const NAME_KEY = 'worldtalk_demo_name';
  const channel = 'worldtalk_demo_room';
  let bc = null;
  try { bc = new BroadcastChannel(channel); } catch {}

  nameInput.value = localStorage.getItem(NAME_KEY) || ('Guest' + Math.floor(1000 + Math.random() * 9000));

  function esc(s) {
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  function load() {
    try { return JSON.parse(localStorage.getItem(KEY) || '[]'); } catch { return []; }
  }
  function save(list) {
    localStorage.setItem(KEY, JSON.stringify(list.slice(-200)));
  }

  function renderAll() {
    const list = load();
    feed.innerHTML = list.map(m => `
      <article class="msg">
        <div><span class="who">${esc(m.name)}</span><span class="when">${esc(m.at)}</span></div>
        <div class="body">${esc(m.text)}</div>
      </article>`).join('');
    feed.scrollTop = feed.scrollHeight;
    onlineEl.textContent = Math.max(1, Number(sessionStorage.getItem('wt_tabs') || 1)) + ' online';
  }

  function push(msg, broadcast = true) {
    const list = load();
    list.push(msg);
    save(list);
    renderAll();
    if (broadcast && bc) bc.postMessage({ type: 'msg', msg });
  }

  document.getElementById('composer').addEventListener('submit', (e) => {
    e.preventDefault();
    const name = (nameInput.value || 'Guest').trim().slice(0, 24);
    const text = (msgInput.value || '').trim();
    if (!text) return;
    localStorage.setItem(NAME_KEY, name);
    msgInput.value = '';
    push({
      id: crypto.randomUUID ? crypto.randomUUID() : String(Date.now()),
      name,
      text,
      at: new Date().toISOString().replace('T', ' ').slice(0, 19)
    });
  });

  document.getElementById('btn-theme').onclick = () => {
    const next = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', next);
  };

  if (bc) {
    bc.onmessage = (ev) => {
      if (ev.data?.type === 'msg') {
        const list = load();
        if (!list.find(m => m.id === ev.data.msg.id)) {
          list.push(ev.data.msg);
          save(list);
          renderAll();
        }
      }
      if (ev.data?.type === 'hello') renderAll();
    };
    bc.postMessage({ type: 'hello' });
  }

  sessionStorage.setItem('wt_tabs', String((Number(sessionStorage.getItem('wt_tabs') || 0) || 0) + 1));
  if (!load().length) {
    push({
      id: 'sys1',
      name: 'WorldTalk',
      text: 'Welcome to the public demo room. Open this page in another tab to see live sync.',
      at: new Date().toISOString().replace('T', ' ').slice(0, 19)
    }, false);
  } else {
    renderAll();
  }
})();
