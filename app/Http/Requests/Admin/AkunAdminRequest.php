<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/** Tambah (POST) dan ubah (PUT) akun admin/petugas. */
class AkunAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $akun = $this->route('akunAdmin');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($akun)],
            'no_hp' => ['nullable', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'status' => ['required', Rule::in([User::STATUS_AKTIF, User::STATUS_NONAKTIF])],
            // Wajib saat tambah; saat ubah hanya diisi bila ingin reset password
            'password' => [$akun ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var User|null $akun */
                $akun = $this->route('akunAdmin');
                if (! $akun || $this->input('status') !== User::STATUS_NONAKTIF) {
                    return;
                }

                // Jangan sampai admin mengunci dirinya sendiri / tidak ada admin aktif tersisa
                if ($akun->is($this->user())) {
                    $validator->errors()->add('status', 'Anda tidak bisa menonaktifkan akun sendiri.');
                } elseif ($akun->isAktif() && User::where('role', User::ROLE_ADMIN)->where('status', User::STATUS_AKTIF)->count() <= 1) {
                    $validator->errors()->add('status', 'Minimal harus ada satu admin aktif.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama', 'no_hp' => 'no. HP'];
    }

    public function messages(): array
    {
        return ['no_hp.regex' => 'No. HP hanya boleh berisi angka, spasi, + atau - (8–20 karakter).'];
    }
}
