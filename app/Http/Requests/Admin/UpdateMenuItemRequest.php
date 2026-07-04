<?php

namespace App\Http\Requests\Admin;

use App\Models\MenuItem;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Marshrut `auth` middleware ostida. Rollar qo'shilsa — Policy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'array'],
            'title.uz' => ['required', 'string', 'max:255'],
            'title.ru' => ['nullable', 'string', 'max:255'],
            'title.kk' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:2048'],
            'parent_id' => ['nullable', 'integer', 'exists:menu_items,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'target_blank' => ['boolean'],
        ];
    }

    /**
     * Cheksiz sikl oldini olish: element o'zini yoki o'z avlodini
     * ota qilib tanlay olmaydi.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $parentId = $this->integer('parent_id') ?: null;

            if ($parentId === null) {
                return;
            }

            /** @var MenuItem $menuItem */
            $menuItem = $this->route('menu_item');

            // O'zini ota qilish
            if ($parentId === $menuItem->id) {
                $validator->errors()->add('parent_id', __('Menyu o‘zini ota sifatida tanlay olmaydi.'));

                return;
            }

            // Avlodini ota qilish (sikl)
            if (in_array($parentId, $this->descendantIds($menuItem), true)) {
                $validator->errors()->add('parent_id', __('Menyu o‘z ostidagi elementni ota sifatida tanlay olmaydi.'));
            }
        });
    }

    /**
     * Berilgan element ostidagi barcha avlod ID'lari.
     *
     * @return array<int, int>
     */
    private function descendantIds(MenuItem $menuItem): array
    {
        $ids = [];

        foreach ($menuItem->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->descendantIds($child));
        }

        return $ids;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title.uz' => __('Sarlavha (o‘zbekcha)'),
            'title.ru' => __('Sarlavha (ruscha)'),
            'title.kk' => __('Sarlavha (qoraqalpoqcha)'),
            'url' => __('Havola'),
            'parent_id' => __('Ota menyu'),
            'sort_order' => __('Tartib'),
        ];
    }
}
