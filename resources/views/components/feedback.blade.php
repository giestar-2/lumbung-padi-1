@if(session('message'))
<span class="hidden" wire:key="feedback-success-{{ sha1(session('message')) }}" x-data x-init="$dispatch('app-notification', { message: @js(session('message')) })"></span>
@endif
@if($errors->any())
<div role="alert" class="mb-5 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700"><p class="font-semibold">Periksa kembali data berikut.</p><ul class="mt-1 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
