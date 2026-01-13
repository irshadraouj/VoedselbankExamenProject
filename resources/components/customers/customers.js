(() => {
  const init = () => {
    const root = document.querySelector('.customers');
    if (!root) return;

    // ================================================
    // TAB SWITCHING
    // ================================================
    // switching between list and add tabs
    // Updates active button state and shows/hides corresponding content sections
    
    const tabButtons = root.querySelectorAll('[data-tab]');
    const tabPanels = root.querySelectorAll('.customers__section');

    const switchTab = (tabName) => {
      // Update button active state
      tabButtons.forEach((btn) => {
        btn.classList.toggle('customers__tab--active', btn.dataset.tab === tabName);
      });

      // Show/hide corresponding panel
      tabPanels.forEach((panel) => {
        const panelTabName = panel.id.replace('tab-', '');
        panel.style.display = panelTabName === tabName ? '' : 'none';
      });
    };

    // Add click handlers to tab buttons
    tabButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        switchTab(btn.dataset.tab);
      });
    });

    // Allow deep-linking to a specific tab via URL query (?tab=list|add|deliveries)
    const initialTab = new URLSearchParams(window.location.search).get('tab');
    if (initialTab && root.querySelector(`[data-tab="${initialTab}"]`)) {
      switchTab(initialTab);
    }

    // ================================================
    // FILTER - Customers list
    // ================================================
    // Filter customers by active/inactive/all status and by search term
    
    const filterButtons = root.querySelectorAll('[data-filter]');
    const tableRows = root.querySelectorAll('.customers__row');
    const searchInput = root.querySelector('#customers-search');

    // Get summary elements
    const totalElement = root.querySelector('.customers__summary-card:nth-child(1) .customers__summary-value');
    const activeElement = root.querySelector('.customers__summary-card:nth-child(2) .customers__summary-value');
    const inactiveElement = root.querySelector('.customers__summary-card:nth-child(3) .customers__summary-value');

    let currentFilter = 'all';
    let currentSearchTerm = '';

    const updateCounts = () => {
      let total = 0;
      let active = 0;
      let inactive = 0;

      tableRows.forEach((row) => {
        // Telt alle rijen
        const accountBan = row.dataset.accountBan;
        const isBanned = accountBan === 'yes';
        
        total++;
        if (isBanned) {
          inactive++;
        } else {
          active++;
        }
      });

      // Update kaarten
      if (totalElement) totalElement.textContent = total;
      if (activeElement) activeElement.textContent = active;
      if (inactiveElement) inactiveElement.textContent = inactive;
    };

    const applyFilter = (filterType, searchTerm = currentSearchTerm) => {
      currentFilter = filterType;
      
      // Update active filter button
      filterButtons.forEach((btn) => {
        btn.classList.toggle('customers__filter-btn--active', btn.dataset.filter === filterType);
      });

      // Filter table rows based on both status AND search term
      tableRows.forEach((row) => {
        const accountBan = row.dataset.accountBan;
        const isBanned = accountBan === 'yes';
        
        // Check status filter
        let statusMatch = false;
        if (filterType === 'all') {
          statusMatch = true;
        } else if (filterType === 'active') {
          statusMatch = !isBanned;
        } else if (filterType === 'inactive') {
          statusMatch = isBanned;
        }

        // Check search filter
        let searchMatch = true;
        if (searchTerm.trim()) {
          const searchLower = searchTerm.toLowerCase();
          const nameCell = row.querySelector('td:nth-child(1)');
          const emailCell = row.querySelector('td:nth-child(2)');
          
          const name = nameCell ? nameCell.textContent.toLowerCase() : '';
          const email = emailCell ? emailCell.textContent.toLowerCase() : '';
          
          searchMatch = name.includes(searchLower) || email.includes(searchLower);
        }

        // Show row only if both filters match
        row.style.display = (statusMatch && searchMatch) ? '' : 'none';
      });

      // Update counts
      updateCounts();
    };

    // Add click handlers to filter buttons
    filterButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        applyFilter(btn.dataset.filter, currentSearchTerm);
      });
    });

    // Add search input handler for live filtering
    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        currentSearchTerm = e.target.value;
        applyFilter(currentFilter, currentSearchTerm);
      });
    }

    // Initialize counts on page load
    updateCounts();

    // default filter to 'all'
    applyFilter('all');






    // ================================================
    // ADD FORM - Generate unique identifiers
    // ================================================
    // When adding a new customer, automatically generate:
    // - title: Customer name (for EE7 entry title)
    // - url_title: URL-friendly slug with timestamp (ensures uniqueness)
    
    const addForm = root.querySelector('#customers-add-form');
    if (addForm) {
      const nameInput = addForm.querySelector('#add-name');
      const titleInput = addForm.querySelector('#form-title');
      const urlTitleInput = addForm.querySelector('#form-url-title');

      nameInput?.addEventListener('input', (e) => {
        const name = e.target.value.trim();
        
        // Set title to the customer name
        titleInput.value = name;
        
        // Generate unique url_title: convert name to slug + add timestamp
        // This ensures the url_title is always unique (preventing duplicates)
        const timestamp = Date.now();
        const slug = name
          .toLowerCase()
          .replace(/\s+/g, '-')           // Replace spaces with hyphens
          .replace(/[^\w-]/g, '');        // Remove special characters
        
        urlTitleInput.value = `${slug}-${timestamp}`;
      });
    }

    // ================================================
    // MODAL - CMS Editor in iframe
    // ================================================
    // Opens the EE7 CMS editor in an iframe modal within the same page
    
    const modal = root.querySelector('#cms-editor-modal');
    const modalOverlay = root.querySelector('.customers__modal-overlay');
    const modalClose = root.querySelector('#cms-editor-close');
    const cmsIframe = root.querySelector('#cms-editor-iframe');
    const cmsTitle = root.querySelector('#cms-editor-title');

    // Open modal
    const openEditorModal = (entryId, entryName) => {
      if (!modal || !cmsIframe) return;
      
      cmsIframe.src = `/cms.php?/cp/publish/edit/entry/${entryId}`;
      if (cmsTitle) cmsTitle.textContent = `Bewerken: ${entryName}`;
      
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';

      // Hide sidebar after iframe loads
      cmsIframe.onload = () => {
        try {
          const iframeDoc = cmsIframe.contentDocument || cmsIframe.contentWindow.document;
          if (!iframeDoc) return;

          // Hide the sidebar
          const sidebar = iframeDoc.querySelector('.ee-sidebar');
          if (sidebar) {
            sidebar.style.display = 'none';
          }

          // Hide the main header
          const mainHeader = iframeDoc.querySelector('.ee-main-header.entries');
          if (mainHeader) {
            mainHeader.style.display = 'none';
          }

          // Make main content full width
          const mainContent = iframeDoc.querySelector('.ee-main');
          if (mainContent) {
            mainContent.style.marginLeft = '0';
            mainContent.style.width = '100%';
          }
        } catch (e) {
          console.log('Could not hide sidebar:', e);
        }
      };
    };

    // Close modal
    const closeEditorModal = () => {
      if (!modal) return;
      modal.style.display = 'none';
      cmsIframe.src = '';
      document.body.style.overflow = '';
      
      // Refresh the page to update customer data
      location.reload();
    };

    // Event listeners
    modalClose?.addEventListener('click', closeEditorModal);
    modalOverlay?.addEventListener('click', closeEditorModal);
    
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal && modal.style.display === 'flex') {
        closeEditorModal();
      }
    });

    // Edit modal button
    const editModalButtons = root.querySelectorAll('[data-action="edit-modal"]');
    editModalButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const entryId = btn.dataset.entryId;
        const entryName = btn.dataset.entryName;
        if (entryId) openEditorModal(entryId, entryName);
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
