@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'bg-[var(--paper)] border-[var(--line)] text-[var(--ink)] placeholder-[var(--moss)]/50 focus:border-[var(--gold)] focus:ring-[var(--gold)] rounded-lg shadow-sm']) !!}>
