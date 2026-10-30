<td class="index-table-td text-center">
    @if (!empty($groupItem['is_active']) && (array_key_exists('category', $groupItem) || $allowGroupSelection) && (!$requestAllowedOnly || !empty($groupItem['request_allowed'])))
        <button type="button" wire:click="selectService(@js($groupItem['id']))"
                class="inline-flex cursor-pointer items-center justify-center" title="{{ __('forms.select') }}">
            @icon('check', 'w-5 h-5 text-blue-600')
        </button>
    @endif
</td>
