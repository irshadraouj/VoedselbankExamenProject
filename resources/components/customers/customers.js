// Customers component - Simple EE7 integration
import './customers.scss';

(() => {
  const init = () => {
    const root = document.querySelector('.customers');
    if (!root) return;

    // ============================================
    // TAB SWITCHING
    // ============================================
    const tabButtons = root.querySelectorAll('[data-tab]');
    const tabPanels = root.querySelectorAll('.customers__section');

    const switchTab = (tabName) => {
      // Update buttons
      tabButtons.forEach((btn) => {
        btn.classList.toggle('customers__tab--active', btn.dataset.tab === tabName);
      });

      // Update panels
      tabPanels.forEach((panel) => {
        const panelTabName = panel.id.replace('tab-', '');
        panel.style.display = panelTabName === tabName ? '' : 'none';
      });
    };

    tabButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        switchTab(btn.dataset.tab);
      });
    });

    // ============================================
    // ADD FORM - Auto-populate title/url_title
    // ============================================
    const addForm = root.querySelector('#customers-add-form');
    if (addForm) {
      const nameInput = addForm.querySelector('#add-name');
      const titleInput = addForm.querySelector('#form-title');
      const urlTitleInput = addForm.querySelector('#form-url-title');

      // Auto-populate title and url_title from name
      nameInput.addEventListener('input', (e) => {
        const name = e.target.value.trim();
        titleInput.value = name;
        // Make url_title unique by adding timestamp
        const timestamp = Date.now();
        urlTitleInput.value = name.toLowerCase().replace(/\s+/g, '-').replace(/[^\w-]/g, '') + '-' + timestamp;
      });
    }

    // ============================================
    // EDIT FORM - Load customer data when selected
    // ============================================
    const editSelect = root.querySelector('#edit-select');
    const editFormContainer = root.querySelector('#edit-form-container');
    const editForm = root.querySelector('#customers-edit-form');
    const editCancel = root.querySelector('#edit-cancel');

    if (editSelect) {
      editSelect.addEventListener('change', (e) => {
        const entryId = e.target.value;
        
        if (!entryId) {
          if (editFormContainer) editFormContainer.style.display = 'none';
          return;
        }

        // Show form container
        if (editFormContainer) editFormContainer.style.display = '';

        // Get customer data from selected option
        const option = e.target.options[e.target.selectedIndex];
        
        // Fill in the form fields
        if (editForm) {
          const entryIdField = editForm.querySelector('#edit-entry-id');
          if (entryIdField) entryIdField.value = option.dataset.entryId || '';
          
          const nameField = editForm.querySelector('#edit-name');
          if (nameField) nameField.value = option.dataset.name || '';
          
          const emailField = editForm.querySelector('#edit-email');
          if (emailField) emailField.value = option.dataset.email || '';
          
          const phoneField = editForm.querySelector('#edit-phone');
          if (phoneField) phoneField.value = option.dataset.phone || '';
          
          const streetField = editForm.querySelector('#edit-street');
          if (streetField) streetField.value = option.dataset.street || '';
          
          const zipField = editForm.querySelector('#edit-zip');
          if (zipField) zipField.value = option.dataset.zip || '';
          
          const regionField = editForm.querySelector('#edit-region');
          if (regionField) regionField.value = option.dataset.region || '';
          
          const adultField = editForm.querySelector('#edit-adults');
          if (adultField) adultField.value = option.dataset.adult || '0';
          
          const childField = editForm.querySelector('#edit-children');
          if (childField) childField.value = option.dataset.child || '0';
          
          const babyField = editForm.querySelector('#edit-babies');
          if (babyField) babyField.value = option.dataset.baby || '0';
          
          const bsnField = editForm.querySelector('#edit-bsn');
          if (bsnField) bsnField.value = option.dataset.bsn || '';
          
          // Also update title/url_title hidden fields
          const titleField = editForm.querySelector('#edit-form-title');
          if (titleField) titleField.value = option.dataset.name || '';
          
          const urlTitleField = editForm.querySelector('#edit-form-url-title');
          if (urlTitleField) urlTitleField.value = (option.dataset.name || '').toLowerCase().replace(/\s+/g, '-').replace(/[^\w-]/g, '');
        }
      });
    }

    // Update title/url_title when edit form name changes
    if (editForm) {
      const editNameField = editForm.querySelector('#edit-name');
      const editTitleField = editForm.querySelector('#edit-form-title');
      const editUrlTitleField = editForm.querySelector('#edit-form-url-title');
      
      if (editNameField && editTitleField && editUrlTitleField) {
        editNameField.addEventListener('input', (e) => {
          const name = e.target.value.trim();
          editTitleField.value = name;
          editUrlTitleField.value = name.toLowerCase().replace(/\s+/g, '-').replace(/[^\w-]/g, '');
        });
      }
    }

    // Cancel button - reset form
    if (editCancel) {
      editCancel.addEventListener('click', () => {
        if (editSelect) editSelect.value = '';
        if (editFormContainer) editFormContainer.style.display = 'none';
        if (editForm) editForm.reset();
      });
    }

    // ============================================
    // TABLE ACTIONS
    // ============================================
    const editButtons = root.querySelectorAll('[data-action="edit"]');
    editButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const entryId = btn.dataset.entryId;
        if (entryId && editSelect) {
          // Set select value to this entry
          editSelect.value = entryId;
          // Trigger change event
          editSelect.dispatchEvent(new Event('change', { bubbles: true }));
          // Switch to edit tab
          switchTab('edit');
        }
      });
    });
  };

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
