<?php $pageTitle = 'Subir factura — ' . APP_NAME; ?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= BASE_URL ?>/clientes/<?= $cliente['id'] ?>" class="btn-back"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 class="page-title">Subir factura</h1>
        <p class="page-subtitle mb-0">Cliente: <strong><?= htmlspecialchars($cliente['nombre']) ?></strong></p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body" style="padding:28px !important">
                <form method="POST" action="<?= BASE_URL ?>/clientes/<?= $cliente['id'] ?>/facturas/subir"
                      enctype="multipart/form-data" id="formSubida">

                    <label class="form-label mb-2">Archivo de factura <span style="color:var(--red)">*</span></label>

                    <label id="dropzone" style="display:block;cursor:pointer">
                        <div style="pointer-events:none">
                            <div style="width:56px;height:56px;background:var(--border-light);border-radius:var(--radius-lg);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:24px;color:var(--text-muted);transition:all var(--transition)" id="dzIcon">
                                <i class="bi bi-cloud-upload"></i>
                            </div>
                            <div style="font-size:15px;font-weight:600;color:var(--text);margin-bottom:4px">
                                Arrastra el archivo aquí
                            </div>
                            <div style="font-size:13px;color:var(--text-muted);margin-bottom:16px">
                                o haz clic para seleccionar
                            </div>
                            <div style="display:inline-block;border:1px solid var(--border);border-radius:var(--radius-sm);padding:6px 16px;font-size:13px;font-weight:500;color:var(--text-muted);background:var(--surface)">
                                Seleccionar archivo
                            </div>
                            <p style="font-size:12px;color:var(--text-subtle);margin:14px 0 0">
                                PDF, JPG, PNG, WEBP, TIFF · Máx. <?= env('UPLOAD_MAX_SIZE_MB', 10) ?> MB
                            </p>
                        </div>
                        <input type="file" name="factura" id="inputFile" class="d-none"
                               accept=".pdf,.jpg,.jpeg,.png,.webp,.tif,.tiff" required>
                    </label>

                    <div id="filePreview" class="d-none mt-3"
                         style="display:flex;align-items:center;gap:10px;background:var(--green-bg);border:1px solid var(--green-border);border-radius:var(--radius-sm);padding:10px 14px">
                        <i class="bi bi-file-earmark-check" style="font-size:18px;color:var(--green)"></i>
                        <span id="fileName" style="font-size:13px;font-weight:500;color:var(--green)"></span>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mt-4" id="btnSubir" style="padding:10px">
                        <i class="bi bi-upload"></i> Subir factura
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('inputFile').addEventListener('change', function() {
    if (this.files.length > 0) {
        document.getElementById('fileName').textContent = this.files[0].name;
        document.getElementById('filePreview').classList.remove('d-none');
        document.getElementById('filePreview').style.display = 'flex';
    }
});

document.getElementById('formSubida').addEventListener('submit', function() {
    const btn = document.getElementById('btnSubir');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Subiendo...';
});
</script>
