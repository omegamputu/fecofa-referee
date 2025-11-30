<div>
    <button wire:click="switchLanguage"
        class="bg-white/10 rounded-lg text-white px-4 py-2 font-semibold text-sm hover:bg-white/10 cursor-pointer transition">
        @if ($locale === 'fr')
            EN - English
        @else
            FR - Français
        @endif
    </button>
</div>