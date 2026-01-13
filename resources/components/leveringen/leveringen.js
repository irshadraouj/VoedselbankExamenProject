const STORAGE_KEY = 'leveringen';

const monthNames = [
  'Januari',
  'Februari',
  'Maart',
  'April',
  'Mei',
  'Juni',
  'Juli',
  'Augustus',
  'September',
  'Oktober',
  'November',
  'December',
];

const getIsoDate = (year, month, day) => {
  const date = new Date(year, month, day);
  return date.toISOString().slice(0, 10);
};

const currentMonthLabel = () => {
  const now = new Date();
  return `${monthNames[now.getMonth()]} ${now.getFullYear()}`;
};

const isInCurrentMonth = (value) => {
  if (!value) return false;
  const now = new Date();
  const [year, month] = value.split('-').map(Number);
  if (!year || !month) return false;
  return year === now.getFullYear() && month === now.getMonth() + 1;
};

const formatDate = (value) => {
  if (!value) return '-';
  const parts = value.split('-');
  if (parts.length !== 3) return value;
  const [year, month, day] = parts;
  return `${day}-${month}-${year}`;
};

const getStoredDeliveries = () => {
  const raw = localStorage.getItem(STORAGE_KEY);
  if (!raw) return null;

  try {
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : null;
  } catch (error) {
    return null;
  }
};

const saveDeliveries = (items) => {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
};

const buildSeedData = () => {
  const now = new Date();
  const year = now.getFullYear();
  const month = now.getMonth();

  return [
    {
      id: 'seed-1',
      datum: getIsoDate(year, month, 4),
      leverancier: 'Bakkerij van Dam',
      items: [
        { product: 'Brood volkoren', aantal: 120 },
        { product: 'Krentenbollen', aantal: 80 },
      ],
    },
    {
      id: 'seed-2',
      datum: getIsoDate(year, month, 11),
      leverancier: 'Groente & Fruit Centrale',
      items: [
        { product: 'Appels (1kg)', aantal: 70 },
        { product: 'Bananen (1kg)', aantal: 65 },
      ],
    },
    {
      id: 'seed-3',
      datum: getIsoDate(year, month, 19),
      leverancier: 'Zuivel Cooperatie',
      items: [
        { product: 'Melk (1L)', aantal: 150 },
        { product: 'Yoghurt (500g)', aantal: 90 },
      ],
    },
  ];
};

