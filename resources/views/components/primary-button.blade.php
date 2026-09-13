<button {{ $attributes->merge(['type' => 'submit', 'class' => 'nt-btn']) }}>
    {{ $slot }}
</button>
