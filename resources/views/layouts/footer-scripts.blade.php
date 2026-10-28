<!-- jquery -->
<script src="{{ URL::asset('assets/js/jquery-3.3.1.min.js') }}"></script>
<!-- plugins-jquery -->
<script src="{{ URL::asset('assets/js/plugins-jquery.js') }}"></script>
<!-- plugin_path -->
<script type="text/javascript">var plugin_path = '{{ asset('assets/js') }}/';</script>

<!-- chart -->
<script src="{{ URL::asset('assets/js/chart-init.js') }}"></script>
<!-- calendar -->
<script src="{{ URL::asset('assets/js/calendar.init.js') }}"></script>
<!-- charts sparkline -->
<script src="{{ URL::asset('assets/js/sparkline.init.js') }}"></script>
<!-- charts morris -->
<script src="{{ URL::asset('assets/js/morris.init.js') }}"></script>
<!-- datepicker -->
<script src="{{ URL::asset('assets/js/datepicker.js') }}"></script>
<!-- sweetalert2 -->
<script src="{{ URL::asset('assets/js/sweetalert2.js') }}"></script>
<!-- toastr -->
@yield('js')
<script src="{{ URL::asset('assets/js/toastr.js') }}"></script>
<!-- validation -->
<script src="{{ URL::asset('assets/js/validation.js') }}"></script>
<!-- lobilist -->
<script src="{{ URL::asset('assets/js/lobilist.js') }}"></script>
<!-- custom -->
<script src="{{ URL::asset('assets/js/custom.js') }}"></script>


<script>
    $(document).ready(function() {
        $('#datatable').DataTable();
    } );
</script>

{{-- زر طباعة مع تحديد الأعمدة لجميع الجداول --}}
<script>
$(document).ready(function () {
    // إضافة زر طباعة فوق كل جدول رئيسي (id="datatable")
    $('table#datatable').each(function () {
        var $table = $(this);
        var printBtn = $('<button/>', {
            type: 'button',
            class: 'btn btn-info mb-2',
            html: '<i class="fa fa-print"></i> طباعة',
            click: function () { openPrintModal($table); }
        });
        $table.closest('.table-responsive').before(printBtn);
    });

    // بناء نافذة اختيار الأعمدة وإظهارها
    function openPrintModal($table) {
        var $theadCells = $table.find('thead tr:first').children('th');
        var listHtml = '<div class="form-check mb-2">' +
            '<input class="form-check-input" type="checkbox" id="printCheckAll" checked>' +
            '<label class="form-check-label" for="printCheckAll"><strong>تحديد الكل</strong></label></div><hr>';

        $theadCells.each(function (index) {
            var label = $(this).text().trim() || ('عمود ' + (index + 1));
            // العمود الأخير (العمليات) غير محدد افتراضياً
            var isProcesses = label.indexOf('العمليات') !== -1 || label.indexOf('Process') !== -1;
            listHtml += '<div class="form-check">' +
                '<input class="form-check-input print-col-check" type="checkbox" value="' + index + '" id="printCol' + index + '"' + (isProcesses ? '' : ' checked') + '>' +
                '<label class="form-check-label" for="printCol' + index + '">' + label + '</label></div>';
        });

        var modalHtml =
            '<div class="modal fade" id="printColumnsModal" tabindex="-1" role="dialog">' +
            '  <div class="modal-dialog" role="document">' +
            '    <div class="modal-content" style="border-radius:12px">' +
            '      <div class="modal-header bg-info text-white">' +
            '        <h5 class="modal-title">اختيار الأعمدة المراد طباعتها</h5>' +
            '        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>' +
            '      </div>' +
            '      <div class="modal-body" style="text-align:right">' + listHtml + '</div>' +
            '      <div class="modal-footer">' +
            '        <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>' +
            '        <button type="button" class="btn btn-info" id="doPrintBtn"><i class="fa fa-print"></i> طباعة</button>' +
            '      </div>' +
            '    </div>' +
            '  </div>' +
            '</div>';

        $('#printColumnsModal').remove();
        $('body').append(modalHtml);

        $('#printCheckAll').on('change', function () {
            $('.print-col-check').prop('checked', this.checked);
        });

        $('#doPrintBtn').on('click', function () {
            var selected = $('.print-col-check:checked').map(function () { return parseInt(this.value); }).get();
            if (!selected.length) { alert('يرجى اختيار عمود واحد على الأقل'); return; }
            printSelectedColumns($table, selected);
            $('#printColumnsModal').modal('hide');
        });

        $('#printColumnsModal').modal('show');
    }

    // طباعة الأعمدة المحددة (جميع الصفوف حتى المصفّاة في DataTables)
    function printSelectedColumns($table, columns) {
        var headers = [];
        var $theadCells = $table.find('thead tr:first').children('th');
        $.each(columns, function (_, i) { headers.push($theadCells.eq(i).text().trim()); });

        var rows = [];
        var dt = $.fn.DataTable.isDataTable($table) ? $table.DataTable() : null;
        var $rows;
        if (dt) {
            $rows = $(dt.rows({search: 'applied'}).nodes());
        } else {
            $rows = $table.find('tbody tr');
        }
        $rows.each(function () {
            var cells = $(this).children('td');
            if (!cells.length) return;
            var row = [];
            $.each(columns, function (_, i) { row.push(cells.eq(i) ? cells.eq(i).text().trim() : ''); });
            rows.push(row);
        });

        var printWindow = window.open('', '_blank', 'width=1000,height=700');
        var tableRows = '';
        $.each(rows, function (_, row) {
            tableRows += '<tr>' + $.map(row, function (c) { return '<td>' + $('<div>').text(c).html() + '</td>'; }).join('') + '</tr>';
        });
        var tableHead = '<tr>' + $.map(headers, function (h) { return '<th>' + $('<div>').text(h).html() + '</th>'; }).join('') + '</tr>';
        var today = new Date().toLocaleDateString('ar-EG');
        var title = $('meta[name="title"]').attr('content') || document.title || 'تقرير';

        printWindow.document.write(
            '<html dir="rtl" lang="ar"><head><meta charset="utf-8"><title>' + title + '</title>' +
            '<style>' +
            'body{font-family:Arial,Tahoma,sans-serif;padding:20px;direction:rtl}' +
            'h2,h4{margin:5px 0;text-align:center}' +
            'table{width:100%;border-collapse:collapse;margin-top:15px}' +
            'th,td{border:1px solid #333;padding:8px;text-align:center;font-size:13px}' +
            'th{background:#e9ecef}' +
            '@media print{.no-print{display:none}}' +
            '</style></head><body>' +
            '<h2>' + title + '</h2>' +
            '<h4>تاريخ الطباعة: ' + today + '</h4>' +
            '<table><thead>' + tableHead + '</thead><tbody>' + tableRows + '</tbody></table>' +
            '<script>window.onload=function(){window.print();}<\/script>' +
            '</body></html>'
        );
        printWindow.document.close();
    }
});
</script>