const root = document.querySelector('.leveringen');
if (root && root.dataset.serverRendered !== 'true') {
  const tbody = root.querySelector('#leveringen-table-body');
  const modal = root.querySelector('#leveringen-modal');
  const form = root.querySelector('#leveringen-form');
  const modalTitle = root.querySelector('#leveringen-modal-title');
  const monthLabel = root.querySelector('#leveringen-month-label');
  const itemsList = root.querySelector('#leveringen-items-list');
  const saveButton = root.querySelector('#leveringen-save');
  const inputs = {
    datum: root.querySelector('#leveringen-datum'),
    leverancier: root.querySelector('#leveringen-leverancier'),
  };

  let leveringen = getStoredDeliveries();
  if (!leveringen) {
    leveringen = buildSeedData();
    saveDeliveries(leveringen);
  }

  if (monthLabel) {
    monthLabel.textContent = currentMonthLabel();
  }

  let activeId = null;
  let viewMode = false;

  const setFormDisabled = (disabled) => {
    Object.values(inputs).forEach((input) => {
      input.disabled = disabled;
    });
    itemsList.querySelectorAll('input, button').forEach((el) => {
      el.disabled = disabled;
    });
    saveButton.style.display = disabled ? 'none' : 'inline-block';
  };

  const addItemRow = (item = { product: '', aantal: '' }) => {
    const row = document.createElement('div');
    row.className = 'leveringen__item-row';
    row.innerHTML = `
      <input type="text" name="product" placeholder="Product" value="${item.product || ''}" required />
      <input type="number" name="aantal" min="1" placeholder="Aantal" value="${item.aantal || ''}" required />
      <button class="leveringen__item-remove" type="button" data-action="remove-item">Verwijderen</button>
    `;
    itemsList.appendChild(row);
  };

  const resetItems = (items = []) => {
    itemsList.innerHTML = '';
    if (!items.length) {
      addItemRow();
      return;
    }
    items.forEach(addItemRow);
  };

  const openModal = (title, delivery = null, isView = false) => {
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    modalTitle.textContent = title;
    viewMode = isView;

    if (delivery) {
      inputs.datum.value = delivery.datum;
      inputs.leverancier.value = delivery.leverancier;
      resetItems(delivery.items || []);
    } else {
      form.reset();
      resetItems();
    }

    setFormDisabled(isView);
  };

  const closeModal = () => {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    activeId = null;
    viewMode = false;
  };

  const renderTable = () => {
    if (!tbody) return;

    const currentMonthRows = leveringen.filter((item) => isInCurrentMonth(item.datum));

    if (!currentMonthRows.length) {
      tbody.innerHTML = '<tr><td class="leveringen__empty" colspan="5">Geen leveringen deze maand.</td></tr>';
      return;
    }

    tbody.innerHTML = currentMonthRows
      .map((item) => {
        const total = (item.items || []).reduce((sum, row) => sum + Number(row.aantal || 0), 0);
        const itemsHtml = (item.items || [])
          .map((row) => `${row.product} (${row.aantal})`)
          .join('<br>');

        return `
          <tr data-id="${item.id}">
            <td>${formatDate(item.datum)}</td>
            <td>${item.leverancier}</td>
            <td><div class="leveringen__items-listing">${itemsHtml}</div></td>
            <td>${total}</td>
            <td>
              <div class="leveringen__actions">
                <button class="leveringen__action-btn" type="button" data-action="view">Bekijken</button>
                <button class="leveringen__action-btn" type="button" data-action="edit">Wijzigen</button>
                <button class="leveringen__action-btn" type="button" data-action="delete">Verwijderen</button>
              </div>
            </td>
          </tr>
        `;
      })
      .join('');
  };

  const getItemById = (id) => leveringen.find((item) => item.id === id);

  renderTable();
  resetItems();

  root.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;

    const action = target.dataset.action;
    if (!action) return;

    if (action === 'add') {
      activeId = null;
      openModal('Nieuwe levering');
      return;
    }

    if (action === 'add-item') {
      addItemRow();
      return;
    }

    if (action === 'remove-item') {
      const row = target.closest('.leveringen__item-row');
      if (row && itemsList.children.length > 1) {
        row.remove();
      }
      return;
    }

    if (action === 'close') {
      closeModal();
      return;
    }

    const row = target.closest('tr');
    if (!row) return;
    const id = row.dataset.id;
    const delivery = getItemById(id);
    if (!delivery) return;

    activeId = id;
    if (action === 'view') {
      openModal('Levering bekijken', delivery, true);
    }
    if (action === 'edit') {
      openModal('Levering wijzigen', delivery, false);
    }
    if (action === 'delete') {
      const confirmed = window.confirm('Weet je zeker dat je deze levering wilt verwijderen?');
      if (!confirmed) return;
      leveringen = leveringen.filter((item) => item.id !== id);
      saveDeliveries(leveringen);
      renderTable();
    }
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    if (viewMode) return;

    const items = Array.from(itemsList.querySelectorAll('.leveringen__item-row'))
      .map((row) => {
        const inputsRow = row.querySelectorAll('input');
        return {
          product: inputsRow[0].value.trim(),
          aantal: Number(inputsRow[1].value),
        };
      })
      .filter((row) => row.product && row.aantal > 0);

    const payload = {
      id: activeId || `lev-${Date.now()}`,
      datum: inputs.datum.value,
      leverancier: inputs.leverancier.value.trim(),
      items,
    };

    if (!payload.datum || !payload.leverancier || payload.items.length === 0) {
      return;
    }

    if (activeId) {
      leveringen = leveringen.map((item) => (item.id === activeId ? payload : item));
    } else {
      leveringen.push(payload);
    }

    saveDeliveries(leveringen);
    renderTable();
    closeModal();
  });

  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      closeModal();
    }
  });
}
