  (() => {
    const root = document.querySelector('.pakket');
    if (!root) return;

    // ===== ELEMENTS =====
    const klantSelect = document.getElementById('pakket-klant-select');
    const klantSearch = document.getElementById('pakket-klant-search');
    const klantInfo = document.getElementById('pakket-klant-info');
    const composeSection = document.getElementById('pakket-compose-section');
    
    const products = Array.from(root.querySelectorAll('.pakket__product'));
    const listEl = document.getElementById('pakket-list');
    const emptyEl = document.getElementById('pakket-empty');
    const totalEl = document.getElementById('pakket-total');
    const summaryEl = document.getElementById('pakket-summary');
    const saveBtn = document.getElementById('pakket-save');
    const clearBtn = document.getElementById('pakket-clear');
    const messageEl = document.getElementById('pakket-message');

    const selections = new Map();
    let selectedCustomerId = null;

    // ===== KLANT ZOEKEN =====
    if (klantSearch) {
      klantSearch.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const options = klantSelect.querySelectorAll('option');

        options.forEach((option) => {
          if (option.value === '') return; // Skip placeholder

          const name = option.dataset.name.toLowerCase();
          const matches = name.includes(searchTerm);
          
          option.style.display = matches || searchTerm === '' ? '' : 'none';
        });

        // Filter visible options
        const visibleOptions = Array.from(options).filter(opt => opt.style.display !== 'none' && opt.value !== '');
        if (visibleOptions.length > 1) {
          // Auto-select eerste resultaat als er maar 1 is
          if (visibleOptions.length === 1) {
            klantSelect.value = visibleOptions[0].value;
            klantSelect.dispatchEvent(new Event('change'));
          }
        }
      });
    }

    // Hide archived customers from dropdown on page load
    const klantOptions = klantSelect.querySelectorAll('option');
    klantOptions.forEach((option) => {
      if (option.value === '') return;
      // Check if this option has any data attributes - if not, it might be archived
      if (!option.dataset.name) {
        option.style.display = 'none';
      }
    });

    // ===== KLANT SELECTIE =====
    klantSelect.addEventListener('change', (e) => {
      const option = e.target.selectedOptions[0];
      selectedCustomerId = option.value;

      if (!selectedCustomerId) {
        klantInfo.style.display = 'none';
        composeSection.style.display = 'none';
        selections.clear();
        updateOverview();
        return;
      }

      // Toon klantgegevens
      document.getElementById('pakket-klant-naam').textContent = option.dataset.name || '-';
      document.getElementById('pakket-klant-email').textContent = option.dataset.email || '-';
      document.getElementById('pakket-klant-phone').textContent = option.dataset.phone || '-';
      document.getElementById('pakket-klant-adult').textContent = option.dataset.adult || '0';
      document.getElementById('pakket-klant-child').textContent = option.dataset.child || '0';
      document.getElementById('pakket-klant-baby').textContent = option.dataset.baby || '0';

      klantInfo.style.display = 'block';
      composeSection.style.display = 'block';
      
      // Wis vorige selectie
      selections.clear();
      updateOverview();
    });

    // ===== PRODUCT SELECTIE =====
    const getKey = (card) => card.getAttribute('data-ean') || card.getAttribute('data-name') || '';

    const updateOverview = () => {
      if (!listEl || !emptyEl || !totalEl) return;

      listEl.innerHTML = '';
      let total = 0;

      products.forEach((card) => {
        const key = getKey(card);
        const qty = selections.get(key) || 0;
        
        if (!qty) {
          card.classList.remove('pakket__product--selected');
          return;
        }

        total += qty;
        card.classList.add('pakket__product--selected');

        const name = card.getAttribute('data-name') || 'Product';
        const li = document.createElement('li');
        li.className = 'pakket__overview-item';
        li.textContent = `${name} (${qty})`;
        listEl.appendChild(li);
      });

      emptyEl.hidden = total > 0;
      if (summaryEl) summaryEl.style.display = total > 0 ? 'block' : 'none';
      totalEl.textContent = total;
    };

    products.forEach((card) => {
      const addButton = card.querySelector('.pakket__product-add');
      const removeButton = card.querySelector('.pakket__product-remove');

      if (addButton) {
        addButton.addEventListener('click', () => {
          const key = getKey(card);
          if (!key) return;

          const stock = parseInt(card.getAttribute('data-stock'), 10) || 0;
          const current = selections.get(key) || 0;

          if (current + 1 > stock) {
            if (messageEl) messageEl.textContent = 'Niet genoeg voorraad beschikbaar.';
            return;
          }

          selections.set(key, current + 1);
          if (messageEl) messageEl.textContent = '';
          updateOverview();
        });
      }

      if (removeButton) {
        removeButton.addEventListener('click', () => {
          const key = getKey(card);
          if (!key) return;

          const current = selections.get(key) || 0;
          if (current <= 1) {
            selections.delete(key);
          } else {
            selections.set(key, current - 1);
          }
          updateOverview();
        });
      }
    });

    // ===== PAKKET OPSLAAN =====
    const expirationPicker = document.getElementById('pakket-expiration-picker');

    if (saveBtn) {
      saveBtn.addEventListener('click', () => {
        if (!selectedCustomerId) {
          if (messageEl) messageEl.textContent = 'Selecteer eerst een klant.';
          return;
        }

        const items = products
          .map((card) => {
            const key = getKey(card);
            return {
              name: card.getAttribute('data-name') || '',
              qty: selections.get(key) || 0,
              entryId: card.getAttribute('data-entry-id') || '',
            };
          })
          .filter((item) => item.qty > 0);

        if (items.length === 0) {
          if (messageEl) messageEl.textContent = 'Voeg eerst producten toe aan het pakket.';
          return;
        }

        // Controleer uitgiftedatum (moet vrijdag zijn)
        if (!expirationPicker || !expirationPicker.value) {
          if (messageEl) messageEl.textContent = 'Kies een uitgiftedatum (alleen vrijdag).';
          return;
        }

        const expValue = expirationPicker.value; // YYYY-MM-DD
        const expDate = new Date(expValue + 'T00:00:00');
        const day = expDate.getDay(); // 0 = zondag, 5 = vrijdag
        if (Number.isNaN(day) || day !== 5) {
          if (messageEl) messageEl.textContent = 'De uitgiftedatum moet op een vrijdag vallen.';
          return;
        }

        // Toon opslag feedback
        if (messageEl) {
          messageEl.textContent = 'Pakket wordt opgeslagen...';
        }

        // Vul Channel Form hidden velden en submit het formulier
        try {
          const customerOption = klantSelect.selectedOptions[0];
          const customerName = customerOption.dataset.name || '';
          const customerBsn = customerOption.dataset.bsn || '';
          document.getElementById('cf-order-name').value = customerName;
          document.getElementById('cf-order-bsn').value = customerBsn;
          document.getElementById('cf-order-items').value = JSON.stringify(items);
          // uitgiftedatum vanuit date-picker (reeds gevalideerd op vrijdag)
          document.getElementById('cf-expiration-date').value = expValue;

          // Set required title and url_title to satisfy Channel Form validation
          document.getElementById('cf-title').value = `${customerName} ${customerBsn}`.trim();
          const slugBase = customerName.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '') || 'order';
          document.getElementById('cf-url-title').value = `${slugBase}-${Date.now()}`;

          const form = document.getElementById('pakket-form');
          if (form) {
            form.submit();
          } else {
            throw new Error('Formulier niet gevonden');
          }
        } catch (error) {
          if (messageEl) messageEl.textContent = '✗ Fout bij opslaan.';
        }
      });
    }

    // ===== PAKKET WISSEN =====
    if (clearBtn) {
      clearBtn.addEventListener('click', () => {
        selections.clear();
        updateOverview();
        if (messageEl) messageEl.textContent = '';
        if (expirationPicker) expirationPicker.value = '';
      });
    }

    // ===== UITKLAPBARE PRODUCTLIJST IN OVERZICHT =====
    root.addEventListener('click', (event) => {
      const toggleBtn = event.target.closest('.pakket__saved-item-toggle');
      if (!toggleBtn) return;

      const container = toggleBtn.closest('.pakket__saved-item');
      if (!container) return;

      const extraItems = container.querySelectorAll('.pakket__saved-item-product--extra');
      const isHidden = extraItems.length && extraItems[0].style.display === 'none';

      extraItems.forEach((item) => {
        item.style.display = isHidden ? '' : 'none';
      });

      toggleBtn.textContent = isHidden ? 'Verberg extra producten' : 'Toon alle producten';
    });

    // Init
    updateOverview();
  })();