@if (App::getLocale() == 'en')
    <script src="{{ URL::asset('assets/js/bootstrap-datatables/en/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('assets/js/bootstrap-datatables/en/dataTables.bootstrap4.min.js') }}"></script>
@else
    <script src="{{ URL::asset('assets/js/bootstrap-datatables/ar/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('assets/js/bootstrap-datatables/ar/dataTables.bootstrap4.min.js') }}"></script>
@endif



<script>
    function CheckAll(className, elem) {
        var elements = document.getElementsByClassName(className);
        var l = elements.length;

        if (elem.checked) {
            for (var i = 0; i < l; i++) {
                elements[i].checked = true;
            }
        } else {
            for (var i = 0; i < l; i++) {
                elements[i].checked = false;
            }
        }
    }
</script>


<script>
    $(document).ready(function () {
        $('select[name="Grade_id"]').on('change', function () {
            var Grade_id = $(this).val();
            if (Grade_id) {
                $.ajax({
                    url: "{{ URL::to('Get_classrooms') }}/" + Grade_id,
                    type: "GET",
                    dataType: "json",
                    success: function (data) {
                        $('select[name="Classroom_id"]').empty();
                        $('select[name="Classroom_id"]').append('<option selected disabled >{{trans('Parent_trans.Choose')}}...</option>');
                        $.each(data, function (key, value) {
                            $('select[name="Classroom_id"]').append('<option value="' + key + '">' + value + '</option>');
                        });

                    },
                });
            }

            else {
                console.log('AJAX load did not work');
            }
        });
    });
</script>


<script>
    $(document).ready(function () {
        $('select[name="Classroom_id"]').on('change', function () {
            var Classroom_id = $(this).val();
            if (Classroom_id) {
                $.ajax({
                    url: "{{ URL::to('Get_Sections') }}/" + Classroom_id,
                    type: "GET",
                    dataType: "json",
                    success: function (data) {
                        $('select[name="section_id"]').empty();
                        $.each(data, function (key, value) {
                            $('select[name="section_id"]').append('<option value="' + key + '">' + value + '</option>');
                        });

                    },
                });
            }

            else {
                console.log('AJAX load did not work');
            }
        });
    });
</script>





<script>
    $(document).ready(function () {
        $('select[name="Grade_id_new"]').on('change', function () {
            var Grade_id = $(this).val();
            if (Grade_id) {
                $.ajax({
                    url: "{{ URL::to('Get_classrooms') }}/" + Grade_id,
                    type: "GET",
                    dataType: "json",
                    success: function (data) {
                        $('select[name="Classroom_id_new"]').empty();
                        $('select[name="Classroom_id_new"]').append('<option selected disabled >{{trans('Parent_trans.Choose')}}...</option>');
                        $.each(data, function (key, value) {
                            $('select[name="Classroom_id_new"]').append('<option value="' + key + '">' + value + '</option>');
                        });

                    },
                });
            }

            else {
                console.log('AJAX load did not work');
            }
        });
    });
</script>


<script>
    $(document).ready(function () {
        $('select[name="Classroom_id_new"]').on('change', function () {
            var Classroom_id = $(this).val();
            if (Classroom_id) {
                $.ajax({
                    url: "{{ URL::to('Get_Sections') }}/" + Classroom_id,
                    type: "GET",
                    dataType: "json",
                    success: function (data) {
                        $('select[name="section_id_new"]').empty();
                        $.each(data, function (key, value) {
                            $('select[name="section_id_new"]').append('<option value="' + key + '">' + value + '</option>');
                        });

                    },
                });
            }

            else {
                console.log('AJAX load did not work');
            }
        });
    });
</script>


