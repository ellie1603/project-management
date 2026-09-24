@php
    $contractor = $contractor ?? null;
@endphp

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label for="contractor-name" class="mb-1 block text-sm font-medium text-slate-700">Contractor / Company Name</label>
        <input id="contractor-name" name="contractor_name" value="{{ old('contractor_name', $contractor->name ?? '') }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
    </div>
    <div>
        <label for="contractor-contact-person" class="mb-1 block text-sm font-medium text-slate-700">Contact Person</label>
        <input id="contractor-contact-person" name="contact_person" value="{{ old('contact_person', $contractor->contact_person ?? '') }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
    </div>
    <div>
        <label for="contractor-contact-number" class="mb-1 block text-sm font-medium text-slate-700">Contact Number</label>
        <input id="contractor-contact-number" name="contact_number" value="{{ old('contact_number', $contractor->contact_number ?? '') }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
    </div>
    <div>
        <label for="contractor-email" class="mb-1 block text-sm font-medium text-slate-700">Business Email</label>
        <input id="contractor-email" type="email" name="contractor_email" value="{{ old('contractor_email', $contractor->email ?? '') }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
    </div>
    <div class="md:col-span-2">
        <label for="contractor-address" class="mb-1 block text-sm font-medium text-slate-700">Address</label>
        <input id="contractor-address" name="address" value="{{ old('address', $contractor->address ?? '') }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
    </div>
    <div class="md:col-span-2">
        <label for="contractor-registration" class="mb-1 block text-sm font-medium text-slate-700">Registration Information</label>
        <input id="contractor-registration" name="registration_information" value="{{ old('registration_information', $contractor->registration_information ?? '') }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
    </div>
    <div>
        <label for="contractor-status" class="mb-1 block text-sm font-medium text-slate-700">Contractor Status</label>
        <select id="contractor-status" name="contractor_status" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
            <option value="active" @selected(old('contractor_status', $contractor->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('contractor_status', $contractor->status ?? '') === 'inactive')>Inactive</option>
        </select>
    </div>
</div>
