<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $operation = $this->input('operation');

        $rules = [
            'contractVersion' => ['required', 'integer', 'in:1'],
            'operation' => ['required', 'string', 'in:restore,poll,message,handoff_requested'],
        ];

        // sessionId omission is permitted only on first writes (message/handoff_requested).
        // Restore/poll require the opaque token; malformed/unknown commands fail before anything is created.
        if (in_array($operation, ['restore', 'poll'], true)) {
            $rules['sessionId'] = ['required', 'string', 'max:128'];
        } else {
            $rules['sessionId'] = ['nullable', 'string', 'max:128'];
        }

        if ($operation === 'poll') {
            $rules['after'] = ['required', 'integer', 'min:0', 'max:2147483647'];
        } else {
            $rules['after'] = ['prohibited'];
        }

        if ($operation === 'message') {
            $rules['text'] = ['required', 'string', 'max:2000'];
        } else {
            $rules['text'] = ['prohibited'];
        }

        if (in_array($operation, ['message', 'handoff_requested'], true)) {
            $rules['page'] = ['required', 'string', 'max:150'];
        } else {
            $rules['page'] = ['prohibited'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // Body-only token transport: reject credentials carried in the URL.
            foreach (['sessionId', 'after', 'contractVersion', 'operation', 'text', 'page'] as $key) {
                if ($this->query($key) !== null) {
                    $validator->errors()->add($key, 'Use POST body, not query string.');
                }
            }
        });
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()->toArray(),
            ], 422)->header('Cache-Control', 'no-store')
        );
    }
}
