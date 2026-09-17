{{--
    A tab whose editor has not landed yet (16.2).

    The nav is the whole shell, so every tab has to open. This says what is
    coming rather than rendering an empty pane that reads as a bug.
--}}
<div class="text-center py-5">
    <i class="bi bi-hammer display-5 text-secondary d-block mb-3"></i>
    <p class="text-secondary mb-0">
        {{ __('Bagian ":tab" sedang disiapkan.', ['tab' => $tabLabel]) }}
    </p>
</div>
