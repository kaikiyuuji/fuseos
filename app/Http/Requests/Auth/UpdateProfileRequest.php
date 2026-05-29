<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Atualização parcial — todos os campos opcionais, mas validados quando presentes.
     * Email e senha são deliberadamente ausentes (não alteráveis aqui).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'avatar' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'status' => ['sometimes', 'nullable', 'string', 'max:140'],
        ];
    }
}
