@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<div class="row g-3 mb-3">
<div class="col-md-6"><label class="form-label">Nama Supplier *</label><input name="name" class="form-control" value="{{ old('name',$supplier->name ?? '') }}" required></div>
<div class="col-md-6"><label class="form-label">Kontak</label><input name="contact_name" class="form-control" value="{{ old('contact_name',$supplier->contact_name ?? '') }}"></div>
<div class="col-md-4"><label class="form-label">HP</label><input name="phone" class="form-control" value="{{ old('phone',$supplier->phone ?? '') }}"></div>
<div class="col-md-4"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="{{ old('email',$supplier->email ?? '') }}"></div>
<div class="col-md-4"><label class="form-label">Status</label><select name="is_active" class="form-select"><option value="1" @selected(old('is_active',($supplier->is_active ?? true)?1:0)==1)>Aktif</option><option value="0" @selected(old('is_active',($supplier->is_active ?? true)?1:0)==0)>Nonaktif</option></select></div>
<div class="col-12"><label class="form-label">Alamat</label><textarea name="address" class="form-control" rows="2">{{ old('address',$supplier->address ?? '') }}</textarea></div>
</div>
