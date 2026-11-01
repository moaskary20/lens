(() => {
  const root = document.body;
  const raw = root?.dataset?.launchAt;
  if (!raw) {
    return;
  }

  const launchAt = Date.parse(raw);
  if (Number.isNaN(launchAt)) {
    return;
  }

  const nodes = {
    days: root.querySelector('[data-unit="days"]'),
    hours: root.querySelector('[data-unit="hours"]'),
    minutes: root.querySelector('[data-unit="minutes"]'),
    seconds: root.querySelector('[data-unit="seconds"]'),
  };

  const pad = (value) => String(Math.max(0, value)).padStart(2, '0');

  const tick = () => {
    const diff = Math.max(0, launchAt - Date.now());
    const totalSeconds = Math.floor(diff / 1000);
    const days = Math.floor(totalSeconds / 86400);
    const hours = Math.floor((totalSeconds % 86400) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    if (nodes.days) nodes.days.textContent = pad(days);
    if (nodes.hours) nodes.hours.textContent = pad(hours);
    if (nodes.minutes) nodes.minutes.textContent = pad(minutes);
    if (nodes.seconds) nodes.seconds.textContent = pad(seconds);

    if (diff === 0) {
      root.classList.add('is-launched');
    }
  };

  tick();
  window.setInterval(tick, 1000);
})();
