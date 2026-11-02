// Visual-only spotlight and explicit reveal for virtual demo card numbers.
document.querySelectorAll('.flashlight-card').forEach(card => {
  card.addEventListener('pointermove', event => {
    if (event.pointerType === 'touch') return;
    const rect = card.getBoundingClientRect();
    card.style.setProperty('--light-x', `${event.clientX - rect.left}px`);
    card.style.setProperty('--light-y', `${event.clientY - rect.top}px`);
  });
});

document.querySelectorAll('[data-card-details]').forEach(group => {
  const number = group.querySelector('[data-masked]');
  const reveal = group.querySelector('.card-reveal');
  const copy = group.querySelector('.card-copy');
  reveal.addEventListener('click', () => {
    const isOpen = reveal.getAttribute('aria-pressed') === 'true';
    number.textContent = isOpen ? number.dataset.masked : number.dataset.full;
    reveal.setAttribute('aria-pressed', String(!isOpen));
    reveal.textContent = isOpen ? 'Show details' : 'Hide details';
    reveal.setAttribute('aria-label', isOpen ? 'Show full card number' : 'Hide full card number');
    copy.hidden = isOpen;
  });
  copy.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(number.dataset.full);
      copy.textContent = 'Copied!';
      setTimeout(() => { copy.textContent = 'Copy number'; }, 1600);
    } catch (_) {
      copy.textContent = 'Copy unavailable';
    }
  });
});
