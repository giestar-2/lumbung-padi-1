let confirmationPending = false;
const approvedActions = new WeakSet();

function requestConfirmation(source, proceed) {
    if (confirmationPending) return;

    confirmationPending = true;
    const destructive = source.dataset.confirm.trim().startsWith('Hapus');
    const finish = () => { confirmationPending = false; };

    window.dispatchEvent(new CustomEvent('app-confirmation', {
        detail: {
            message: source.dataset.confirm,
            title: source.dataset.confirmTitle || (destructive ? 'Hapus data ini?' : 'Konfirmasi tindakan'),
            label: source.dataset.confirmLabel || (destructive ? 'Ya, hapus' : 'Ya, lanjutkan'),
            destructive,
            onCancel: finish,
            onConfirm: () => {
                finish();
                if (source.isConnected && !source.disabled) proceed();
            },
        },
    }));
}

document.addEventListener('click', (event) => {
    const source = event.target.closest?.('[data-confirm]');
    if (!source || source.disabled || approvedActions.has(source)) return;

    const hasClickAction = source.getAttributeNames().some((name) => name.startsWith('wire:click'));
    if (source instanceof HTMLButtonElement && source.type === 'submit' && source.form && !hasClickAction) return;

    event.preventDefault();
    event.stopImmediatePropagation();
    requestConfirmation(source, () => {
        approvedActions.add(source);
        try { source.click(); } finally { approvedActions.delete(source); }
    });
}, true);

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (approvedActions.has(form)) return;

    const submitter = event.submitter;
    const source = form.hasAttribute('data-confirm') ? form : submitter?.closest('[data-confirm]');
    if (!source) return;

    event.preventDefault();
    event.stopImmediatePropagation();
    requestConfirmation(source, () => {
        if (!form.isConnected) return;
        approvedActions.add(form);
        try { form.requestSubmit(submitter || undefined); } finally { approvedActions.delete(form); }
    });
}, true);
