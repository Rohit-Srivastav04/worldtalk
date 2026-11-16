(() => {
  const saved = localStorage.getItem('wt_theme');
  if (saved) document.documentElement.setAttribute('data-theme', saved);
  fetch('/api/v1/stats').then(r => r.json()).then(d => {
    if (!d.ok) return;
    const el = document.getElementById('stat-online');
    if (el) el.textContent = String(d.stats.online ?? 0);
  }).catch(() => {});
})();
