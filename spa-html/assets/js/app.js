document.addEventListener('DOMContentLoaded', () => {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];

  // ДЕМО: переключение гость / авторизован (?guest=1). В SPA это решает наличие токена (v-if / условный рендер).
  if (new URLSearchParams(location.search).has('guest') && !document.body.classList.contains('is-guest')) {
    document.body.classList.replace('is-auth', 'is-guest');
  }

  // ДЕМО: состояние ошибок валидации (?errors=1). В SPA ошибки приходят с сервера.
  if (new URLSearchParams(location.search).get('errors')) {
    $$('[data-demo-error]').forEach(el => {
      const f = el.closest('.field');
      f.classList.add('has-error');
      f.insertAdjacentHTML('beforeend', `<p class="error-text">${el.dataset.demoError}</p>`);
    });
    $('.form-alert[data-demo]')?.removeAttribute('hidden');
  }

  // Превью изображения в форме рецепта
  const file = $('#image'), preview = $('#image-preview');
  if (file && preview) file.addEventListener('change', () => {
    if (file.files[0]) { preview.src = URL.createObjectURL(file.files[0]); preview.hidden = false; }
  });

  // Сортировка списка (демо на data-атрибутах карточек)
  const sortBy = $('#sort-by'), sortDir = $('#sort-dir'), list = $('#recipes');
  if (sortBy && sortDir && list) {
    const apply = () => {
      const key = sortBy.value, dir = sortDir.dataset.dir === 'asc' ? 1 : -1;
      $$('.recipe-card', list).sort((a, b) => (a.dataset[key] - b.dataset[key]) * dir).forEach(c => list.append(c));
    };
    sortBy.addEventListener('change', apply);
    sortDir.addEventListener('click', () => {
      const asc = sortDir.dataset.dir !== 'asc';
      sortDir.dataset.dir = asc ? 'asc' : 'desc';
      sortDir.textContent = asc ? '↑ По возрастанию' : '↓ По убыванию';
      apply();
    });
  }

  // Уведомление WebSocket (демо). В SPA: ws.onmessage → showToast(recipe)
  const toasts = $('#toasts');
  window.showToast = (title, href = 'recipe.html') => {
    if (!toasts) return;
    const el = document.createElement('div');
    el.className = 'toast';
    el.innerHTML = `<span>🔔</span><div><strong>Новый рецепт</strong><a href="${href}">${title}</a></div><button class="close" aria-label="Закрыть">×</button>`;
    el.querySelector('.close').onclick = () => el.remove();
    toasts.append(el);
    setTimeout(() => el.remove(), 8000);
  };
  if (toasts && toasts.dataset.demo !== undefined) setTimeout(() => showToast('Сырники со сметаной'), 3000);

  // Модальное окно удаления
  const modal = $('#delete-modal');
  if (modal) {
    $$('[data-delete]').forEach(btn => btn.addEventListener('click', () => {
      $('[data-modal-title]', modal).textContent = btn.dataset.delete;
      modal.hidden = false;
    }));
    $$('[data-close]', modal).forEach(b => b.addEventListener('click', () => modal.hidden = true));
    modal.addEventListener('click', e => e.target === modal && (modal.hidden = true));
    document.addEventListener('keydown', e => e.key === 'Escape' && (modal.hidden = true));
  }

  // Конструктор: перетаскивание ингредиентов в область
  const zone = $('#dropzone');
  if (zone) {
    const selected = new Set();
    const render = () => {
      $$('.ing', zone).forEach(n => n.remove());
      $('.placeholder', zone).hidden = selected.size > 0;
      selected.forEach(id => {
        const src = $(`#pool .ing[data-id="${id}"]`);
        zone.insertAdjacentHTML('beforeend', `<span class="ing" data-id="${id}">${src.dataset.name}<span class="x" data-x="${id}">×</span></span>`);
      });
      $$('#pool .ing').forEach(p => p.classList.toggle('used', selected.has(p.dataset.id)));
      // ДЕМО-фильтр результатов: показываем рецепты, где есть ВСЕ выбранные ингредиенты
      let shown = 0;
      $$('#results .recipe-card').forEach(c => {
        const have = c.dataset.ingredients.split(',');
        const ok = selected.size > 0 && [...selected].every(i => have.includes(i));
        c.hidden = !ok; if (ok) shown++;
      });
      $('#results-count').textContent = selected.size ? `Найдено рецептов: ${shown}` : 'Добавьте ингредиенты, чтобы увидеть рецепты';
      $('#results-empty').hidden = !(selected.size && !shown);
    };
    $$('#pool .ing').forEach(p => {
      p.draggable = true;
      p.addEventListener('dragstart', e => { e.dataTransfer.setData('text/plain', p.dataset.id); p.classList.add('dragging'); });
      p.addEventListener('dragend', () => p.classList.remove('dragging'));
      p.addEventListener('dblclick', () => { selected.add(p.dataset.id); render(); }); // запасной вариант для тач-экранов
    });
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', e => {
      e.preventDefault(); zone.classList.remove('dragover');
      selected.add(e.dataTransfer.getData('text/plain')); render();
    });
    zone.addEventListener('click', e => { const x = e.target.closest('[data-x]'); if (x) { selected.delete(x.dataset.x); render(); } });
    render();
  }
});
