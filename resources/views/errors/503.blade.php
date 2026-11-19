<x-error-page code="503" icon="refresh-cw" title="ລະບົບກຳລັງປັບປຸງ" message="ພວກເຮົາກຳລັງປັບປຸງລະບົບ ຈະກັບມາໃຊ້ງານໄດ້ໃນອີກບໍ່ດົນ.">
    <x-slot:actions><a href="{{ url()->current() }}" class="btn-primary">{{ icon('refresh-cw', 'size-4') }} ໂຫຼດໃໝ່</a></x-slot:actions>
</x-error-page>
