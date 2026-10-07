<div
    x-data="{
        visible: false,
        message: '',
        timer: null,
        notify(detail) {
            clearTimeout(this.timer);
            this.message = detail.message;
            this.visible = true;
            this.dismissLater();
        },
        dismissLater() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.visible = false, 6000);
        }
    }"
    @app-notification.window="notify($event.detail)"
    @mouseenter="clearTimeout(timer)"
    @mouseleave="dismissLater()"
    x-show="visible"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-end="opacity-0 translate-y-2"
    role="status"
    aria-live="polite"
    aria-atomic="true"
    class="fixed right-4 top-20 z-[60] flex w-[calc(100%-2rem)] max-w-sm items-start gap-3 rounded-2xl border border-emerald-100 bg-white p-4 shadow-elegant sm:right-6"
>
    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"><x-icon name="check"/></span>
    <div class="min-w-0 flex-1"><p class="text-sm font-semibold text-brand-textDark">Berhasil</p><p x-text="message" class="mt-1 text-xs leading-5 text-brand-textGray"></p></div>
    <button type="button" @click="visible = false; clearTimeout(timer)" aria-label="Tutup notifikasi" class="rounded-lg p-1 text-brand-textGray hover:bg-brand-bgMain"><x-icon name="close" class="h-4 w-4"/></button>
</div>
