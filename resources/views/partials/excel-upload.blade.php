{{--
    Popup upload Excel + popup hasil upload (baris yang dilewati). Dipakai menu Siswa dan Guru.
    Pasangannya di controller: App\Http\Controllers\Concerns\HandlesExcelUpload.

    Parameter:
      $title   : judul, mis. "Data Siswa"
      $baseUrl : prefix route, mis. url('/Student') -> /Upload, /DownloadTemplate
      $hint    : penjelasan cara data diperbarui
    Tombol pemicu: #btn_upload di toolbar. Setelah upload sukses dipanggil window.onExcelUploaded() kalau ada.
--}}
<div class="modal fade sg-modal" id="modalUpload" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-upload"></i></div>
                <div>
                    <div class="dpf-header-title">Unggah {{ $title }}</div>
                    <div class="dpf-header-sub">File Excel (.xlsx / .xls)</div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body">
                <div class="dpf-row">
                    <div class="dpf-row-left">
                        <div class="dpf-label">File <span class="text-red">*</span></div>
                    </div>
                    <div class="dpf-row-right">
                        <input class="form-control" id="uploadFile" type="file" accept=".xlsx,.xls" />
                        <a href="{{ $baseUrl }}/DownloadTemplate" class="sg-upload-template"><i class="fa fa-download"></i> Unduh template</a>
                        <div class="dpf-hint">Maks. 10 MB. {{ $hint }}</div>
                    </div>
                </div>
            </div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Batal</button>
                <button type="button" class="dpf-btn dpf-btn-primary" id="btn_doUpload"><i class="fa fa-upload"></i> Unggah</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade sg-modal" id="modalUploadResult" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="dpf-header">
                <div class="dpf-header-icon"><i class="fa fa-exclamation-triangle"></i></div>
                <div>
                    <div class="dpf-header-title">Hasil Unggah</div>
                    <div class="dpf-header-sub" id="uploadResultSub"></div>
                </div>
                <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
            </div>
            <div class="dpf-body">
                <ul id="uploadResultWarnings" class="sg-upload-warnings"></ul>
            </div>
            <div class="dpf-footer">
                <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Tutup</button>
            </div>
        </div>
    </div>
</div>

@push('excel-upload-scripts')
<style>
    .sg-upload-template { display: inline-block; margin-top: 6px; font-size: 12px; font-weight: 600; }
    .sg-upload-warnings { max-height: 420px; overflow-y: auto; padding-left: 18px; margin: 12px 0; }
    .sg-upload-warnings li { margin-bottom: 6px; word-break: break-word; }
</style>
<script type="text/javascript">
    (function () {
        var baseUrl = @json($baseUrl), title = @json($title);

        $(function () {
            $("#btn_upload").on("click", function () {
                $("#uploadFile").val("");
                $("#modalUpload").modal("show");
            });

            $("#btn_doUpload").on("click", function () {
                var file = $("#uploadFile")[0].files[0];
                if (!file) { showErrorMesgGrowl("Pilih file yang akan diunggah."); return; }

                showConfirmMesg("Unggah " + HMA.escapeHtml(file.name) + " ke <b>" + HMA.escapeHtml(title) + "</b>?", function () {
                    var form = new FormData();
                    form.append("file", file);

                    popUpProgressShow();
                    $.ajax({
                        type: "POST",
                        url: baseUrl + "/Upload",
                        data: form,
                        processData: false,
                        contentType: false,
                        dataType: "json"
                    }).done(function (result) {
                        popUpProgressHide();
                        if (result.status !== true) {
                            showErrorMesgGrowl(HMA.formatMessage(result.message));
                            return;
                        }
                        showSuccessMesgGrowl(HMA.formatMessage(result.message));
                        $("#modalUpload").modal("hide");
                        if (typeof window.onExcelUploaded === "function") window.onExcelUploaded();
                        if (result.warnings && result.warnings.length) {
                            $("#uploadResultSub").text(result.message);
                            var $list = $("#uploadResultWarnings").empty();
                            $.each(result.warnings, function (_, w) { $list.append($("<li>").text(w)); });
                            $("#modalUploadResult").modal("show");
                        }
                    }).fail(function () {
                        popUpProgressHide();
                        showErrorMesgGrowl("Unggah gagal. Silakan coba lagi.");
                    });
                }, "Konfirmasi");
            });
        });
    })();
</script>
@endpush
