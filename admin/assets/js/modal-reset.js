(function() {
  'use strict';

  function clearUrlParams() {
    try {
      const url = new URL(window.location);
      url.searchParams.delete('action');
      url.searchParams.delete('id');
      window.history.replaceState({}, '', url);
    } catch (e) { /* ignore */ }
  }

  function clearHiddenIds(form) {
    form.querySelectorAll('input[type="hidden"][name*="_id"]').forEach(input => {
      if (input.name !== 'page' && input.name !== 'panel') {
        input.value = '';
      }
    });
  }

  function resetForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
      form.reset();
      clearHiddenIds(form);
    }
    clearUrlParams();
  }

  function initGlobalModalReset() {
    if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
      // shouldn't happen if scripts loaded in right order, but guard anyway
      return;
    }

    const modals = document.querySelectorAll('.modal');
    if (!modals || modals.length === 0) return;

    modals.forEach(modal => {
      const form = modal.querySelector('form');
      if (!form) return;

      const modalId = modal.id;

      // hidden.bs.modal: reset and clear ids
      modal.addEventListener('hidden.bs.modal', function() {
        const isEditMode = window.location.search.includes('action=edit');
        if (isEditMode) clearUrlParams();
        form.reset();
        clearHiddenIds(form);
      });

      // show.bs.modal: if not edit mode reset
      modal.addEventListener('show.bs.modal', function() {
        const isEditMode = window.location.search.includes('action=edit');
        if (!isEditMode) {
          form.reset();
          clearHiddenIds(form);
        }
      });

      // init Add button listeners (safely)
      if (modalId) {
        const selectors = [
          `[data-bs-target="#${modalId}"]`,
          `[data-bs-toggle="modal"][href="#${modalId}"]`
        ];
        document.querySelectorAll(selectors.join(',')).forEach(button => {
          button.addEventListener('click', function() {
            const isEditMode = window.location.search.includes('action=edit');
            if (isEditMode) clearUrlParams();
            setTimeout(() => {
              if (!window.location.search.includes('action=edit')) {
                form.reset();
                clearHiddenIds(form);
              }
            }, 80);
          });
        });
      }
    });
  }

  // Wait for DOM and for bootstrap loaded
  function readyInit() {
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
      initGlobalModalReset();
    } else {
      // Poll until bootstrap available or timeout
      let attempts = 0;
      const interval = setInterval(() => {
        attempts++;
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
          clearInterval(interval);
          initGlobalModalReset();
        } else if (attempts > 30) { // ~3s timeout
          clearInterval(interval);
        }
      }, 100);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', readyInit);
  } else {
    readyInit();
  }

  window.resetForm = resetForm;
  window.clearUrlParams = clearUrlParams;
})();
