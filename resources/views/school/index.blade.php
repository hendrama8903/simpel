{{-- Profil sekolah: identitas, kepala sekolah, logo, KKTP & bobot nilai rapor. --}}
@extends('layouts.admin')

@php($s = $school)

@section('content')
<section class="content">
    <div class="dp-card">
        <div class="box-header dp-toolbar">
            <div class="dp-toolbar-left">
                @if ($edit)
                    <button type="button" class="dp-btn dp-btn-primary" id="btn_save"><i class="fa fa-save"></i> Simpan</button>
                @endif
            </div>
        </div>
        <div class="box-body">
            <div class="sch-layout">
                <div class="sch-logo">
                    <img id="logoImg" src="{{ url('/School/Logo') }}?v={{ time() }}" alt="Logo sekolah">
                    @if ($edit)
                        <input type="file" id="logoFile" accept=".jpg,.jpeg,.png" style="display:none">
                        <button type="button" class="dp-btn dp-btn-outline" id="btn_logo" {{ $s->SchoolId ? '' : 'disabled' }}><i class="fa fa-image"></i> Ganti Logo</button>
                        <div class="dpf-hint">JPG/PNG maks. 2 MB, dipakai di kop rapor &amp; kartu ujian.</div>
                    @endif
                </div>

                <div>
                    <div class="sg-doc-section">Identitas Sekolah</div>
                    <div class="sg-doc-form">
                        <div class="sg-span-2"><label>Nama Sekolah <span class="text-red">*</span></label><input class="form-control" data-field="SchoolName" maxlength="150"></div>
                        <div><label>Jenjang</label>
                            <select class="form-control" data-field="SchoolLevel">
                                @foreach (\App\Repositories\SchoolRepo::Levels as $code => $label)
                                    <option value="{{ $code }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div><label>NPSN</label><input class="form-control" data-field="Npsn" maxlength="20"></div>
                        <div class="sg-span-2"><label>Alamat</label><input class="form-control" data-field="Address" maxlength="255"></div>
                        <div><label>Desa/Kelurahan</label><input class="form-control" data-field="Village" maxlength="100"></div>
                        <div><label>Kecamatan</label><input class="form-control" data-field="District" maxlength="100"></div>
                        <div><label>Kabupaten/Kota</label><input class="form-control" data-field="City" maxlength="100"></div>
                        <div><label>Provinsi</label><input class="form-control" data-field="Province" maxlength="100"></div>
                        <div><label>Kode Pos</label><input class="form-control" data-field="PostalCode" maxlength="10"></div>
                        <div><label>Telepon</label><input class="form-control" data-field="Phone" maxlength="30"></div>
                        <div><label>Email</label><input class="form-control" data-field="Email" maxlength="100"></div>
                        <div><label>Website</label><input class="form-control" data-field="Website" maxlength="100"></div>
                    </div>

                    <div class="sg-doc-section">Kepala Sekolah &amp; Tanda Tangan</div>
                    <div class="sg-doc-form">
                        <div><label>Nama Kepala Sekolah</label><input class="form-control" data-field="PrincipalName" maxlength="100"></div>
                        <div><label>NIP Kepala Sekolah</label><input class="form-control" data-field="PrincipalNip" maxlength="30"></div>
                        <div><label>Tempat Tanda Tangan</label><input class="form-control" data-field="ReportPlace" maxlength="100" placeholder="mis. Bandung"></div>
                    </div>

                    <div class="sg-doc-section">Penilaian (Kurikulum Merdeka)</div>
                    <div class="sg-doc-form">
                        <div><label>KKTP</label><input class="form-control" type="number" min="0" max="100" data-field="Kktp">
                            <div class="dpf-hint">Batas ketercapaian TP. Nilai TP di bawah KKTP masuk deskripsi "perlu bantuan".</div></div>
                        <div><label>Bobot Sumatif Lingkup Materi (%)</label><input class="form-control" type="number" min="0" max="100" data-field="WeightSummative"></div>
                        <div><label>Bobot STS (%)</label><input class="form-control" type="number" min="0" max="100" data-field="WeightSts"></div>
                        <div><label>Bobot SAS (%)</label><input class="form-control" type="number" min="0" max="100" data-field="WeightSas">
                            <div class="dpf-hint">Jumlah ketiga bobot harus 100. Komponen yang belum ada nilainya tidak dihitung.</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<style>
    .sch-layout { display: grid; grid-template-columns: 200px minmax(0, 1fr); gap: 24px; padding: 6px 4px 16px; }
    .sch-logo { text-align: center; }
    .sch-logo img { width: 160px; height: 160px; object-fit: contain; border: 1px solid #EAECF0; border-radius: 12px; padding: 10px; background: #fff; margin-bottom: 10px; }
    .sch-layout .sg-doc-section { margin-top: 14px; }
    @media (max-width: 768px) { .sch-layout { grid-template-columns: 1fr; } }
</style>
<script type="text/javascript">
    var gSchool = @json($s);
    var gCanEdit = @json($edit);

    $(function () {
        $("[data-field]").each(function () {
            var v = gSchool[$(this).data("field")];
            $(this).val(v === null || v === undefined ? "" : v).prop("disabled", !gCanEdit);
        });

        $("#btn_save").on("click", function () {
            var data = {};
            $("[data-field]").each(function () { data[$(this).data("field")] = $(this).val(); });
            showConfirmMesg("Simpan profil sekolah?", function () {
                HMA.post("{{ url('/School/Save') }}", data, function () { $("#btn_logo").prop("disabled", false); });
            }, "Konfirmasi");
        });

        $("#btn_logo").on("click", function () { $("#logoFile").val("").trigger("click"); });
        $("#logoFile").on("change", function () {
            var file = this.files[0];
            if (!file) return;
            var form = new FormData();
            form.append("file", file);
            popUpProgressShow();
            $.ajax({ type: "POST", url: "{{ url('/School/UploadLogo') }}", data: form, processData: false, contentType: false, dataType: "json" })
                .done(function (result) {
                    popUpProgressHide();
                    if (result.status) {
                        showSuccessMesgGrowl(HMA.formatMessage(result.message));
                        $("#logoImg").attr("src", "{{ url('/School/Logo') }}?v=" + Date.now());
                    } else {
                        showErrorMesgGrowl(HMA.formatMessage(result.message));
                    }
                })
                .fail(popUpProgressHide);
        });
    });
</script>
@endsection
