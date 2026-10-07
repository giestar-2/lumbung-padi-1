<dialog
    id="confirmation-dialog"
    x-ref="dialog"
    x-data="{
        title: '',
        message: '',
        label: '',
        destructive: false,
        confirmAction: null,
        cancelAction: null,
        open(detail) {
            this.title = detail.title;
            this.message = detail.message;
            this.label = detail.label;
            this.destructive = detail.destructive;
            this.confirmAction = detail.onConfirm;
            this.cancelAction = detail.onCancel;
            this.$refs.dialog.showModal();
        },
        finish(confirmed) {
            const action = confirmed ? this.confirmAction : this.cancelAction;
            this.confirmAction = this.cancelAction = null;
            if (this.$refs.dialog.open) this.$refs.dialog.close();
            action?.();
        },
        closeOnBackdrop(event) {
            const rect = this.$refs.dialog.getBoundingClientRect();
            if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) this.finish(false);
        },
        destroy() { this.cancelAction?.(); }
    }"
    @app-confirmation.window="open($event.detail)"
    @cancel.prevent="finish(false)"
    @keydown.escape.stop
    @click="closeOnBackdrop($event)"
    aria-labelledby="confirmation-title"
    aria-describedby="confirmation-description"
    class="confirmation-dialog m-auto w-[calc(100%-2rem)] max-w-md rounded-2xl border-0 bg-white p-0 text-brand-textDark shadow-elegant"
>
    <div class="p-6 sm:p-7">
        <div class="mb-5 flex items-start justify-between gap-4">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl" :class="destructive ? 'bg-red-50 text-red-600' : 'bg-brand-light text-brand-primary'">
                <span x-show="!destructive"><x-icon name="check" class="h-6 w-6"/></span>
                <span x-show="destructive"><x-icon name="trash" class="h-6 w-6"/></span>
            </span>
            <button type="button" @click="finish(false)" class="rounded-lg p-2 text-brand-textGray hover:bg-brand-bgMain" aria-label="Tutup konfirmasi"><x-icon name="close"/></button>
        </div>
        <h2 id="confirmation-title" x-text="title" class="font-heading text-xl font-bold"></h2>
        <p id="confirmation-description" x-text="message" class="mt-3 text-sm leading-6 text-brand-textGray"></p>
    </div>
    <div class="flex justify-end gap-3 border-t border-[#eaeef2] bg-[#f6fafe] px-6 py-4 sm:px-7">
        <button type="button" @click="finish(false)" autofocus class="btn">Batal</button>
        <button type="button" @click="finish(true)" x-text="label" class="btn" :class="destructive ? 'bg-red-600 text-white hover:bg-red-700' : 'btn-primary'" data-confirm-accept></button>
    </div>
</dialog>
