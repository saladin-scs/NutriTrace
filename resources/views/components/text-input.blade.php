@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-[var(--nt-line)] focus:border-nt-leaf focus:ring-nt-leaf rounded-2xl shadow-sm']) }}>
