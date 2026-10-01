/**
 * Helper modal generik.
 * Dipakai di halaman CRUD (nasabah, petugas, jenis sampah) untuk
 * membuka modal tambah/edit/hapus tanpa reload halaman.
 */

function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
}

/**
 * Mengisi form edit dengan data dari tombol yang diklik.
 * Contoh pemakaian tombol:
 * <button onclick="fillEditForm('modalEdit', this)" data-id="1" data-nama="Budi">Edit</button>
 *
 * formPrefix menentukan id input tujuan, contoh: data-nama -> #edit_nama
 */
function fillEditForm(modalId, sourceEl) {
    const dataset = sourceEl.dataset;
    Object.keys(dataset).forEach(function (key) {
        const input = document.getElementById('edit_' + key);
        if (input) input.value = dataset[key];
    });
    openModal(modalId);
}

/** Set nilai form hapus (id record) lalu buka modal konfirmasi */
function confirmDelete(modalId, id) {
    const input = document.getElementById('delete_id');
    if (input) input.value = id;
    openModal(modalId);
}

// Tutup modal jika klik area gelap di luar box
document.addEventListener('click', function (e) {
    if (e.target.classList && e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('open');
    }
});
