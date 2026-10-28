{{--
    كومبوننت جدول عام: فلترة فورية + طباعة مع تحديد الأعمدة

    الاستخدام:
    <x-data-table id="students_table" title="قائمة الطلاب">
        <thead>
            <tr><th>الاسم</th><th>الرقم الوطني</th>...</tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr><td>...</td></tr>
            @endforeach
        </tbody>
    </x-data-table>

    الخصائص:
    - id        : معرف فريد للجدول (اختياري، يولّد تلقائياً)
    - title     : عنوان يظهر في نافذة الطباعة (اختياري)
    - printable : إظهار زر الطباعة (افتراضي true)
    - filterable: إظهار حقل الفلترة (افتراضي true)
--}}
@props([
    'id' => null,
    'title' => null,
    'printable' => true,
    'filterable' => true,
])
@php
    $tableId = $id ?? ('dtc_' . uniqid());
    $printTitle = $title ?? (document_title() ?? 'تقرير');
@endphp

<div class="dtc-wrapper" data-dtc="{{ $tableId }}" data-title="{{ $printTitle }}">
    <div class="dtc-toolbar d-flex flex-wrap align-items-center gap-2 mb-2">
        @if($filterable)
            <div class="dtc-filter flex-grow-1" style="max-width:320px">
                <input type="text" class="form-control dtc-search" placeholder="🔍 فلترة / بحث في الجدول...">
            </div>
        @endif
        @if($printable)
            <button type="button" class="btn btn-info dtc-print-btn">
                <i class="fa fa-print"></i> طباعة
            </button>
        @endif
        <span class="dtc-count text-muted small mr-auto"></span>
    </div>

    <div class="table-responsive">
        <table id="{{ $tableId }}" {{ $attributes->merge(['class' => 'table table-hover']) }} style="text-align:center">
            {{ $slot }}
        </table>
    </div>
</div>

