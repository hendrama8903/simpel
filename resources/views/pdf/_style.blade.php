{{-- Gaya dasar PDF (dompdf: hanya CSS 2.1, tanpa flex/grid). --}}
<style>
    @page { margin: 22px 28px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
    .kop { width: 100%; border-bottom: 2.5px double #000; margin-bottom: 10px; }
    .kop td { vertical-align: middle; }
    .kop-logo { width: 70px; }
    .kop-logo img { width: 62px; height: 62px; }
    .kop-text { text-align: center; font-size: 9.5px; line-height: 1.35; }
    .kop-name { font-size: 14px; font-weight: bold; }
    h1 { font-size: 13px; text-align: center; margin: 4px 0 8px; }
    table.grid { width: 100%; border-collapse: collapse; }
    table.grid th, table.grid td { border: 1px solid #333; padding: 4px 5px; }
    table.grid th { background: #E8EEF9; }
    .center { text-align: center; }
    .right { text-align: right; }
    .muted { color: #555; }
    .page-break { page-break-after: always; }
</style>
