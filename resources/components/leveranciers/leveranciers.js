const STORAGE_KEY = 'leveranciers';

const seedData = [
  {
    bedrijf: 'Bakkerij van Dam',
    contact: 'Henk van Dam',
    telefoon: '0201234567',
    email: 'info@bakkerijvandam.nl',
    levering: '2026-01-15',
  },
  {
    bedrijf: 'Groente & Fruit Centrale',
    contact: 'Sandra de Groot',
    telefoon: '0207654321',
    email: 'leveringen@gfc.nl',
    levering: '2026-01-12',
  },
  {
    bedrijf: 'Zuivel Coöperatie',
    contact: 'Jan Smeets',
    telefoon: '0301112233',
    email: 'jan@zuivelcoop.nl',
    levering: '2026-01-18',
  },
  {
    bedrijf: 'Vleeswarenhandel Jansen',
    contact: 'Piet Jansen',
    telefoon: '0204445566',
    email: 'p.jansen@vleeshandel.nl',
    levering: '2026-01-20',
  },
];

const formatDate = (value) => {
  if (!value) return '-';
  const parts = value.split('-');
  if (parts.length !== 3) return value;
  const [year, month, day] = parts;
  return `${day}-${month}-${year}`;
};

const getStoredSuppliers = () => {
  const raw = localStorage.getItem(STORAGE_KEY);
  if (!raw) return null;

  try {
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : null;
  } catch (error) {
    return null;
  }
};

const saveSuppliers = (items) => {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
};

const root = document.querySelector('.leveranciers');
if (root) {
  const tbody = root.querySelector('#leveranciers-table-body');
  const modal = root.querySelector('#leveranciers-modal');
  const form = root.querySelector('#leveranciers-form');
  const modalTitle = root.querySelector('#leveranciers-modal-title');
  const saveButton = root.querySelector('#leveranciers-save');
  const inputs = {
    bedrijf: root.querySelector('#leverancier-bedrijf'),
    contact: root.querySelector('#leverancier-contact'),
    telefoon: root.querySelector('#leverancier-telefoon'),
    email: root.querySelector('#leverancier-email'),
    levering: root.querySelector('#leverancier-levering'),
  };

  let leveranciers = getStoredSuppliers();
  if (!leveranciers) {
    leveranciers = [...seedData];
    saveSuppliers(leveranciers);
  }

  let activeIndex = null;
  let viewMode = false;

  const setFormDisabled = (disabled) => {
    Object.values(inputs).forEach((input) => {
      input.disabled = disabled;
    });
    saveButton.style.display = disabled ? 'none' : 'inline-block';
  };

  const openModal = (title, supplier = null, isView = false) => {
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    modalTitle.textContent = title;
    viewMode = isView;
    setFormDisabled(isView);

    if (supplier) {
      inputs.bedrijf.value = supplier.bedrijf;
      inputs.contact.value = supplier.contact;
      inputs.telefoon.value = supplier.telefoon;
      inputs.email.value = supplier.email;
      inputs.levering.value = supplier.levering;
    } else {
      form.reset();
    }
  };

  const closeModal = () => {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    activeIndex = null;
    viewMode = false;
  };

  const renderTable = () => {
    if (!tbody) return;

    if (!leveranciers.length) {
      tbody.innerHTML = '<tr><td class="leveranciers__empty" colspan="6">Geen leveranciers gevonden.</td></tr>';
      return;
    }

    tbody.innerHTML = leveranciers
      .map((item, index) => `
        <tr data-index="${index}">
          <td>${item.bedrijf}</td>
          <td>${item.contact}</td>
          <td>${item.telefoon}</td>
          <td>${item.email}</td>
          <td>${formatDate(item.levering)}</td>
          <td>
            <div class="leveranciers__actions">
              <button class="leveranciers__action-btn" type="button" data-action="view">Bekijken</button>
              <button class="leveranciers__action-btn" type="button" data-action="edit">Wijzigen</button>
            </div>
          </td>
        </tr>
      `)
      .join('');
  };

  renderTable();

  root.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;

    const action = target.dataset.action;
    if (!action) return;

    if (action === 'add') {
      activeIndex = null;
      openModal('Nieuwe leverancier');
      return;
    }

    if (action === 'close') {
      closeModal();
      return;
    }

    const row = target.closest('tr');
    if (!row) return;

    const index = Number(row.dataset.index);
    if (Number.isNaN(index) || !leveranciers[index]) return;

    activeIndex = index;
    if (action === 'view') {
      openModal('Leverancier bekijken', leveranciers[index], true);
    }
    if (action === 'edit') {
      openModal('Leverancier wijzigen', leveranciers[index], false);
    }
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    if (viewMode) return;

    const payload = {
      bedrijf: inputs.bedrijf.value.trim(),
      contact: inputs.contact.value.trim(),
      telefoon: inputs.telefoon.value.trim(),
      email: inputs.email.value.trim(),
      levering: inputs.levering.value,
    };

    if (activeIndex === null) {
      leveranciers.push(payload);
    } else {
      leveranciers[activeIndex] = payload;
    }

    saveSuppliers(leveranciers);
    renderTable();
    closeModal();
  });

  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      closeModal();
    }
  });
}
