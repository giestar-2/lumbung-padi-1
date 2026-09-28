@if(session('message'))
<div role="status" class="mb-5 flex items-center gap-3 rounded-xl border border-[#bce8d9] bg-[#ecfaf5] px-4 py-3 text-sm text-[#087357]"><x-icon name="check"/>{{ session('message') }}</div>
@endif
@if($errors->any())
<div role="alert" class="mb-5 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700"><p class="font-semibold">Periksa kembali data berikut.</p><ul class="mt-1 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