@once
@push('styles')
<style>
.dtc-wrapper .dtc-search { border-radius: 8px; }
.dtc-wrapper .dtc-print-btn { border-radius: 8px; }
.dtc-modal-col-list { max-height: 300px; overflow-y: auto; text-align: right; }
.dtc-modal-col-list .form-check { padding-right: 1.5rem; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    if (window.__dtcInit) return;
    window.__dtcInit = true;

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.dtc-wrapper').forEach(initComponent);
    });

    function initComponent(wrapper) {
        var tableId = wrapper.getAttribute('data-dtc');
        var table = wrapper.querySelector('table');
        var tbody = table.querySelector('tbody');

        // ---------- الفلترة الفورية ----------
        var search = wrapper.querySelector('.dtc-search');
        if (search) {
            search.addEventListener('input', function () {
                var q = this.value.trim().toLowerCase();
                var visible = 0;
                tbody.querySelectorAll('tr').forEach(function (tr) {
                    if (tr.querySelector('td') === null) return; // صف فارغ
                    var match = tr.textContent.toLowerCase().indexOf(q) !== -1;
                    tr.style.display = match ? '' : 'none';
                    if (match) visible++;
                });
                updateCount(wrapper, tbody, visible);
            });
            updateCount(wrapper, tbody, tbody.querySelectorAll('tr').length);
        }

        // ---------- زر الطباعة + تحديد الأعمدة ----------
        var printBtn = wrapper.querySelector('.dtc-print-btn');
        if (printBtn) {
            printBtn.addEventListener('click', function () { openColumnModal(wrapper, table, tbody); });
        }
    }

    function updateCount(wrapper, tbody, n) {
        var c = wrapper.querySelector('.dtc-count');
        if (c) c.textContent = 'عدد السجلات: ' + n;
    }

    function getHeaders(table) {
        var cells = table.querySelector('thead tr').children;
        var out = [];
        for (var i = 0; i < cells.length; i++) {
            var t = cells[i].textContent.trim();
            out.push(t || ('عمود ' + (i + 1)));
        }
        return out;
    }

    function isOperations(label) {
        return label.indexOf('العمليات') !== -1 || label.toLowerCase().indexOf('process') !== -1;
    }

    // نافذة اختيار الأعمدة
    function openColumnModal(wrapper, table, tbody) {
        document.getElementById('dtcPrintModal')?.remove();
        var headers = getHeaders(table);

        var list = '<div class="form-check mb-2">' +
            '<input class="form-check-input" type="checkbox" id="dtcCheckAll" checked>' +
            '<label class="form-check-label" for="dtcCheckAll"><strong>تحديد الكل</strong></label></div><hr>';
        headers.forEach(function (h, i) {
            var checked = isOperations(h) ? '' : ' checked';
            list += '<div class="form-check"><input class="form-check-input dtc-col-check" type="checkbox" value="' + i + '" id="dtcCol' + i + '"' + checked + '>' +
                '<label class="form-check-label" for="dtcCol' + i + '">' + escapeHtml(h) + '</label></div>';
        });

        var modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'dtcPrintModal';
        modal.innerHTML =
            '<div class="modal-dialog" role="document"><div class="modal-content" style="border-radius:12px">' +
            '<div class="modal-header bg-info text-white"><h5 class="modal-title">اختيار الأعمدة المراد طباعتها</h5>' +
            '<button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>' +
            '<div class="modal-body dtc-modal-col-list">' + list + '</div>' +
            '<div class="modal-footer">' +
            '<button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>' +
            '<button type="button" class="btn btn-info" id="dtcDoPrint"><i class="fa fa-print"></i> طباعة</button>' +
            '</div></div></div>';
        document.body.appendChild(modal);

        modal.querySelector('#dtcCheckAll').addEventListener('change', function () {
            modal.querySelectorAll('.dtc-col-check').forEach(function (cb) { cb.checked = this.checked; }, this);
        });

        modal.querySelector('#dtcDoPrint').addEventListener('click', function () {
            var selected = Array.prototype.map.call(
                modal.querySelectorAll('.dtc-col-check:checked'),
                function (cb) { return parseInt(cb.value); }
            );
            if (!selected.length) { alert('يرجى اختيار عمود واحد على الأقل'); return; }
            printTable(wrapper, table, tbody, headers, selected);
            $('#dtcPrintModal').modal('hide');
        });

        $('#dtcPrintModal').modal('show');
    }

    // الطباعة: الأعمدة المحددة + الصفوف المفلترة فقط
    function printTable(wrapper, table, tbody, headers, columns) {
        var rows = [];
        tbody.querySelectorAll('tr').forEach(function (tr) {
            if (tr.style.display === 'none') return; // تخطي الصفوف المستبعدة بالفلترة
            var cells = tr.children;
            if (!cells.length) return;
            var row = [];
            columns.forEach(function (i) { row.push(cells[i] ? cells[i].textContent.trim() : ''); });
            rows.push(row);
        });

        var title = wrapper.getAttribute('data-title') || 'تقرير';
        var today = new Date().toLocaleDateString('ar-EG');
        var head = columns.map(function (i) { return '<th>' + escapeHtml(headers[i]) + '</th>'; }).join('');
        var body = rows.map(function (r) {
            return '<tr>' + r.map(function (c) { return '<td>' + escapeHtml(c) + '</td>'; }).join('') + '</tr>';
        }).join('');

        var w = window.open('', '_blank', 'width=1000,height=700');
        w.document.write(
            '<html dir="rtl" lang="ar"><head><meta charset="utf-8"><title>' + escapeHtml(title) + '</title>' +
            '<style>body{font-family:Arial,Tahoma,sans-serif;padding:20px;direction:rtl}' +
            'h2,h4{margin:5px 0;text-align:center}' +
            'table{width:100%;border-collapse:collapse;margin-top:15px}' +
            'th,td{border:1px solid #333;padding:8px;text-align:center;font-size:13px}' +
            'th{background:#e9ecef}</style></head><body>' +
            '<h2>' + escapeHtml(title) + '</h2><h4>تاريخ الطباعة: ' + today + '</h4>' +
            '<table><thead><tr>' + head + '</tr></thead><tbody>' + body + '</tbody></table>' +
            '<script>window.onload=function(){window.print();}<\/script></body></html>'
        );
        w.document.close();
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
})();
</script>
@endpush
@endonce
