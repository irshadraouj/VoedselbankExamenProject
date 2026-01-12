        (() => {
            const init = () => {
                const root = document.querySelector('.leveranciers');
                if (!root) return;

                // TAB SWITCHING
                const tabButtons = root.querySelectorAll('[data-tab]');
                const tabPanels = root.querySelectorAll('.leveranciers__panel');

                const switchTab = (tabName) => {
                    tabButtons.forEach((btn) => {
                        btn.classList.toggle('leveranciers__tab--active', btn.dataset.tab === tabName);
                    });

                    tabPanels.forEach((panel) => {
                        const panelTabName = panel.id.replace('tab-', '');
                        panel.style.display = panelTabName === tabName ? '' : 'none';
                    });
                };

                tabButtons.forEach((btn) => {
                    btn.addEventListener('click', () => switchTab(btn.dataset.tab));
                });

                // ADD FORM - auto-generate title and url_title
                const addForm = root.querySelector('#leveranciers-add-form');
                if (addForm) {
                    const nameInput = addForm.querySelector('#supplier-name');
                    const titleInput = addForm.querySelector('#form-title');
                    const urlTitleInput = addForm.querySelector('#form-url-title');

                    nameInput?.addEventListener('input', (e) => {
                        const name = e.target.value.trim();
                        if (titleInput) titleInput.value = name;
                        if (urlTitleInput) {
                            const timestamp = Date.now();
                            const slug = name.toLowerCase().replace(/\s+/g, '-').replace(/[^\w-]/g, '');
                            urlTitleInput.value = `${slug}-${timestamp}`;
                        }
                    });
                }

                // DELIVERY FORM - auto-generate title and url_title
                const deliveryForm = root.querySelector('#leveranciers-delivery-form');
                if (deliveryForm) {
                    const supplierSelect = deliveryForm.querySelector('#delivery-supplier');
                    
                    // Populate supplier dropdown from global data
                    if (supplierSelect && window.supplierData) {
                        window.supplierData.forEach((supplier) => {
                            if (supplier.id && supplier.name) {
                                const option = document.createElement('option');
                                option.value = supplier.id;
                                option.textContent = supplier.name;
                                supplierSelect.appendChild(option);
                            }
                        });
                        console.log('Loaded suppliers:', window.supplierData.length);
                    }

                    const dateInput = deliveryForm.querySelector('#delivery-date');
                    const titleInput = deliveryForm.querySelector('#delivery-form-title');
                    const urlTitleInput = deliveryForm.querySelector('#delivery-form-url-title');

                    const generateDeliveryTitle = () => {
                        const supplier = supplierSelect?.options[supplierSelect.selectedIndex]?.text || 'Levering';
                        const date = dateInput?.value || new Date().toISOString().split('T')[0];
                        const timestamp = Date.now();
                        
                        if (titleInput) titleInput.value = `${supplier} - ${date}`;
                        if (urlTitleInput) {
                            const slug = `${supplier}-${date}`.toLowerCase().replace(/\s+/g, '-').replace(/[^\w-]/g, '');
                            urlTitleInput.value = `${slug}-${timestamp}`;
                        }
                    };

                    supplierSelect?.addEventListener('change', generateDeliveryTitle);
                    dateInput?.addEventListener('change', generateDeliveryTitle);

                    // FORM SUBMISSION - ensure title and url_title are filled
                    const form = deliveryForm.closest('form') || deliveryForm.querySelector('form');
                    if (form) {
                        form.addEventListener('submit', (e) => {
                            // Generate values if not set
                            if (!titleInput.value) {
                                const supplier = supplierSelect?.options[supplierSelect.selectedIndex]?.text || 'Levering';
                                const date = dateInput?.value || new Date().toISOString().split('T')[0];
                                const timestamp = Date.now();
                                titleInput.value = `${supplier} - ${date}`;
                                
                                const slug = `${supplier}-${date}`.toLowerCase().replace(/\s+/g, '-').replace(/[^\w-]/g, '');
                                urlTitleInput.value = `${slug}-${timestamp}`;
                            }
                        });
                    }

                    // CUSTOM PRODUCTS
                    let customProductCounter = 0;
                    const customProductForm = deliveryForm.querySelector('#custom-product-form');
                    const addCustomProductBtn = deliveryForm.querySelector('#add-custom-product-btn');
                    const addCustomProductConfirm = deliveryForm.querySelector('#add-custom-product-confirm');
                    const addCustomProductCancel = deliveryForm.querySelector('#add-custom-product-cancel');
                    const customProductsContainer = deliveryForm.querySelector('#custom-products-container');

                    addCustomProductBtn?.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (customProductForm) customProductForm.style.display = 'block';
                    });

                    addCustomProductCancel?.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (customProductForm) customProductForm.style.display = 'none';
                        // clear form
                        deliveryForm.querySelector('#custom-product-name').value = '';
                        deliveryForm.querySelector('#custom-product-ean').value = '';
                        deliveryForm.querySelector('#custom-product-cat').selectedIndex = 0;
                        deliveryForm.querySelector('#custom-product-amount').value = '0';
                    });

                    addCustomProductConfirm?.addEventListener('click', (e) => {
                        e.preventDefault();
                        const name = deliveryForm.querySelector('#custom-product-name').value.trim();
                        const ean = deliveryForm.querySelector('#custom-product-ean').value.trim();
                        const cat = deliveryForm.querySelector('#custom-product-cat').value.trim();
                        const amount = deliveryForm.querySelector('#custom-product-amount').value;

                        if (!name || !cat) {
                            alert('Productnaam en Categorie zijn verplicht!');
                            return;
                        }

                        if (parseInt(amount) < 0) {
                            alert('Aantal moet groter dan 0 zijn!');
                            return;
                        }

                        customProductCounter++;
                        const customId = `custom-${customProductCounter}`;

                        const productCard = document.createElement('div');
                        productCard.className = 'delivery-product-card custom-product-card';
                        productCard.id = `card-${customId}`;
                        productCard.style.cssText = 'border: 2px solid #4CAF50; border-radius: 4px; padding: 16px; background-color: #f1f8f4; position: relative;';
                        productCard.innerHTML = `
                            <div style="position: absolute; top: 8px; right: 8px;">
                                <button type="button" class="remove-custom-product" data-custom-id="${customId}" style="background: #ff5252; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 16px; padding: 0; display: flex; align-items: center; justify-content: center;">×</button>
                            </div>
                            <h4 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #2e7d32; padding-right: 32px;">${name} <span style="font-size: 11px; color: #999; font-weight: normal;">(eigen)</span></h4>
                            <p style="margin: 0 0 4px 0; font-size: 12px; color: #666;">
                                <strong>EAN:</strong> ${ean || 'n.v.t'}
                            </p>
                            <p style="margin: 0 0 12px 0; font-size: 12px; color: #666;">
                                <strong>Categorie:</strong> ${cat}
                            </p>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <label for="product-amount-${customId}" style="font-size: 12px;">Aantal:</label>
                                <input type="number" 
                                       id="product-amount-${customId}" 
                                       class="delivery-product-amount" 
                                       name="custom_products[${customId}][amount]" 
                                       min="0" 
                                       value="${amount}" 
                                       style="width: 60px; padding: 6px; border: 1px solid #ccc; border-radius: 4px;" />
                            </div>
                            <input type="hidden" name="custom_products[${customId}][name]" value="${name}" />
                            <input type="hidden" name="custom_products[${customId}][ean]" value="${ean}" />
                            <input type="hidden" name="custom_products[${customId}][cat]" value="${cat}" />
                        `;

                        customProductsContainer?.appendChild(productCard);

                        // add remove button listener
                        productCard.querySelector('.remove-custom-product')?.addEventListener('click', (e) => {
                            e.preventDefault();
                            productCard.remove();
                        });

                        // clear form and hide
                        deliveryForm.querySelector('#custom-product-name').value = '';
                        deliveryForm.querySelector('#custom-product-ean').value = '';
                        deliveryForm.querySelector('#custom-product-cat').selectedIndex = 0;
                        deliveryForm.querySelector('#custom-product-amount').value = '0';
                        if (customProductForm) customProductForm.style.display = 'none';
                    });
                }

                // MODAL - CMS Editor in iframe (edit & create)
                const modal = root.querySelector('#cms-editor-modal');
                const modalOverlay = root.querySelector('.customers__modal-overlay');
                const modalClose = root.querySelector('#cms-editor-close');
                const cmsIframe = root.querySelector('#cms-editor-iframe');
                const cmsTitle = root.querySelector('#cms-editor-title');

                const openEditorModal = (entryId, entryName, mode = 'edit') => {
                    if (!modal || !cmsIframe) return;

                    if (mode === 'edit' && entryId) {
                        cmsIframe.src = `/cms.php?/cp/publish/edit/entry/${entryId}`;
                        if (cmsTitle) cmsTitle.textContent = `Bewerken: ${entryName}`;
                    } else {
                        // create new supplier — channel short name 'supplier'
                        cmsIframe.src = `/cms.php?/cp/publish/create/entry/supplier`;
                        if (cmsTitle) cmsTitle.textContent = `Nieuwe leverancier`;
                    }

                    modal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';

                    cmsIframe.onload = () => {
                        try {
                            const iframeDoc = cmsIframe.contentDocument || cmsIframe.contentWindow.document;
                            if (!iframeDoc) return;
                            const sidebar = iframeDoc.querySelector('.ee-sidebar');
                            if (sidebar) sidebar.style.display = 'none';
                            const mainHeader = iframeDoc.querySelector('.ee-main-header.entries');
                            if (mainHeader) mainHeader.style.display = 'none';
                            const mainContent = iframeDoc.querySelector('.ee-main');
                            if (mainContent) { mainContent.style.marginLeft = '0'; mainContent.style.width = '100%'; }
                        } catch (e) {
                            console.log('Could not hide sidebar:', e);
                        }
                    };
                };

                const closeEditorModal = () => {
                    if (!modal) return;
                    modal.style.display = 'none';
                    if (cmsIframe) cmsIframe.src = '';
                    document.body.style.overflow = '';
                    // refresh page to reflect changes
                    location.reload();
                };

                modalClose?.addEventListener('click', closeEditorModal);
                modalOverlay?.addEventListener('click', closeEditorModal);
                document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal && modal.style.display === 'flex') closeEditorModal(); });

                // edit buttons
                root.querySelectorAll('[data-action="edit-modal"]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const entryId = btn.dataset.entryId;
                        const entryName = btn.dataset.entryName;
                        if (entryId) openEditorModal(entryId, entryName, 'edit');
                    });
                });

                // create button
                root.querySelectorAll('[data-action="create-modal"]').forEach((btn) => {
                    btn.addEventListener('click', () => openEditorModal(null, null, 'create'));
                });
            };

            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
        })();

