@props([
    'id' => 'confirm-modal',
    'title' => 'Konfirmasi Tindakan',
    'message' => 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
    'confirmText' => 'Ya, Lanjutkan',
    'cancelText' => 'Batal',
    'confirmVariant' => 'danger',
])

<div class="modal-backdrop" id="{{ $id }}" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-icon-wrap modal-icon-{{ $confirmVariant }}">
                <x-icon name="alert-triangle" size="22" />
            </div>
            <div>
                <h3 class="modal-title" id="{{ $id }}-title">{{ $title }}</h3>
                <p class="modal-desc" id="{{ $id }}-desc">{{ $message }}</p>
            </div>
        </div>

        <form id="{{ $id }}-form" method="POST" action="">
            @csrf
            <input type="hidden" name="_method" value="POST">

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="window.LaundryDesk.closeConfirmModal()">
                    {{ $cancelText }}
                </button>
                <button type="submit" class="btn btn-{{ $confirmVariant }}" id="{{ $id }}-submit">
                    {{ $confirmText }}
                </button>
            </div>
        </form>
    </div>
</div>
