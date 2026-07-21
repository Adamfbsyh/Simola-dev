<x-app-layout>
    <x-slot name="header">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
            <div>
                <h2 style="font-size:20px; font-weight:800; margin:0; color:#111827;">
                    Detail File PDF
                </h2>
                <p style="font-size:13px; color:#6b7280; margin:4px 0 0;">
                    {{ $reportUpload->nama_file }}
                </p>
            </div>

            <div style="display:flex; gap:8px;">
                <a href="{{ url()->previous() }}"
                    style="background:#6b7280; color:white; padding:9px 14px; border-radius:8px; text-decoration:none; font-weight:700; font-size:13px;">
                    Kembali
                </a>

                <a href="{{ route('upload-terpadu.download', $reportUpload->id) }}"
                    style="background:#16a34a; color:white; padding:9px 14px; border-radius:8px; text-decoration:none; font-weight:700; font-size:13px;">
                    Unduh PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div style="max-width:1200px; margin:0 auto; padding:24px;">
    <div style="background:white; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 8px 20px rgba(0,0,0,0.06); overflow:hidden;">

        <object
            data="{{ route('upload-terpadu.preview', $reportUpload->id) }}"
            type="application/pdf"
            style="width:100%; height:80vh;"
        >
            <div style="padding:24px; text-align:center;">
                <p style="color:#6b7280; margin-bottom:14px;">
                    PDF tidak bisa ditampilkan langsung di browser.
                </p>

                <a href="{{ route('upload-terpadu.preview', $reportUpload->id) }}"
                   target="_blank"
                   style="background:#2563eb; color:white; padding:10px 14px; border-radius:8px; text-decoration:none; font-weight:700;">
                    Buka di Tab Baru
                </a>

                <a href="{{ route('upload-terpadu.download', $reportUpload->id) }}"
                   style="background:#16a34a; color:white; padding:10px 14px; border-radius:8px; text-decoration:none; font-weight:700; margin-left:8px;">
                    Unduh PDF
                </a>
            </div>
        </object>

    </div>
</div>
</x-app-layout>