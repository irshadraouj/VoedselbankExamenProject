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

